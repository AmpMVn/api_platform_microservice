<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Price\Deal\Deal;
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
    name: 'do:sync:rs:objects:pricedeals',
    description: 'Do sync with old Rentsoft API',
)]
class PriceDealsCommand extends Command
{

    private $rsApi;
    private $em;
    private $apiGateway;

    public function __construct(RsApiHttpClient $rsApi, EntityManagerInterface $em, ApiGatewayKeycloakHttpClient $apiGateway)
    {
        $this->rsApi = $rsApi;
        $this->em = $em;
        $this->apiGateway = $apiGateway;

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
        $output->writeln("\n<info>Rentsoft => Objects, Pricedeals</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $z = 0;
        $y = 0;
        $a = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */
            $clientRentsoftId = $client->getRentsoftClientId();

            $this->rsApi->getObjectsByClientId($clientRentsoftId);
            $remoteObjects = $this->rsApi->getData()->body->results;

            if (!$remoteObjects) {
                continue;
            }

            # Build string of article ids
            $objectIdString = "";
            foreach ($remoteObjects as $remoteObject) {
                $objectIdString .= $remoteObject->id . ",\n";
            }

            $objectIdString = substr($objectIdString, 0, ( strlen($objectIdString) - 2 ));

            $postFields = [
                "sql" => "SELECT * FROM object_price_deals WHERE object_id IN (" . $objectIdString . ")",
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

                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'remoteId' => $fetch->object_id ]);

                if (!$syncedObject) {
                    $z ++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedObject->getLocaleId());
                }

                ## REMOVE EXISTING ENTRIES
                if (!in_array($fetch->object_id, $removedArray)) {

                    foreach ($article->getPriceDeals() as $priceDeal) {
                        $article->getPriceDeals()->removeElement($priceDeal);
                        $y ++;
                    }

                    array_push($removedArray, $fetch->object_id);
                }

                $syncedPriceDeal = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-pricedeals', 'remoteId' => $fetch->config_price_deals_id ]);

                if (!$syncedPriceDeal) {
                    $a ++;
                    continue;
                }


                $priceDeal = $this->em->getRepository(Deal::class)->find($syncedPriceDeal->getLocaleId());

                $article->addPriceDeal($priceDeal);
                $x ++;
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Pricedeals (" . $x . " created, " . $y . " removed, " . $z . " skipped [no object found], " . $a . " skipped [no pricedeal settings found])</>");

        return Command::SUCCESS;
    }

}
