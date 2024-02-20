<?php

namespace App\Command\Do\Sync\Rentsoft\Settings;

use App\Entity\Article\Article;
use App\Entity\Price\Deal\Deal;
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
    name: 'do:sync:rs:settings:pricedeals',
    description: 'Do sync with old Rentsoft API',
)]
class PricedealsCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n<info>Rentsoft => Settings, Pricedeals</>");
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

            $postFields = [
                "sql" => "SELECT * FROM config_price_deals WHERE title != '' AND client_id = " . $clientRentsoftId
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

                $syncedPriceDeal = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'settings-pricedeals', 'remoteId' => $fetch->id]);

                if (!$syncedPriceDeal) {

                    $deal = new Deal();
                    $this->em->persist($deal);

                    $syncRs = new UpdatedRentsoft();
                    $syncRs->setLocaleClass(Deal::class);
                    $syncRs->setLocaleId($deal->getId());
                    $syncRs->setRemoteId($fetch->id);
                    $syncRs->setRemoteAction('settings-pricedeals');
                    $this->em->persist($syncRs);

                    $x++;
                } else {
                    $deal = $this->em->getRepository(Deal::class)->find($syncedPriceDeal->getLocaleId());
                    $y++;
                }

                $deal->setClientId($client->getId());
                $deal->setName($fetch->title);
                $deal->setPrice($fetch->price);

                switch ($fetch->type) {
                    case 0:
                        $deal->setDealBase(Deal::DEAL_BASE_DAY);

                        if ($fetch->specification == 0) {
                            $deal->setDealSpecification(Deal::DEAL_SSPECIFICATION_TIME);
                        }

                        if ($fetch->specification == 1) {
                            $deal->setDealSpecification(Deal::DEAL_SSPECIFICATION_LENGTH);
                        }

                        break;

                    case 1:
                        $deal->setDealBase(Deal::DEAL_BASE_HOUR);

                        if ($fetch->specification == 0) {
                            $deal->setDealSpecification(Deal::DEAL_SSPECIFICATION_TIME);

                            $deal->setSpec10Start($fetch->deal_valid_from);
                            $deal->setSpec10ValidDays($fetch->deal_valid_for);
                            $deal->setSpec10MaxHours($fetch->deal_span);
                        }

                        if ($fetch->specification == 1) {
                            $deal->setDealSpecification(Deal::DEAL_SSPECIFICATION_LENGTH);

                            $deal->setSpec20ValidDays($fetch->deal_valid_for);
                            $deal->setSpec20HourStart($fetch->deal_span_start);
                            $deal->setSpec20HourEnd($fetch->deal_span);
                        }

                        break;
                }

                $startT = $fetch->start_timestamp;
                $start = new \DateTime();
                $start->setTimestamp($startT);

                $endT = $fetch->end_timestamp;
                $end = new \DateTime();
                $end->setTimestamp($endT);

                $deal->setValidStart($start);
                $deal->setValidEnd($end);

                $deal->setEnabledMsOnlineBooking(false);

                if ($fetch->status_online == 1) {
                    $deal->setEnabledMsOnlineBooking(true);
                }

                $this->em->flush();
            }
        }

        $this->em->clear();
        $output->writeln("<info>Rentsoft => Settings, Pricedeals (" . $x . " created, " . $y . " updated, " . $z . " updated)");

        return Command::SUCCESS;

    }

}
