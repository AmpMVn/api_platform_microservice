<?php

namespace App\Command\Do\Sync\Rentsoft\Settings;

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
    name: 'do:sync:rs:settings:locations',
    description: 'Do sync with old Rentsoft API',
)]
class LocationsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Settings, Locations</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid')) {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */
            $clientRentsoftId = $client->getRentsoftClientId();

            $postFields = [
                "sql" => "SELECT * FROM config_location WHERE title != '' AND client_id = " . $clientRentsoftId
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

            foreach ($response as $remoteLocation) {

                /** @var UpdatedRentsoft $syncedLocation */
                $syncedLocation = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-location-command', 'remoteId' => $remoteLocation->id]);

                if (!$syncedLocation) {

                    $settingsLocation = new Location();
                    $this->em->persist($settingsLocation);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Location::class);
                    $syncRs->setLocaleId($settingsLocation->getId());
                    $syncRs->setRemoteId($remoteLocation->id);
                    $syncRs->setRemoteAction('settings-location-command');
                    $this->em->persist($syncRs);

                    $currentProcessStep = static::PROCESS_STEP_ADD;
                    $x++;
                } else {
                    $settingsLocation = $this->em->getRepository(Location::class)->findOneBy(array('id' => $syncedLocation->getLocaleId()));

                    $currentProcessStep = static::PROCESS_STEP_UPDATE;
                    $y++;
                }

                # MARK LOCATION AS DELETED WHEN ITS ALREADY TRANSFERED AND DELETED BY RENTSOFT
                if ($remoteLocation->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_UPDATE) {

                    $syncResult = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(array('remoteAction' => 'settings-location-command', 'remoteId' => $remoteLocation->id));

                    if ($syncResult) {
                        $this->em->remove($syncResult);
                    }

                    if ($settingsLocation) {
                        $this->em->remove($settingsLocation);
                    }

                    $this->em->flush();

                    continue;
                }

                # DON'T ADD LOCATION WHEN ITS ALREADY DELETED
                if ($remoteLocation->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_ADD) {

                    if ($syncRs) {
                        $this->em->remove($syncRs);
                    }

                    if ($settingsLocation) {
                        $this->em->remove($settingsLocation);
                    }

                    $this->em->flush();

                    continue;
                }

                $settingsLocation->setClientId($client->getId());
                $settingsLocation->setName($remoteLocation->name);
                $settingsLocation->setStreet($remoteLocation->street);
                $settingsLocation->setHouseNumber("");
                $settingsLocation->setZip($remoteLocation->zip);
                $settingsLocation->setCity($remoteLocation->city);
                $settingsLocation->setCountry($remoteLocation->country);

                if ($remoteLocation->lat > 0) {
                    $settingsLocation->setLat($remoteLocation->lat);
                }

                if ($remoteLocation->lng > 0) {
                    $settingsLocation->setLng($remoteLocation->lng);
                }

                // STATUS
                if ($remoteLocation->oi_status == "0") {
                    $settingsLocation->setStatus(Location::STATUS_ACTIVE);
                } else {
                    $settingsLocation->setStatus(Location::STATUS_INACTIVE);
                }

                // IMAGES
                if ($remoteLocation->deleted == 0) {
                    $this->rsApi->getLocationImagesByStorageIdAndClientId($remoteLocation->id, $clientRentsoftId);
                    $remoteLocationImages = $this->rsApi->getData()->body;

                    foreach ($settingsLocation->getImages() as $image) {
                        $this->em->remove($image);
                    }

                    if ($remoteLocationImages) {

                        $counter = 0;

                        foreach ($remoteLocationImages as $image) {

                            $locationImage = new \App\Entity\Settings\Location\Image();
                            $this->em->persist($locationImage);

                            if ($counter == 0) {
                                $locationImage->setMainImage(true);
                            } else {
                                $locationImage->setMainImage(false);
                            }

                            $locationImage->setFilepath('https://media.rentsoft.de/_rental_station_images/' . $image->filename);
                            $locationImage->setLocation($settingsLocation);
                            $locationImage->setFilesize($image->filesize);


                            $counter++;
                        }
                    }
                }

                $this->em->flush();
            }
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Settings, Locations (" . $x . " created, " . $y . " updated)</>");

        return Command::SUCCESS;

    }

}
