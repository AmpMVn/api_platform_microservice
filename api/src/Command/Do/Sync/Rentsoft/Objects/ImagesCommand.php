<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Article\Image;
use App\Entity\Sync\UpdatedRentsoft;
use App\Extension\RsApiHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'do:sync:rs:objects:images',
    description: 'Do sync with old Rentsoft API',
)]
class ImagesCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects, Images</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */

            $clientRentsoftId = $client->getRentsoftClientId();

            /** @var UpdatedRentsoft $syncedRsClient */
            $objectResults = $this->em->getRepository(Article::class)->findBy(array('clientId' => $client->getId()));

            if (!$objectResults) {
                continue;
            }

            # Build string of article ids
            $objectIdString = "";
            /** @var Article $object */
            foreach ($objectResults as $object) {

                /** @var UpdatedRentsoft $syncedObject */
                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'localeId' => $object->getId() ]);

                if (!$syncedObject)
                {
                    continue;
                }

                $objectIdString .= $syncedObject->getRemoteId() . ", ";
            }

            $objectIdString = substr($objectIdString, 0, ( strlen($objectIdString) - 2 ));

            $postFields = [
                "sql" => "SELECT
                        car_image.*
                    FROM car_image
                    WHERE car_id IN (" . $objectIdString . ")",
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

            $removedArray = [];
            foreach ($response as $fetch) {

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'remoteId' => $fetch->car_id ]);

                if (!$syncedArticle) {
                    $z ++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                }

                ### REMOVE EXISTING ENTRIES ###
                if (!in_array($fetch->car_id, $removedArray)) {

                    foreach ($article->getImages() as $image) {
                        $y ++;
                        $this->em->remove($image);
                        $this->em->flush();
                    }

                    array_push($removedArray, $fetch->car_id);
                }

                $image = new Image();

                if ($fetch->prio == 1) {
                    $image->setMainImage(true);
                } else {
                    $image->setMainImage(false);
                }

                $image->setFilepath('https://media.rentsoft.de/_carimages/' . $fetch->filename);
                $image->setArticle($article);
                $image->setFilesize(1);
                $this->em->persist($image);

                $x ++;
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Images (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found])");

        return Command::SUCCESS;

    }

}
