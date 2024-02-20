<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
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
    name: 'do:sync:rs:objects:pricerates',
    description: 'Do sync with old Rentsoft API',
)]
class PriceGroupsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects, Pricerates</>");
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
            $articleIdString = "";
            foreach ($remoteObjects as $remoteArticle) {
                $articleIdString .= $remoteArticle->id . ",\n";
            }

            $articleIdString = substr($articleIdString, 0, ( strlen($articleIdString) - 2 ));

            $postFields = [
                "sql" => "SELECT
                        object_price_group.*
                    FROM object_price_group WHERE object_price_group.object_id IN  (" . $articleIdString . ")",
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

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'remoteId' => $fetch->object_id ]);

                if (!$syncedArticle) {
                    $z ++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                }

                ## REMOVE EXISTING ENTRIES
                if (!in_array($fetch->object_id, $removedArray)) {

                    foreach ($article->getPriceRates() as $priceRate) {
                        $article->getPriceRates()->removeElement($priceRate);
                        $y ++;
                    }

                    array_push($removedArray, $fetch->object_id);
                }

                $syncedPriceGroup = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-pricegroup-command', 'remoteId' => $fetch->config_price_group_id ]);

                if (!$syncedPriceGroup) {
                    $a ++;
                    continue;
                }

                /** @var \App\Entity\Price\Rate\Group $priceRate */
                $priceRate = $this->em->getRepository(\App\Entity\Price\Rate\Group::class)->find($syncedPriceGroup->getLocaleId());

                $article->addPriceRates($priceRate);
                $x ++;
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Pricerates (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found], " . $a . " skipped [no pricerate settings])</>");

        return Command::SUCCESS;
    }

}
