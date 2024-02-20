<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Article\Accessories;
use App\Entity\Article\Article;
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
    name: 'do:sync:rs:articles:extras',
    description: 'Do sync with old Rentsoft API',
)]
class ExtrasCommand extends Command
{
    private $apiGateway;
    private $rsApi;
    private $em;

    public function __construct(RsApiHttpClient $rsApi, EntityManagerInterface $em, ApiGatewayKeycloakHttpClient $apiGateway)
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n<info>Rentsoft => Articles, Accessories</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $a = 0;
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

            $this->rsApi->getArticlesByClientId($clientRentsoftId);
            $remoteArticles = $this->rsApi->getData()->body->entries;

            if (!$remoteArticles) {
                continue;
            }

            # Build string of article ids
            $articleIdString = "";
            foreach ($remoteArticles as $remoteArticle) {
                $articleIdString .= $remoteArticle->id . ", ";
            }

            $articleIdString = substr($articleIdString, 0, (strlen($articleIdString) - 2));

            $postFields = ["sql" => "SELECT article_online_settings_article.*, article_category.name AS category_name FROM article_online_settings_article LEFT JOIN article ON article_online_settings_article.article_id_real = article.id LEFT JOIN article_category ON article_category.id = article.article_category_id WHERE article_online_settings_article.article_id IN (" . $articleIdString . ") ORDER BY prio ASC"];

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
                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $fetch->article_id_real]);

                if (!$syncedArticle || !$syncedArticleExtra) {
                    $z++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticleExtra->getLocaleId());
                    $articleExtra = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());

                    if (!$article || !$articleExtra)
                    {
                        $a ++;
                        continue;
                    }
                }

                ### REMOVE EXISTING ENTRIES ###
                if (!in_array($fetch->article_id, $removedArray)) {

                    /** @var Accessories $existingAccessorie */
                    foreach ($article->getAccessories() as $existingAccessorieEntry) {
                        $this->em->remove($existingAccessorieEntry);
                        $y++;
                    }

                    array_push($removedArray, $fetch->article_id);
                }

                $accessorie = new Accessories();
                $accessorie->setArticleParent($article);
                $accessorie->setArticleChild($articleExtra);
                $accessorie->setMaxCount($fetch->max_count);
                $accessorie->setRequiredMsOnlineBooking(($fetch->required == "0") ? false : true);
                $accessorie->setEnabledMsOnlineBooking(($fetch->status_online == "0") ? false : true);
                $accessorie->setTakeoverInProcess(($fetch->status_takeover == "0") ? false : true);

                if ($fetch->category_name != "")
                {
                    $accessorie->setGroupName("Extras");
                } else {
                    $accessorie->setGroupName("Extras");
                }

                if (isset($priorityCounter[$fetch->article_id])) {
                    $accessorie->setPriority($priorityCounter[$fetch->article_id]);
                    $priorityCounter[$fetch->article_id]++;
                } else {
                    $accessorie->setPriority(0);
                    $priorityCounter[$fetch->article_id] = 0;
                }

                $this->em->persist($accessorie);
                $x++;
            }

            $this->em->flush();

        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Accessories (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found], " . $z . " skipped [no child article found])");

        return Command::SUCCESS;

    }

}
