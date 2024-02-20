<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Article\Article;
use App\Entity\Article\Attribute;
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
    name: 'do:sync:rs:articles:attributes',
    description: 'Do sync with old Rentsoft API',
)]
class AttributesCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n<info>Rentsoft => Articles, Attributes</>");
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

            $postFields = array("sql" => "SELECT
                        article_feature_attribute_set_entry.id,
                        article_feature_attribute_set_entry.article_id,
                        article_feature_attribute_set_entry.name,
                        article_feature_attribute_set_entry.value,
                        article_attribute_set_entry.type
                    FROM article_feature_attribute_set_entry
                    LEFT JOIN article_attribute_set ON article_attribute_set.id = article_feature_attribute_set_entry.article_attribute_set_id
                    LEFT JOIN article_attribute_set_entry ON article_attribute_set_entry.id = article_feature_attribute_set_entry.article_attribute_set_entry_id
                    LEFT JOIN article ON article.id = article_feature_attribute_set_entry.article_id
                    WHERE article_feature_attribute_set_entry.article_id IN (" . $articleIdString . ") AND article.article_attribute_set_id = article_feature_attribute_set_entry.article_attribute_set_id ORDER BY article_feature_attribute_set_entry.article_id ASC");

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
            $counter = 0;

            foreach ($response as $fetch) {

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $fetch->article_id]);

                if (!$syncedArticle) {
                    $z++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                }

                # REMOVE EXISTING ENTRIES FOR CLEAN UP
                if (!in_array($fetch->article_id, $removedArray)) {
                    foreach ($article->getAttributes() as $attribute) {
                        $this->em->remove($attribute);
                        $this->em->flush();
                        $y++;
                    }

                    array_push($removedArray, $fetch->article_id);
                }

                # ADD NEW ATTRIBUTES
                if ($fetch->value != "") {

                    $attribute = new Attribute();
                    $attribute->setArticle($article);
                    $attribute->setType($fetch->type);
                    $attribute->setName($fetch->name);
                    $attribute->setValue($fetch->value);
                    $this->em->persist($attribute);

                    if ($fetch->name == "Saison") {
                        $article->setRelevance($fetch->value);
                        $this->em->persist($article);
                    }

                    if (isset($priorityCounter[$fetch->article_id])) {
                        $attribute->setPriority($priorityCounter[$fetch->article_id]);
                        $priorityCounter[$fetch->article_id]++;
                    } else {
                        $attribute->setPriority(0);
                        $priorityCounter[$fetch->article_id] = 0;
                    }

                    $x++;
                }

                $counter++;
                if ($counter >= 100) {
                    $this->em->flush();
                    $counter = 0;
                }
            }
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Attributes (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found])</>");

        return Command::SUCCESS;
    }

}
