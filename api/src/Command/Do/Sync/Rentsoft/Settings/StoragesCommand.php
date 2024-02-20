<?php

namespace App\Command\Do\Sync\Rentsoft\Settings;

use App\Entity\Settings\Location\Location;
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
    name: 'do:sync:rs:settings:storages',
    description: 'Do sync with old Rentsoft API',
)]
class StoragesCommand extends Command
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

    protected function configure() : void
    {
        $this
            ->addArgument('client-uuid', InputArgument::OPTIONAL, 'Provide client uuid for single client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln("\n<info>Rentsoft => Settings, Storages</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */
            $clientRentsoftId = $client->getRentsoftClientId();

            $postFields = [
                "sql" => "SELECT * FROM article_location WHERE name != '' AND client_id = " . $clientRentsoftId
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

            foreach ($response as $remoteStorage) {

                /** @var UpdatedRentsoft $syncedLocation */
                $syncedLocation = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-storage-command', 'remoteId' => $remoteStorage->id]);

                if (!$syncedLocation) {

                    $settingsStorage = new Storage();
                    $this->em->persist($settingsStorage);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Storage::class);
                    $syncRs->setLocaleId($settingsStorage->getId());
                    $syncRs->setRemoteId($remoteStorage->id);
                    $syncRs->setRemoteAction('settings-storage-command');
                    $this->em->persist($syncRs);

                    $currentProcessStep = static::PROCESS_STEP_ADD;
                    $x++;
                } else {
                    $settingsStorage = $this->em->getRepository(Storage::class)->findOneBy(array('id' => $syncedLocation->getLocaleId()));

                    $currentProcessStep = static::PROCESS_STEP_UPDATE;
                    $y++;
                }

                # MARK LOCATION AS DELETED WHEN ITS ALREADY TRANSFERED AND DELETED BY RENTSOFT
                if ($remoteStorage->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_UPDATE) {

                    $syncResult = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(array('remoteAction' => 'settings-location-command', 'remoteId' => $remoteStorage->id));

                    if ($syncResult)
                    {
                        $this->em->remove($syncResult);
                    }

                    if ($settingsStorage)
                    {
                        $this->em->remove($settingsStorage);
                    }

                    $this->em->flush();

                    continue;
                }

                # DON'T ADD LOCATION WHEN ITS ALREADY DELETED
                if ($remoteStorage->deleted == 1 && $currentProcessStep == static::PROCESS_STEP_ADD) {

                    if ($syncRs)
                    {
                        $this->em->remove($syncRs);
                    }

                    if ($settingsStorage)
                    {
                        $this->em->remove($settingsStorage);
                    }

                    $this->em->flush();

                    continue;
                }

                if ($remoteStorage->config_location_id >= 1) {
                    /** @var UpdatedRentsoft $syncedLocation */
                    $syncedLocation = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-location-command', 'remoteId' => $remoteStorage->config_location_id ]);
                    $locationResult = $this->em->getRepository(Location::class)->find($syncedLocation->getLocaleId());

                    $settingsStorage->setLocation($locationResult);
                } else {
                    $settingsStorage->setLocation(null);
                }

                // Get images
                $this->rsApi->getStorageImagesByStorageIdAndClientId($remoteStorage->id, $clientRentsoftId);
                $remoteStorageImages = $this->rsApi->getData()->body;

                foreach ($settingsStorage->getImages() as $image) {
                    $this->em->remove($image);
                }

                if ($remoteStorageImages) {

                    $counter = 0;

                    foreach ($remoteStorageImages as $image) {

                        $storageImage = new \App\Entity\Settings\Storage\Image();

                        if ($counter == 0) {
                            $storageImage->setMainImage(true);
                        } else {
                            $storageImage->setMainImage(false);
                        }

                        $storageImage->setFilepath('https://media.rentsoft.de/_article_location_images/' . $image->filename);
                        $storageImage->setStorage($settingsStorage);
                        $storageImage->setFilesize($image->filesize);
                        $this->em->persist($storageImage);

                        $counter ++;
                    }
                }

                $settingsStorage->setClientId($client->getId());
                $settingsStorage->setName($remoteStorage->name);
                $settingsStorage->setStreet($remoteStorage->street);
                $settingsStorage->setHouseNumber("");
                $settingsStorage->setZip($remoteStorage->zip);
                $settingsStorage->setCity($remoteStorage->city);
                $settingsStorage->setCountry($remoteStorage->country);
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Settings, Storages (".$x." created, ".$y." updated)</>");


        return Command::SUCCESS;

    }

}
