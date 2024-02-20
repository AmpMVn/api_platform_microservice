<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Article\Attribute;
use App\Entity\Article\Image;
use App\Entity\Article\Stock;
use App\Entity\Client\Client;
use App\Entity\Microservice\Article\OnlineBooking;
use App\Entity\Price\Rate\Group;
use App\Entity\Settings\Storage\Storage;
use App\Entity\Sync\UpdatedRentsoft;
use App\Extension\RsApiHttpClient;
use App\Repository\Client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\Sync\UpdatedMicroservice;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'do:sync:rs:objects:equipments',
    description: 'Do sync with old Rentsoft API',
)]
class EquipmentsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects, Attributes</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;

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

            foreach ($remoteObjects as $remoteObject) {

                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'remoteId' => $remoteObject->id ]);

                if (!$syncedObject) {
                    $z ++;
                    continue;
                }

                /** @var Article $article */
                $article = $this->em->getRepository(Article::class)->find($syncedObject->getLocaleId());

                foreach ($article->getAttributes() as $attribute) {
                    $this->em->remove($attribute);
                    $y ++;
                }

                $this->em->flush();

                $this->rsApi->getFreeFieldsByObjectId($remoteObject->id, $clientRentsoftId);
                $remoteFreeFields = $this->rsApi->getData()->body;

                $priorityCounter = 0;

                switch ($article->getArticleType()) {
                    case Article::ARTICLE_TYPE_CAR:

                        $this->rsApi->getObjectDetailsForAutovermietung($remoteObject->id, $clientRentsoftId);
                        $details = $this->rsApi->getData()->body;

                        if ($remoteObject->category_name != "") {
                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Kategorie");
                            $attribute->setValue($remoteObject->category_name);
                            $attribute->setIcon("<i class='fas fa-info fa-fw'></i>");
                            $attribute->setType("textfield");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Kategorie")->translate($attribute, 'name', 'en', "Category");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->ps != "" && $details->ps != "0") {
                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Leistung");
                            $attribute->setValue($details->ps . " PS");
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-engine fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Leistung")->translate($attribute, 'name', 'en', "Horsepower");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->gear_count != "" && $details->gear_count != "0") {
                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Anzahl Gänge");
                            $attribute->setValue($details->gear_count);
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-gears fa-fw'></i>");



                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->circuit != "" && $details->circuit != "0") {
                            if ($details->circuit == 1) {
                                $circuit = "Handschaltung";
                            }

                            if ($details->circuit == 2) {
                                $circuit = "Automatik";
                            }

                            if ($details->circuit == 3) {
                                $circuit = "Halbautomatik";
                            }

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Getriebe");
                            $attribute->setValue($circuit);
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-gears fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Getriebe")->translate($attribute, 'name', 'en', "Transmission");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->capacity != "" && $details->capacity != "0") {

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Hubraum");
                            $attribute->setValue($details->capacity);
                            $attribute->setIcon("<i class='fas fa-glass-half fa-fw'></i>");
                            $attribute->setType("textfield");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->count_zylinder != "" && $details->count_zylinder != "0") {

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Zylinder");
                            $attribute->setValue($details->count_zylinder);
                            $attribute->setIcon("<i class='fas fa-prescription fa-fw'></i>");
                            $attribute->setType("textfield");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->torque != "" && $details->torque != "0") {

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Drehmoment");
                            $attribute->setValue($details->torque);
                            $attribute->setType("textfield");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->power_unit != "" && $details->power_unit != "0") {
                            if ($details->power_unit == 1) {
                                $powerUnit = "Heckantrieb";
                            }

                            if ($details->power_unit == 2) {
                                $powerUnit = "Frontantrieb";
                            }

                            if ($details->power_unit == 3) {
                                $powerUnit = "Allradantrieb";
                            }

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Antrieb");
                            $attribute->setValue($powerUnit);
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-tire fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Antrieb")->translate($attribute, 'name', 'en', "Drive");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->drive_fuel != "" && $details->drive_fuel != "0") {
                            if ($details->drive_fuel == 1) {
                                $driveFuel = "Benzin";
                            }

                            if ($details->drive_fuel == 2) {
                                $driveFuel = "Diesel";
                            }

                            if ($details->drive_fuel == 3) {
                                $driveFuel = "Elektro";
                            }

                            if ($details->drive_fuel == 4) {
                                $driveFuel = "Gas";
                            }

                            if ($details->drive_fuel == 8) {
                                $driveFuel = "Hybrid";
                            }

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Kraftstoff");
                            $attribute->setValue($driveFuel);
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-gas-pump fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Krafstoff")->translate($attribute, 'name', 'en', "Fuel");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->speed != "" && $details->speed != "0") {
                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("V-Max");
                            $attribute->setValue($details->speed . " km/h");
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-gauge-max fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "V-Max")->translate($attribute, 'name', 'en', "Max speed");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->kmh100 != "" && $details->kmh100 != "0") {
                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("0-100 km/h");
                            $attribute->setValue($details->kmh100." sek");
                            $attribute->setIcon("<i class='fas fa-stopwatch fa-fw'></i>");
                            $attribute->setType("textfield");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        if ($details->color != "" && $details->color != "0") {
                            if ($details->color == 1) {
                                $color = "Schwarz";
                            }

                            if ($details->color == 2) {
                                $color = "Grau";
                            }

                            if ($details->color == 3) {
                                $color = "Beige";
                            }

                            if ($details->color == 4) {
                                $color = "Braun";
                            }

                            if ($details->color == 5) {
                                $color = "Rot";
                            }

                            if ($details->color == 6) {
                                $color = "Grün";
                            }

                            if ($details->color == 7) {
                                $color = "Blau";
                            }

                            if ($details->color == 8) {
                                $color = "Lila";
                            }

                            if ($details->color == 9) {
                                $color = "Gold";
                            }

                            if ($details->color == 10) {
                                $color = "Weiß";
                            }

                            if ($details->color == 11) {
                                $color = "Orange";
                            }

                            if ($details->color == 12) {
                                $color = "Silber";
                            }

                            if ($details->color == 13) {
                                $color = "Gelb";
                            }

                            $attribute = new Attribute();
                            $attribute->setArticle($article);
                            $attribute->setName("Farbe");
                            $attribute->setValue($color);
                            $attribute->setType("textfield");
                            $attribute->setIcon("<i class='fas fa-brush fa-fw'></i>");

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository->translate($attribute, 'name', 'de', "Farbe")->translate($attribute, 'name', 'en', "Color");

                            $attribute->setPriority($priorityCounter);
                            $priorityCounter ++;

                            $this->em->persist($attribute);
                            $x ++;
                        }

                        break;

                        case Article::ARTICLE_TYPE_CARAVAN:

                            if ($remoteObject->category_name != "") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Kategorie");
                                $attribute->setValue($remoteObject->category_name);
                                $attribute->setIcon("<i class='fas fa-info fa-fw'></i>");
                                $attribute->setType("textfield");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Kategorie")->translate($attribute, 'name', 'en', "Category");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            $this->rsApi->getObjectDetailsForCaravan($remoteObject->id, $clientRentsoftId);
                            $details = $this->rsApi->getData()->body;

                            if ($details->Color != "" && $details->Color != "0") {
                                if ($details->Color == 1) {
                                    $color = "Schwarz";
                                }

                                if ($details->Color == 2) {
                                    $color = "Grau";
                                }

                                if ($details->Color == 3) {
                                    $color = "Beige";
                                }

                                if ($details->Color == 4) {
                                    $color = "Braun";
                                }

                                if ($details->Color == 5) {
                                    $color = "Rot";
                                }

                                if ($details->Color == 6) {
                                    $color = "Grün";
                                }

                                if ($details->Color == 7) {
                                    $color = "Blau";
                                }

                                if ($details->Color == 8) {
                                    $color = "Lila";
                                }

                                if ($details->Color == 9) {
                                    $color = "Gold";
                                }

                                if ($details->Color == 10) {
                                    $color = "Weiß";
                                }

                                if ($details->Color == 11) {
                                    $color = "Orange";
                                }

                                if ($details->Color == 12) {
                                    $color = "Silber";
                                }

                                if ($details->Color == 13) {
                                    $color = "Gelb";
                                }

                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Farbe");
                                $attribute->setValue($color);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-brush fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Farbe")->translate($attribute, 'name', 'en', "Color");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->Circuit != "" && $details->Circuit != "0") {
                                if ($details->Circuit == 1) {
                                    $circuit = "Handschaltung";
                                }

                                if ($details->Circuit == 2) {
                                    $circuit = "Automatik";
                                }

                                if ($details->Circuit == 3) {
                                    $circuit = "Halbautomatik";
                                }

                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Getriebe");
                                $attribute->setValue($circuit);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-gears fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Getriebe")->translate($attribute, 'name', 'en', "Transmission");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->DriveFuel != "" && $details->DriveFuel != "0") {
                                if ($details->DriveFuel == 1) {
                                    $driveFuel = "Benzin";
                                }

                                if ($details->DriveFuel == 2) {
                                    $driveFuel = "Diesel";
                                }

                                if ($details->DriveFuel == 3) {
                                    $driveFuel = "Elektro";
                                }

                                if ($details->DriveFuel == 4) {
                                    $driveFuel = "Gas";
                                }

                                if ($details->DriveFuel == 8) {
                                    $driveFuel = "Hybrid";
                                }

                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Kraftstoff");
                                $attribute->setValue($driveFuel);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-gas-pump fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Kraftstoff")->translate($attribute, 'name', 'en', "Fuel");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->Ps != "" && $details->Ps != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Leistung");
                                $attribute->setValue($details->Ps . " PS");
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-engine fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Leistung")->translate($attribute, 'name', 'en', "Horsepower");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->SleepingRooms != "" && $details->SleepingRooms != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Schlafplätze");
                                $attribute->setValue($details->SleepingRooms);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-bed fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Schlafplätze")->translate($attribute, 'name', 'en', "Sleeping places");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->BeltedSeatCount != "" && $details->BeltedSeatCount != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Sitze mit Gurt");
                                $attribute->setValue($details->BeltedSeatCount);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-person-seat fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Sitze mit Gurt")->translate($attribute, 'name', 'en', "Belted seats");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->MaxPersons != "" && $details->MaxPersons != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Max. Personen");
                                $attribute->setValue($details->MaxPersons);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-users fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Max. Personen")->translate($attribute, 'name', 'en', "Max. Persons");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->HwlUnit == "0")
                            {
                                $unit = "m";
                            }

                            if ($details->HwlUnit == "1")
                            {
                                $unit = "mm";
                            }

                            if ($details->Length != "" && $details->Length != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Länge");
                                $attribute->setValue($details->Length." ".$unit);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-arrow-right-long fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Länge")->translate($attribute, 'name', 'en', "Length");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->Width != "" && $details->Width != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Breite");
                                $attribute->setValue($details->Width." ".$unit);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-arrow-right-arrow-left fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Breite")->translate($attribute, 'name', 'en', "Width");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->Height != "" && $details->Height != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Höhe");
                                $attribute->setValue($details->Height." ".$unit);
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-arrow-up-long fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Höhe")->translate($attribute, 'name', 'en', "Height");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            if ($details->WeightMax != "" && $details->WeightMax != "0") {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName("Zul. Gesamtgewicht");
                                $attribute->setValue($details->WeightMax." kg");
                                $attribute->setType("textfield");
                                $attribute->setIcon("<i class='fas fa-weight-hanging fa-fw'></i>");

                                $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                                $repository->translate($attribute, 'name', 'de', "Zul. Gesamtgewicht")->translate($attribute, 'name', 'en', "Permitted total weight");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }

                            break;

                    case Article::ARTICLE_TYPE_MACHINE:

                        $this->rsApi->getObjectDetailsForMachine($remoteObject->id, $clientRentsoftId);
                        $details = $this->rsApi->getData()->body;

                        if ($details->Description != "")
                        {
                            $article->setDescription($details->Description);

                            $repository = $this->em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            $repository
                                ->translate($article, 'description', 'de', $details->Description)
                                ->translate($article, 'description', 'en', ( !empty(json_decode($details->DescriptionTranslation)->en) ) ? json_decode($details->DescriptionTranslation)->en : null)
                                ->translate($article, 'description', 'it', ( !empty(json_decode($details->DescriptionTranslation)->it) ) ? json_decode($details->DescriptionTranslation)->it : null)
                                ->translate($article, 'description', 'fr', ( !empty(json_decode($details->DescriptionTranslation)->fr) ) ? json_decode($details->DescriptionTranslation)->fr : null);
                        }

                        foreach ($remoteFreeFields as $value)
                        {
                            if ($value->type == "textfield" && $value->storedValue != null && $value->storedValue != "")
                            {
                                $attribute = new Attribute();
                                $attribute->setArticle($article);
                                $attribute->setName($value->name);
                                $attribute->setValue($value->storedValue." ".$value->unitType);
                                $attribute->setType("textfield");

                                $attribute->setPriority($priorityCounter);
                                $priorityCounter ++;

                                $this->em->persist($attribute);
                                $x ++;
                            }
                        }

                        break;
                }
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Attributes (" . $x . " created, " . $y . " removed, " . $z . " skipped [no object found])</>");


        return Command::SUCCESS;

    }

}
