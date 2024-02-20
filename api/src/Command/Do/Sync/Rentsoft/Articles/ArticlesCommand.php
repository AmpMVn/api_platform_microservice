<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Article\Article;
use App\Entity\Price\Rate\Entry;
use App\Entity\Price\Rate\Group;
use App\Entity\Settings\Category;
use App\Entity\Settings\Storage\Storage;
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
    name: 'do:sync:rs:articles',
    description: 'Do sync with old Rentsoft API',
)]
class ArticlesCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Articles</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $a = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */
            $clientRentsoftId = $client->getRentsoftClientId();

            ### DELETED ARTICLES ###
            $this->rsApi->getDeletedArticlesByClientId($clientRentsoftId);
            $deletedRemoteArticles = $this->rsApi->getData()->body;

            if (!$deletedRemoteArticles) {
                continue;
            }

            foreach ($deletedRemoteArticles as $deletedRemoteArticle) {

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $deletedRemoteArticle->Id]);

                if (!$syncedArticle) {
                    continue;
                }

                $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());

                if (!$article)
                {
                    continue;
                }


                $this->em->remove($article);

                $a++;
            }

            ### ACTIVE ARTICLES ###
            $this->rsApi->getArticlesByClientId($clientRentsoftId);

            $remoteArticles = $this->rsApi->getData()->body->entries;

            if (!$remoteArticles) {
                continue;
            }

            foreach ($remoteArticles as $remoteArticle) {

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $remoteArticle->id]);

                if (!$syncedArticle) {

                    $article = new Article();
                    $this->em->persist($article);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Article::class);
                    $syncRs->setLocaleId($article->getId());
                    $syncRs->setRemoteId($remoteArticle->id);
                    $syncRs->setRemoteAction('article-command');
                    $this->em->persist($syncRs);

                    $x++;
                } else {
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                    $y++;
                }

                if ($remoteArticle->article_category_id >= 1) {
                    $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-category', 'remoteId' => $remoteArticle->article_category_id]);

                    if ($syncedCategory) {
                        $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());
                        $article->setCategory($category);
                    }
                }

                $article->setName($remoteArticle->name);
                $article->setModel($remoteArticle->model);
                $article->setManufacturer($remoteArticle->manufacturer);
                $article->setDescription(html_entity_decode($remoteArticle->description_long));
                $article->setDescriptionTeaser(html_entity_decode($remoteArticle->description_short));

                if (strpos($remoteArticle->name, "KM Inklusive"))
                {
                    $freeKm = explode(" ", $remoteArticle->name);
                    $freeKm = $freeKm[0];

                    $article->setArticleType(Article::ARTICLE_TYPE_KM_PACKAGE);
                    $article->setArticleValue1($freeKm);
                } elseif (strpos(strtolower($remoteArticle->name), "reikilometerpaket:"))
                {
                    $freeKm = explode(" ", $remoteArticle->name);
                    $freeKm = $freeKm[1];

                    $article->setArticleType(Article::ARTICLE_TYPE_KM_PACKAGE);
                    $article->setArticleValue1($freeKm);
                } else {
                    $article->setArticleType(Article::ARTICLE_TYPE_ARTICLE);
                }

                if (!empty($remoteArticle->tag)) {
                    $article->setTags($remoteArticle->tag);
                }

                if ($remoteArticle->article_quantity_type == 0) {
                    $article->setQuantityType(Article::QUANTITY_MANUAL);
                }

                if ($remoteArticle->article_quantity_type == 1) {
                    $article->setQuantityType(Article::QUANTITY_STOCK);
                }

                if ($remoteArticle->article_quantity_type == 0) {
                    $article->setQuantity($remoteArticle->article_quantity);
                }

                if ($remoteArticle->article_quantity_type == 1) {
                    $article->setQuantity($remoteArticle->quantity);
                }

                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');

                $repository
                    ->translate($article, 'name', 'de', $remoteArticle->name)
                    ->translate($article, 'name', 'en', (!empty(json_decode($remoteArticle->name_translation)->en)) ? json_decode($remoteArticle->name_translation)->en : null)
                    ->translate($article, 'name', 'it', (!empty(json_decode($remoteArticle->name_translation)->it)) ? json_decode($remoteArticle->name_translation)->it : null)
                    ->translate($article, 'name', 'fr', (!empty(json_decode($remoteArticle->name_translation)->fr)) ? json_decode($remoteArticle->name_translation)->fr : null);

                $repository
                    ->translate($article, 'descriptionTeaser', 'de', $remoteArticle->description_short)
                    ->translate($article, 'descriptionTeaser', 'en', (!empty(json_decode($remoteArticle->description_short_translation)->en)) ? json_decode($remoteArticle->description_short_translation)->en : null)
                    ->translate($article, 'descriptionTeaser', 'it', (!empty(json_decode($remoteArticle->description_short_translation)->it)) ? json_decode($remoteArticle->description_short_translation)->it : null)
                    ->translate($article, 'descriptionTeaser', 'fr', (!empty(json_decode($remoteArticle->description_short_translation)->fr)) ? json_decode($remoteArticle->description_short_translation)->fr : null);

                $article->setClientId($client->getId());
                $article->setArticleId($remoteArticle->article_id);
                $article->setStatus(Article::STATUS_ACTIVE);

                switch ($remoteArticle->price_calculation_pricetype) {

                    case "0":
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_RATES_DAY);

                        // REMOVE EXISTING
                        /** @var Group $priceRate */
                        foreach ($article->getPriceRates() as $priceRate)
                        {
                            if ($priceRate->getName() == "Preis pro Tag Standard") {

                                $this->em->remove($priceRate);
                            }
                        }

                        $start = new \DateTime();
                        $start->setDate(date("Y"), 1, 1);

                        $end = new \DateTime();
                        $end->setDate(2030, 12, 31);

                        $priceGroup = new Group();
                        $priceGroup->setName("Preis pro Tag Standard");
                        $priceGroup->setClientId($client->getId());
                        $priceGroup->setValidFrom($start);
                        $priceGroup->setValidTo($end);
                        $priceGroup->setDefaultPriceRate(true);
                        $priceGroup->setEnabledMsOnlineBooking(true);
                        $this->em->persist($priceGroup);

                        $priceGroupEntry = new Entry();
                        $priceGroupEntry->setPriceRateGroup($priceGroup);
                        $priceGroupEntry->setUnit(24);
                        $priceGroupEntry->setUnitPrice($remoteArticle->price_per_day);
                        $priceGroupEntry->setUnitFrom(0);
                        $priceGroupEntry->setUnitTo(9999);

                        $this->em->persist($priceGroupEntry);

                        $article->addPriceRates($priceGroup);
                        break;

                    case "2":
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_RATES_DAY);
                        break;

                    case "3":
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_FIX);
                        break;

                    case "5":
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_RATES_DAY_FIX);
                        break;
                }

                ### SYNC STORAGE ###
                if ($article->getQuantityType() == 10) {

                    /** @var UpdatedRentsoft $syncedStorage */
                    $syncedStorage = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-storage-command', 'remoteId' => $remoteArticle->article_location_id]);

                    if ($syncedStorage) {
                        /** @var Storage $storage */
                        $storage = $this->em->getRepository(Storage::class)->find($syncedStorage->getLocaleId());

                        $article->setStorage($storage);

                        if ($storage->getLocation() && !is_null($storage->getLocation()))
                        {
                            $article->setLocation($storage->getLocation());
                        }
                    }
                }

                ### SYNC PRICE FIX ###
                $article->setPriceFix(null);

                if ($remoteArticle->price_flatrate != 0) {
                    $article->setPriceFix($remoteArticle->price_flatrate);
                }
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles (" . $x . " created, " . $y . " updated, " . $a . " removed)</>");

        return Command::SUCCESS;

    }

}
