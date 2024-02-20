<?php

namespace App\Command\Do\Sync\Rentsoft\Settings;

use App\Entity\Article\Article;
use App\Entity\Price\Deal\Deal;
use App\Entity\Price\Discount\Discount;
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
    name: 'do:sync:rs:settings:pricediscounts',
    description: 'Do sync with old Rentsoft API',
)]
class PricediscountsCommand extends Command
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

    protected function configure(): void
    {
        $this
            ->addArgument('client-uuid', InputArgument::OPTIONAL, 'Provide client uuid for single client');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n<info>Rentsoft => Settings, Pricediscounts</>");
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

            $postFields = [
                "sql" => "SELECT * FROM config_price_discount WHERE title != '' AND client_id = " . $clientRentsoftId
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

            foreach ($response as $fetch) {

                // Buchungsrabatt
                if ($fetch->discount_type == 3) {
                    /** @var UpdatedRentsoft $syncedPricediscount */
                    $syncedPricediscount = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-pricediscount', 'remoteId' => $fetch->id]);

                    if (!$syncedPricediscount) {

                        $discount = new Discount();
                        $this->em->persist($discount);

                        $syncRs = new UpdatedRentsoft();
                        $syncRs->setLocaleClass(Discount::class);
                        $syncRs->setLocaleId($discount->getId());
                        $syncRs->setRemoteId($fetch->id);
                        $syncRs->setRemoteAction('settings-pricediscount');
                        $this->em->persist($syncRs);

                        $x++;
                    } else {
                        $discount = $this->em->getRepository(Discount::class)->find($syncedPricediscount->getLocaleId());
                        $y++;
                    }

                    $discount->setClientId($client->getId());
                    $discount->setName($fetch->title);

                    $discount->setType(999);

                    // EUR
                    if ($fetch->discount_type_booking_value_unit == 1) {
                        $discount->setType(Discount::DISCOUNT_TYPE_CASH);
                    }

                    // Percent
                    if ($fetch->discount_type_booking_value_unit == 2) {
                        $discount->setType(Discount::DISCOUNT_TYPE_PERCENT);
                    }

                    $discount->setValue($fetch->discount_type_booking_value);
                }

                $this->em->flush();
            }
        }

        $this->em->clear();
        $output->writeln("<info>Rentsoft => Settings, Pricediscounts (" . $x . " created, " . $y . " updated, " . $z . " updated)");

        return Command::SUCCESS;

    }

}
