<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Settings\Category;
use App\Entity\Sync\UpdatedRentsoft;
use App\Extension\RsApiHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'do:sync:rs:articles:categories',
    description: 'Do sync with old Rentsoft API',
)]
class CategoriesCommand extends Command
{

    private $apiGateway;
    private $rsApi;
    private $em;

    public function __construct(RsApiHttpClient $rsApi, ApiGatewayKeycloakHttpClient $apiGateway, EntityManagerInterface $em)
    {
        $this->apiGateway = $apiGateway;
        $this->rsApi = $rsApi;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addArgument('client-uuid', InputArgument::OPTIONAL, 'Provide client uuid for single client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln("\n<info>Rentsoft => Articles, Categories</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var Group $client */

            $clientRentsoftId = $client->getRentsoftClientId();

            ### LEVEL ONE ###
            $postFields = [
                "sql" => "SELECT * FROM article_category WHERE client_id = ".$clientRentsoftId." AND level = 0 AND name != '' AND deleted = 0 ORDER BY level ASC",
            ];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array(
                $curl, [
                         CURLOPT_RETURNTRANSFER => 1,
                         CURLOPT_URL => $url,
                         CURLOPT_USERAGENT => 'JSON CONNECTOR',
                         CURLOPT_POST => 1,
                         CURLOPT_POSTFIELDS => $postFields,
                         CURLOPT_SSL_VERIFYHOST => 0,
                         CURLOPT_SSL_VERIFYPEER => 0,
                     ]
            );

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            foreach ($response as $remoteCategory)
            {
                $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->id ]);

                if (!$syncedCategory) {

                    $category = new Category();
                    $this->em->persist($category);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Category::class);
                    $syncRs->setLocaleId($category->getId());
                    $syncRs->setRemoteId($remoteCategory->id);
                    $syncRs->setRemoteAction('settings-category');
                    $this->em->persist($syncRs);

                    $x ++;
                } else {
                    $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                    $y ++;
                }

                $category->setClientId($client->getId());
                $category->setName($remoteCategory->name);

                $category->setEnableOnlineBooking(false);
                if ($remoteCategory->status_online == 1)
                {
                    $category->setEnableOnlineBooking(true);
                }

