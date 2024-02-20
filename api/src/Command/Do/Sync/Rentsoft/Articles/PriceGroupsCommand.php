<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

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
    name: 'do:sync:rs:articles:pricerates',
    description: 'Do sync with old Rentsoft API',
)]
class PriceGroupsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Articles, Pricerates</>");
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

            $this->rsApi->getArticlesByClientId($clientRentsoftId);
            $remoteArticles = $this->rsApi->getData()->body->entries;

            if (!$remoteArticles) {
                continue;
            }

            # Build string of article ids
            $articleIdString = "";
            foreach ($remoteArticles as $remoteArticle) {
                $articleIdString .= $remoteArticle->id . ",\n";
            }

            $articleIdString = substr($articleIdString, 0, ( strlen($articleIdString) - 2 ));

            $postFields = [
                "sql" => "SELECT
                        article_price_group.*
                    FROM article_price_group WHERE article_price_group.article_id IN  (" . $articleIdString . ")",
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

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'article-command', 'remoteId' => $fetch->article_id ]);

                if (!$syncedArticle) {
                    $z ++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                }

                ## REMOVE EXISTING ENTRIES
                if (!in_array($fetch->article_id, $removedArray)) {

                    foreach ($article->getPriceRates() as $priceRate) {
                        $article->getPriceRates()->removeElement($priceRate);
                        $y ++;
                    }

                    array_push($removedArray, $fetch->article_id);
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

//                ### WHEN NORMAL PREIS PRO TAG
//                if ($remoteArticle->price_calculation_pricetype == 0) {
//                    $start = new \DateTime();
//                    $start->setDate(date("Y"), 1, 1);
//
//                    $end = new \DateTime();
//                    $end->setDate(2030, 12, 31);
//
//                    $priceGroup = new Group();
//                    $priceGroup->setName("Preis pro Tag Standard");
//                    $priceGroup->setClient($client);
//                    $priceGroup->setValidFrom($start);
//                    $priceGroup->setValidTo($end);
//                    $priceGroup->setDefaultPriceRate(true);
//                    $priceGroup->setEnabledMsOnlineBooking(true);
//                    $this->em->persist($priceGroup);
//
//                    $priceGroupEntry = new Entry();
//                    $priceGroupEntry->setPriceRateGroup($priceGroup);
//                    $priceGroupEntry->setUnit(24);
//                    $priceGroupEntry->setUnitPrice($remoteArticle->price_per_day);
//                    $priceGroupEntry->setUnitFrom(0);
//                    $priceGroupEntry->setUnitTo(9999);
//
//                    $this->em->persist($priceGroupEntry);
//
//                    $article->addPriceRates($priceGroup);
//                    $x ++;
//                }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Pricerates (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found], " . $a . " skipped [no pricerate settings])</>");

        return Command::SUCCESS;

    }

}
