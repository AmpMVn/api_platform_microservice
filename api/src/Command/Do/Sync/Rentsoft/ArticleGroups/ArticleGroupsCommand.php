<?php

namespace App\Command\Do\Sync\Rentsoft\ArticleGroups;

use App\Entity\ArticleGroup\Accessories;
use App\Entity\Article\Article;
use App\Entity\ArticleGroup\ArticleGroup;
use App\Entity\ArticleGroup\Attribute;
use App\Entity\ArticleGroup\Image;
use App\Entity\Price\Rate\Group;
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
    name: 'do:sync:rs:articlegroups',
    description: 'Do sync with old Rentsoft API',
)]
class ArticleGroupsCommand extends Command
{

    const STATUS_ACTIVE = 10;
    const PROCESS_STEP_ADD = "add";
    const PROCESS_STEP_UPDATE = "update";

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
        $output->writeln("\n<info>Rentsoft => Articlegroups</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $a = 0;
        $x = 0;
        $y = 0;
        $z = 0;
        $b = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid')) {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */
            $clientRentsoftId = $client->getRentsoftClientId();

            $postFields = ["sql" => "SELECT * FROM config_object_category WHERE deleted = 0 AND name != '' AND client_id = " . $clientRentsoftId];

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

            foreach ($response as $remoteObjectCategory) {

                /** @var UpdatedRentsoft $syncedObjectCategory */
                $syncedObjectCategory = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-article-category-command', 'remoteId' => $remoteObjectCategory->id]);

                if (!$syncedObjectCategory) {

                    $articleGroup = new ArticleGroup();
                    $this->em->persist($articleGroup);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(ArticleGroup::class);
                    $syncRs->setLocaleId($articleGroup->getId());
                    $syncRs->setRemoteId($remoteObjectCategory->id);
                    $syncRs->setRemoteAction('settings-article-category-command');
                    $this->em->persist($syncRs);

                    $currentProcessStep = static::PROCESS_STEP_ADD;
                    $x++;
                } else {
                    $articleGroup = $this->em->getRepository(ArticleGroup::class)->findOneBy(array('id' => $syncedObjectCategory->getLocaleId()));

                    $currentProcessStep = static::PROCESS_STEP_UPDATE;
                    $y++;
                }

                # MARK GROUP AS DELETED WHEN ITS ALREADY TRANSFERED AND DELETED BY RENTSOFT
                if ($remoteObjectCategory->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_UPDATE) {

                    $syncResult = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(array('remoteAction' => 'settings-article-category-command', 'remoteId' => $remoteObjectCategory->id));

                    $this->em->remove($syncResult);
                    $this->em->remove($articleGroup);
                    $this->em->flush();

                    continue;
                }

                # DON'T ADD GROUP WHEN ITS ALREADY DELETED
                if ($remoteObjectCategory->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_ADD) {

                    $this->em->remove($syncRs);
                    $this->em->remove($articleGroup);
                    $this->em->flush();

                    continue;
                }

                $articleGroup->setClientId($client->getId());
                $articleGroup->setName($remoteObjectCategory->name);
                $articleGroup->setEnableOnlineBooking(false);

                if ($remoteObjectCategory->status_online == "1") {
                    $articleGroup->setEnableOnlineBooking(true);
                }

                $this->em->flush();

                ###################################
                # SYNC ACCESSORIES
                ###################################
                foreach ($articleGroup->getAccessories() as $accessory)
                {
                    $this->em->remove($accessory);
                    $this->em->flush();
                }

                $postFields = ["sql" => "SELECT * FROM config_object_category_article WHERE config_object_category_id = " . $remoteObjectCategory->id];
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

                $response6 = null;
                $responseOrig6 = curl_exec($curl);
                $response6 = json_decode($responseOrig6);

                if (is_null($response6)) {
                    continue;
                }

                foreach ($response6 as $fetchExtra) {

                    $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'article-command', 'remoteId' => $fetchExtra->article_id]);

                    if (!$syncedArticle) {
                        $b++;
                        continue;
                    } else {
                        /** @var Article $article */
                        $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                    }

                    $accessorie = new Accessories();
                    $accessorie->setArticleGroup($articleGroup);
                    $accessorie->setArticleChild($article);
                    $accessorie->setMaxCount($fetchExtra->max_count);
                    $accessorie->setRequiredMsOnlineBooking(($fetchExtra->required == "0") ? false : true);
                    $accessorie->setEnabledMsOnlineBooking(($fetchExtra->status_online == "0") ? false : true);
                    $accessorie->setGroupName(null);

                    if (isset($priorityCounter[$fetchExtra->article_id])) {
                        $accessorie->setPriority($priorityCounter[$fetchExtra->article_id]);
                        $priorityCounter[$fetchExtra->article_id]++;
                    } else {
                        $accessorie->setPriority(0);
                        $priorityCounter[$fetchExtra->article_id] = 0;
                    }

                    $this->em->persist($accessorie);
                }