                $this->em->flush();
            }

            ### LEVEL TWO ###
            $postFields = [
                "sql" => "SELECT * FROM article_category WHERE client_id = ".$clientRentsoftId." AND level = 1 AND name != '' AND deleted = 0 ORDER BY level ASC",
            ];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array(
                $curl, [
                         CURLOPT_RETURNTRANSFER => 1,
                         CURLOPT_URL => $url,
                         CURLOPT_USERAGENT => 'JSON CONNECTOR',
                         CURLOPT_POST => 1,
                         CURLOPT_POSTFIELDS => $postFields,
                         CURLOPT_SSL_VERIFYHOST => 0,
                         CURLOPT_SSL_VERIFYPEER => 0,
                     ]
            );

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            foreach ($response as $remoteCategory)
            {

                $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->id ]);
                $syncedParentCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->parent_id ]);

                if (!$syncedParentCategory)
                {
                    $z ++;
                    continue;
                }

                if (!$syncedCategory) {

                    $category = new Category();
                    $this->em->persist($category);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Category::class);
                    $syncRs->setLocaleId($category->getId());
                    $syncRs->setRemoteId($remoteCategory->id);
                    $syncRs->setRemoteAction('settings-category');
                    $this->em->persist($syncRs);

                    $x ++;
                } else {
                    $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                    $y ++;
                }

                $categoryParent = $this->em->getRepository(Category::class)->find($syncedParentCategory->getLocaleId());

                $category->setClientId($client->getId());
                $category->setName($remoteCategory->name);
                $category->setParent($categoryParent);

                $category->setEnableOnlineBooking(false);
                if ($remoteCategory->status_online == 1)
                {
                    $category->setEnableOnlineBooking(true);
                }

                $this->em->flush();

            }

            ### LEVEL THREE ###
            $postFields = [
                "sql" => "SELECT * FROM article_category WHERE client_id = ".$clientRentsoftId." AND level = 2 AND name != '' AND deleted = 0 ORDER BY level ASC",
            ];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array(
                $curl, [
                         CURLOPT_RETURNTRANSFER => 1,
                         CURLOPT_URL => $url,
                         CURLOPT_USERAGENT => 'JSON CONNECTOR',
                         CURLOPT_POST => 1,
                         CURLOPT_POSTFIELDS => $postFields,
                         CURLOPT_SSL_VERIFYHOST => 0,
                         CURLOPT_SSL_VERIFYPEER => 0,
                     ]
            );

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            foreach ($response as $remoteCategory)
            {
                $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->id ]);
                $syncedParentCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->parent_id ]);

                if (!$syncedParentCategory)
                {
                    $z ++;
                    continue;
                }

                if (!$syncedCategory) {

                    $category = new Category();
                    $this->em->persist($category);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Category::class);
                    $syncRs->setLocaleId($category->getId());
                    $syncRs->setRemoteId($remoteCategory->id);
                    $syncRs->setRemoteAction('settings-category');
                    $this->em->persist($syncRs);

                    $x ++;
                } else {
                    $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                    $y ++;
                }

                $categoryParent = $this->em->getRepository(Category::class)->find($syncedParentCategory->getLocaleId());

                $category->setClientId($client->getId());
                $category->setName($remoteCategory->name);
                $category->setParent($categoryParent);

                $category->setEnableOnlineBooking(false);
                if ($remoteCategory->status_online == 1)
                {
                    $category->setEnableOnlineBooking(true);
                }
            }

            $this->em->flush();

            ### LEVEL FOUR ###
            $postFields = [
                "sql" => "SELECT * FROM article_category WHERE client_id = ".$clientRentsoftId." AND level = 3 AND name != '' AND deleted = 0 ORDER BY level ASC",
            ];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array(
                $curl, [
                         CURLOPT_RETURNTRANSFER => 1,
                         CURLOPT_URL => $url,
                         CURLOPT_USERAGENT => 'JSON CONNECTOR',
                         CURLOPT_POST => 1,
                         CURLOPT_POSTFIELDS => $postFields,
                         CURLOPT_SSL_VERIFYHOST => 0,
                         CURLOPT_SSL_VERIFYPEER => 0,
                     ]
            );

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            foreach ($response as $remoteCategory)
            {
                $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->id ]);
                $syncedParentCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->parent_id ]);

                if (!$syncedParentCategory)
                {
                    $z ++;
                    continue;
                }

                if (!$syncedCategory) {

                    $category = new Category();
                    $this->em->persist($category);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Category::class);
                    $syncRs->setLocaleId($category->getId());
                    $syncRs->setRemoteId($remoteCategory->id);
                    $syncRs->setRemoteAction('settings-category');
                    $this->em->persist($syncRs);

                    $x ++;
                } else {
                    $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                    $y ++;
                }

                $categoryParent = $this->em->getRepository(Category::class)->find($syncedParentCategory->getLocaleId());

                $category->setClientId($client->getId());
                $category->setName($remoteCategory->name);
                $category->setParent($categoryParent);

                $category->setEnableOnlineBooking(false);
                if ($remoteCategory->status_online == 1)
                {
                    $category->setEnableOnlineBooking(true);
                }
            }

            $this->em->flush();

            ### LEVEL FIVE ###
            $postFields = [
                "sql" => "SELECT * FROM article_category WHERE client_id = ".$clientRentsoftId." AND level = 4 AND name != '' AND deleted = 0 ORDER BY level ASC",
            ];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array(
                $curl, [
                         CURLOPT_RETURNTRANSFER => 1,
                         CURLOPT_URL => $url,
                         CURLOPT_USERAGENT => 'JSON CONNECTOR',
                         CURLOPT_POST => 1,
                         CURLOPT_POSTFIELDS => $postFields,
                         CURLOPT_SSL_VERIFYHOST => 0,
                         CURLOPT_SSL_VERIFYPEER => 0,
                     ]
            );

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            foreach ($response as $remoteCategory)
            {
                $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->id ]);
                $syncedParentCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-category', 'remoteId' => $remoteCategory->parent_id ]);

                if (!$syncedParentCategory)
                {
                    $z ++;
                    continue;
                }

                if (!$syncedCategory) {

                    $category = new Category();
                    $this->em->persist($category);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Category::class);
                    $syncRs->setLocaleId($category->getId());
                    $syncRs->setRemoteId($remoteCategory->id);
                    $syncRs->setRemoteAction('settings-category');
                    $this->em->persist($syncRs);

                    $x ++;
                } else {
                    $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                    $y ++;
                }

                $categoryParent = $this->em->getRepository(Category::class)->find($syncedParentCategory->getLocaleId());

                $category->setClientId($client->getId());
                $category->setName($remoteCategory->name);
                $category->setParent($categoryParent);

                $category->setEnableOnlineBooking(false);
                if ($remoteCategory->status_online == 1)
                {
                    $category->setEnableOnlineBooking(true);
                }
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Categories (" . $x . " created, " . $y . " updated, ".$z." skipped [because no parent found])</>");

        return Command::SUCCESS;
    }

}
