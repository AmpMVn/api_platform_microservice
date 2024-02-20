<?php

namespace App\Command\Do\Sync\Rentsoft\Settings;

use App\Entity\Price\Rate\Entry;
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
    name: 'do:sync:rs:settings:pricegroups',
    description: 'Do sync with old Rentsoft API',
)]
class PriceratesCommand extends Command
{

    const STATUS_ACTIVE = 10;

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
        $output->writeln("\n<info>Rentsoft => Settings, Pricerates</>");
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

            $this->rsApi->getPriceGroupsByClientId($clientRentsoftId);
            $remotePricegroups = $this->rsApi->getData()->body;

            if (!$remotePricegroups) {
                continue;
            }

            foreach ($remotePricegroups as $remotePricegroup) {

                /** @var UpdatedRentsoft $syncedPriceGroup */
                $syncedPriceGroup = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'settings-pricegroup-command', 'remoteId' => $remotePricegroup->id ]);

                if (!$syncedPriceGroup) {

                    $settingsPriceGroup = new Group();
                    $this->em->persist($settingsPriceGroup);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Group::class);
                    $syncRs->setLocaleId($settingsPriceGroup->getId());
                    $syncRs->setRemoteId($remotePricegroup->id);
                    $syncRs->setRemoteAction('settings-pricegroup-command');
                    $this->em->persist($syncRs);
                    $x ++;

                } else {
                    $settingsPriceGroup = $this->em->getRepository(Group::class)->findOneBy([ 'id' => $syncedPriceGroup->getLocaleId() ]);
                    $y ++;
                }

                $settingsPriceGroup->setClientId($client->getId());
                $settingsPriceGroup->setName($remotePricegroup->name);
                $settingsPriceGroup->setStatus(self::STATUS_ACTIVE);

                if ($remotePricegroup->status_default == 1) {
                    $settingsPriceGroup->setDefaultPriceRate(true);
                } else {
                    $settingsPriceGroup->setDefaultPriceRate(false);
                }

                if ($remotePricegroup->status_online == 1) {
                    $settingsPriceGroup->setEnabledMsOnlineBooking(true);
                }

                if ($remotePricegroup->status_online == 0) {
                    $settingsPriceGroup->setEnabledMsOnlineBooking(false);
                }

                $validFrom = new \DateTime();
                $validFrom->setTimestamp($remotePricegroup->start);
                $settingsPriceGroup->setValidFrom($validFrom);

                $validTo = new \DateTime();
                $validTo->setTimestamp($remotePricegroup->end);
                $settingsPriceGroup->setValidTo($validTo);

                ### GET PRICE GROUP ENTRIES ###
                $this->rsApi->getEntriesForPricegroups($clientRentsoftId, $remotePricegroup->id);
                $remotePricegroupEntries = $this->rsApi->getData()->body;##

                foreach ($settingsPriceGroup->getPriceRateEntries() as $entry) {
                    $this->em->remove($entry);
                }

                if (isset($remotePricegroupEntries)) {

                    foreach ($remotePricegroupEntries as $remotePricegroupEntry) {

                        if ($remotePricegroupEntry->from_value == 0 && $remotePricegroupEntry->to_value == 0) {
                            continue;
                        }

                        $settingsPriceGroupEntry = new Entry();
                        $settingsPriceGroupEntry->setUnit((int) $remotePricegroupEntry->type);
                        $settingsPriceGroupEntry->setUnitFrom((int) $remotePricegroupEntry->from_value);
                        $settingsPriceGroupEntry->setUnitTo((int) $remotePricegroupEntry->to_value);

                        if ($remotePricegroupEntry->price_type == "0") {
                            $settingsPriceGroupEntry->setPriceType(Entry::PRICE_RATE_TYPE_PER_UNIT);
                            $settingsPriceGroupEntry->setUnitPrice((float) $remotePricegroupEntry->price_per_type);
                            $settingsPriceGroupEntry->setUnitFree((float) $remotePricegroupEntry->free_km_h_per_type);
                        }

                        if ($remotePricegroupEntry->price_type == "1") {
                            $settingsPriceGroupEntry->setPriceType(Entry::PRICE_RATE_TYPE_FIX);
                            $settingsPriceGroupEntry->setUnitName($remotePricegroupEntry->price_fixxed_name);
                            $settingsPriceGroupEntry->setUnitPrice((float) $remotePricegroupEntry->price_fixxed);
                            $settingsPriceGroupEntry->setUnitFree((float) $remotePricegroupEntry->free_km_h_per_type);
                        }

                        $settingsPriceGroupEntry->setPriceRateGroup($settingsPriceGroup);
                        $settingsPriceGroupEntry->setDiscountMon((float) $remotePricegroupEntry->monday);
                        $settingsPriceGroupEntry->setDiscountTue((float) $remotePricegroupEntry->tuesday);
                        $settingsPriceGroupEntry->setDiscountWed((float) $remotePricegroupEntry->wednesday);
                        $settingsPriceGroupEntry->setDiscountThu((float) $remotePricegroupEntry->thursday);
                        $settingsPriceGroupEntry->setDiscountFri((float) $remotePricegroupEntry->friday);
                        $settingsPriceGroupEntry->setDiscountSat((float) $remotePricegroupEntry->saturday);
                        $settingsPriceGroupEntry->setDiscountSun((float) $remotePricegroupEntry->sunday);
                        $settingsPriceGroupEntry->setFixPriceMon((float) $remotePricegroupEntry->monday_fix);
                        $settingsPriceGroupEntry->setFixPriceTue((float) $remotePricegroupEntry->tuesday_fix);
                        $settingsPriceGroupEntry->setFixPriceWed((float) $remotePricegroupEntry->wednesday_fix);
                        $settingsPriceGroupEntry->setFixPriceThu((float) $remotePricegroupEntry->thursday_fix);
                        $settingsPriceGroupEntry->setFixPriceFri((float) $remotePricegroupEntry->friday_fix);
                        $settingsPriceGroupEntry->setFixPriceSat((float) $remotePricegroupEntry->saturday_fix);
                        $settingsPriceGroupEntry->setFixPriceSun((float) $remotePricegroupEntry->sunday_fix);

                        $this->em->persist($settingsPriceGroupEntry);
                    }
                }
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Settings, Pricerates (" . $x . " created, " . $y . " updated)</>");

        return Command::SUCCESS;

    }

}