                $this->em->flush();

                ###################################
                # SYNC PRICE GROUPS
                ###################################
                foreach ($articleGroup->getPriceRates() as $priceRate)
                {
                    $articleGroup->getPriceRates()->removeElement($priceRate);
                    $this->em->flush();
                }

                $postFields = ["sql" => "SELECT * FROM config_object_category_price WHERE config_object_category_id = " . $remoteObjectCategory->id];
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

                $response5 = null;
                $responseOrig5 = curl_exec($curl);
                $response5 = json_decode($responseOrig5);

                if (is_null($response5)) {
                    continue;
                }

                foreach ($response5 as $fetchPriceGroup) {

                    $syncedPriceGroup = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-pricegroup-command', 'remoteId' => $fetchPriceGroup->config_price_group_id ]);

                    if (!$syncedPriceGroup) {
                        $a ++;
                        continue;
                    }

                    /** @var \App\Entity\Price\Rate\Group $priceRate */
                    $priceRate = $this->em->getRepository(\App\Entity\Price\Rate\Group::class)->find($syncedPriceGroup->getLocaleId());

                    if (!$priceRate)
                    {
                        continue;
                    }

                    $articleGroup->addPriceRates($priceRate);
                }

                $this->em->flush();

                ###################################
                # DELETE ALL IMAGES AND ADD NEW
                ###################################
                foreach ($articleGroup->getImages() as $existingImage) {
                    $this->em->remove($existingImage);
                    $this->em->flush();
                }

                $postFields = ["sql" => "SELECT * FROM config_object_category_image WHERE config_object_category_id = " . $remoteObjectCategory->id];

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

                $response2 = null;
                $responseOrig2 = curl_exec($curl);
                $response2 = json_decode($responseOrig2);

                if (is_null($response2)) {
                    continue;
                }

                foreach ($response2 as $fetchImage) {
                    $image = new Image();

                    if ($fetchImage->prio == 1) {
                        $image->setMainImage(true);
                    } else {
                        $image->setMainImage(false);
                    }

                    $image->setFilepath('https://media.rentsoft.de/_groupimages/' . $fetchImage->filename);
                    $image->setArticleGroup($articleGroup);
                    $image->setFilesize(1);
                    $this->em->persist($image);

                    $z++;
                }

                $this->em->flush();

                ###################################
                # DELETE ATTRIBUTES AND ADD
                ###################################
                foreach ($articleGroup->getAttributes() as $attribute) {
                    $this->em->remove($attribute);
                    $this->em->flush();
                }

                $postFields = ["sql" => "SELECT * FROM config_object_category_settings WHERE config_object_category_id = " . $remoteObjectCategory->id];

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

                $response3 = null;
                $responseOrig3 = curl_exec($curl);
                $response3 = json_decode($responseOrig3);

                if (is_null($response3) || sizeof($response3) == 0) {
                    continue;
                }

                $prioCounter = 1;
                $settings = $response3[0];

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Max. Personen");
                $attribute->setType("textfield");
                $attribute->setValue($settings->max_persons);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-users fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Sitzplätze mit Gurt");
                $attribute->setType("textfield");
                $attribute->setValue($settings->belted_seats);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-person-seat fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Max. Schlafplätze");
                $attribute->setType("textfield");
                $attribute->setValue($settings->sleeping_places);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-bed fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Länge");
                $attribute->setType("textfield");
                $attribute->setValue($settings->length." m");
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-arrow-right-long fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Breite");
                $attribute->setType("textfield");
                $attribute->setValue($settings->width." m");
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-arrow-right-arrow-left fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Höhe");
                $attribute->setType("textfield");
                $attribute->setValue($settings->height." m");
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-arrow-up-long fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Leergewicht");
                $attribute->setType("textfield");
                $attribute->setValue($settings->weight_empty." Kg");
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-weight-hanging fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Höhe");
                $attribute->setType("Zul. Gesamtgewicht"." Kg");
                $attribute->setValue($settings->weight_max);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-weight-hanging fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Max. Zuladung"." Kg");
                $attribute->setType("textfield");
                $attribute->setValue($settings->weight_add);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-weight-hanging fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("Motorisierung");
                $attribute->setType("textfield");
                $attribute->setValue($settings->engine);
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-engine fa-fw'></i>");
                $prioCounter++;

                $this->em->persist($attribute);
                $this->em->flush();

                $attribute = new Attribute();
                $attribute->setArticleGroup($articleGroup);
                $attribute->setName("KW");
                $attribute->setType("textfield");
                $attribute->setValue($settings->kw." Kw");
                $attribute->setPriority($prioCounter);
                $attribute->setIcon("<i class='fas fa-battery-bolt fa-fw'></i>");

                $this->em->persist($attribute);
                $this->em->flush();
            }
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articlegroups (" . $x . " created, " . $y . " updated, " . $z . " images, ".$a." skipped)</>");

        return Command::SUCCESS;

    }

}
