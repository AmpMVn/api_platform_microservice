<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Accessories;
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
    name: 'do:sync:rs:objects:extras',
    description: 'Do sync with old Rentsoft API',
)]
class ExtrasCommand extends Command
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

    protected function configure(): void
    {
        $this
            ->addArgument('client-uuid', InputArgument::OPTIONAL, 'Provide client uuid for single client');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n<info>Rentsoft => Objects, Accessories</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid')) {
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

            $articleIdString = substr($articleIdString, 0, (strlen($articleIdString) - 2));

            $postFields = ["sql" => "SELECT
                                        object_online_settings_article.*,
                                        article_category.name AS category_name,
                                        (SELECT object_online_settings_article_category.activate_category_rules FROM object_online_settings_article_category WHERE object_online_settings_article_category.article_category_id = article_category.id AND object_online_settings_article_category.object_id = object_online_settings_article.object_id) AS activate_category_rules
FROM object_online_settings_article LEFT JOIN article ON article.id = object_online_settings_article.article_id LEFT JOIN article_category ON article_category.id = article.article_category_id
WHERE object_online_settings_article.object_id IN (" . $articleIdString . ") GROUP BY object_online_settings_article.id ORDER BY prio ASC"];

            $url = "https://json-connector.rentsoft.de/index.php";
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'JSON CONNECTOR',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ));

            $response = null;
            $responseOrig = curl_exec($curl);
            $response = json_decode($responseOrig);

            if (is_null($response)) {
                continue;
            }

            $removedArray = [];
            $priorityCounter = array();

            foreach ($response as $fetch) {

                $syncedArticleExtra = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $fetch->article_id]);
                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'object-command', 'remoteId' => $fetch->object_id]);

                if (!$syncedArticle || !$syncedArticleExtra) {
                    $z++;
                    continue;
                } else {
                    /** @var Article $article */
                    $articleExtra = $this->em->getRepository(Article::class)->find($syncedArticleExtra->getLocaleId());
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());

                    if (!$article || !$articleExtra) {
                        $z++;
                        continue;
                    }
                }

                ### REMOVE EXISTING ENTRIES ###
                if (!in_array($fetch->object_id, $removedArray)) {

                    /** @var Accessories $existingAccessorie */
                    foreach ($article->getAccessories() as $existingAccessorieEntry) {
                        $this->em->remove($existingAccessorieEntry);
                        $y++;
                    }

                    array_push($removedArray, $fetch->object_id);
                }

                $accessorie = new Accessories();
                $accessorie->setArticleParent($article);
                $accessorie->setArticleChild($articleExtra);
                $accessorie->setMaxCount($fetch->max_count);
                $accessorie->setRequiredMsOnlineBooking(($fetch->required == "0") ? false : true);
                $accessorie->setEnabledMsOnlineBooking(($fetch->status_online == "0") ? false : true);
                $accessorie->setTakeoverInProcess(($fetch->status_takeover == "0") ? false : true);
                $accessorie->setEnableSingleSelectionRule(false);

                if ($fetch->activate_category_rules == 1) {
                    $accessorie->setEnableSingleSelectionRule(true);
                }

                if ($fetch->category_name != "") {
                    $accessorie->setGroupName($fetch->category_name);
                } else {
                    $accessorie->setGroupName("Extras");
                }

//                if (isset($priorityCounter[$fetch->article_id]))
//                {
//                    $accessorie->setPriority($priorityCounter[$fetch->article_id]);
//                    $priorityCounter[$fetch->article_id] ++;
//                } else {
//                    $accessorie->setPriority(0);
//                    $priorityCounter[$fetch->article_id]  = 0;
//                }

                $accessorie->setPriority((int)$fetch->prio);

                $this->em->persist($accessorie);
                $x++;
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Accessories (" . $x . " created, " . $y . " removed, " . $z . " skipped [no object found])</>");

        return Command::SUCCESS;

    }

}
