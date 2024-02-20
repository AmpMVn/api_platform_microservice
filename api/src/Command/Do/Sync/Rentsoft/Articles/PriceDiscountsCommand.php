<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Article\Article;
use App\Entity\Price\Discount\Discount;
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
    name: 'do:sync:rs:articles:pricediscounts',
    description: 'Do sync with old Rentsoft API',
)]
class PriceDiscountsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Articles, Pricediscounts</>");
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
                        article_price_discount.*
                    FROM article_price_discount WHERE article_price_discount.article_id IN  (" . $articleIdString . ")",
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

                    foreach ($article->getPriceDiscounts() as $priceDiscountEntry) {
                        $article->getPriceRates()->removeElement($priceDiscountEntry);
                        $y ++;
                    }

                    array_push($removedArray, $fetch->article_id);
                }

                $syncedPricediscount = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-pricediscount', 'remoteId' => $fetch->config_price_discount_id ]);

                if (!$syncedPricediscount) {
                    $a ++;
                    continue;
                }

                /** @var Discount $priceDiscount */
                $priceDiscount = $this->em->getRepository(Discount::class)->find($syncedPricediscount->getLocaleId());

                $article->addPriceDiscount($priceDiscount);
                $x ++;
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Pricediscounts (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found], " . $a . " skipped [no pricediscount settings])</>");

        return Command::SUCCESS;

    }

}
