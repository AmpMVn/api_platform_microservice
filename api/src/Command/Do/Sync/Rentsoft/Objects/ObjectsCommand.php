<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Settings\Category;
use App\Entity\Settings\Location\Location;
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
    name: 'do:sync:rs:objects',
    description: 'Do sync with old Rentsoft API',
)]
class ObjectsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;
        $a = 0;
        $b = 0;
        $c = 0;
        $counter_no_category = 0;

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

            foreach ($remoteObjects as $remoteObject) {

                if ($remoteObject->config_location_id >= 1) {

                    // Get location
                    $syncedLocation = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-location-command', 'remoteId' => $remoteObject->config_location_id]);

                    if (!$syncedLocation) {
                        $c++;
                        continue;
                    }

                    $location = $this->em->getRepository(Location::class)->find($syncedLocation->getLocaleId());

                    if (!$location) {
                        $c++;
                        continue;
                    }
                }

                $this->rsApi->getObjectDetailByObjectIdId($clientRentsoftId, $remoteObject->id);
                $remoteObjectDetail = $this->rsApi->getData()->body;

                if ($remoteObjectDetail == null) {
                    continue;
                }

                // Get category
                if ($remoteObject->config_car_category_id >= 1) {

                    $syncedCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-category-object', 'remoteId' => $remoteObject->config_car_category_id]);

                    if ($syncedCategory) {
                        $category = $this->em->getRepository(Category::class)->find($syncedCategory->getLocaleId());

                        if (!$category) {
                            continue;
                            $counter_no_category++;
                        }
                    }
                }

                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'object-command', 'remoteId' => $remoteObject->id]);

                if (!$syncedObject) {

                    $article = new Article();
                    $this->em->persist($article);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Article::class);
                    $syncRs->setLocaleId($article->getId());
                    $syncRs->setRemoteId($remoteObject->id);
                    $syncRs->setRemoteAction('object-command');
                    $this->em->persist($syncRs);

                    $x++;
                } else {
                    $article = $this->em->getRepository(Article::class)->find($syncedObject->getLocaleId());
                    $y++;
                }

                if ($remoteObject->config_location_id >= 1)
                {
                    $article->setLocation($location);
                } else {
                    $article->setLocation(null);
                }

                $article->setCategory($category);
                $article->setPriceDeposit((double)$remoteObjectDetail->data->default_caution);
                $article->setName($remoteObject->object);
                $article->setModel($remoteObjectDetail->data->object_2);
                $article->setManufacturer($remoteObjectDetail->data->object_1);
                $article->setModelDescription($remoteObjectDetail->data->object_3);

                switch ($client->getAttributes()['branch'][0]) {
                    case "carrental":
                        $article->setArticleType(Article::ARTICLE_TYPE_CAR);
                        break;

                    case "machinerental":
                    case "andere":
                        $article->setArticleType(Article::ARTICLE_TYPE_MACHINE);
                        break;

                    case "caravanrental":
                        $article->setArticleType(Article::ARTICLE_TYPE_CARAVAN);
                        break;
                }

                $article->setClientId($client->getId());
                $article->setArticleId($remoteObjectDetail->data->internal_id);
                $article->setStatus(Article::STATUS_ACTIVE);
                $article->setArticleCounterType(Article::ARTICLE_COUNTER_KM);
                $article->setArticleCounter($remoteObjectDetail->data->km);

                if ($article->getArticleType() == Article::ARTICLE_TYPE_CAR) {

                    $article->setDescription((isset($remoteObjectDetail->equipment->description) && !is_null($remoteObjectDetail->equipment->description)) ? $remoteObjectDetail->equipment->description : "");
                    $article->setDescriptionTeaser(null);

                    if (isset($remoteObjectDetail->equipment->description) && !is_null($remoteObjectDetail->equipment->description)) {
                        $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                        $repository
                            ->translate($article, 'description', 'de', $remoteObjectDetail->equipment->description)
                            ->translate($article, 'description', 'en', (!empty(json_decode($remoteObjectDetail->equipment->description_translation)->en)) ? json_decode($remoteObjectDetail->equipment->description_translation)->en : null)
                            ->translate($article, 'description', 'it', (!empty(json_decode($remoteObjectDetail->equipment->description_translation)->it)) ? json_decode($remoteObjectDetail->equipment->description_translation)->it : null)
                            ->translate($article, 'description', 'fr', (!empty(json_decode($remoteObjectDetail->equipment->description_translation)->fr)) ? json_decode($remoteObjectDetail->equipment->description_translation)->fr : null);
                    }
                }

                if ($article->getArticleType() == Article::ARTICLE_TYPE_CARAVAN) {
                    $article->setDescription($remoteObjectDetail->detail->description);
                    $article->setDescriptionTeaser($remoteObjectDetail->detail->description);
                }

                if (!empty($remoteObjectDetail->data->tag)) {
                    $article->setTags($remoteObjectDetail->data->tag);
                }

                $article->setQuantityType(Article::QUANTITY_MANUAL);
                $article->setQuantity(1);

                switch ($remoteObjectDetail->data->price_calculation_pricetype) {

                    case "0": // Pauschalpreise
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_FIX);
                        break;

                    case "1": // Tagestarife
                    case "2": // Preisgruppen (Tage)
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_RATES_DAY);
                        break;

                    case "3": // Preisgruppen (Stunden)
                        $article->setDefaultPriceCalculation(Article::DEFAULT_PRICE_CALCULATION_RATES_HOUR);
                        break;
                }

                $this->em->flush();
            }

            # ARCHIVED AND DELETED
            $this->rsApi->getObjectsByClientId($clientRentsoftId, "archived");
            $remoteObjects = $this->rsApi->getData()->body->results;

            if (!$remoteObjects) {
                continue;
            }

            foreach ($remoteObjects as $remoteObject) {
                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'object-command', 'remoteId' => $remoteObject->id]);

                if (!$syncedObject) {
                    continue;
                }

                $article = $this->em->getRepository(Article::class)->find($syncedObject->getLocaleId());

                if (!$article) {
                    continue;
                }

                $b++;

                $this->em->remove($article);
                $this->em->remove($syncedObject);
                $this->em->flush();
            }

        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects (" . $x . " created, " . $y . " updated, " . $z . " images added, " . $a . " images removed, " . $c . " skipped [no location], " . $counter_no_category . " no category, " . $b . " completely removed)</>");

        return Command::SUCCESS;

    }
}
