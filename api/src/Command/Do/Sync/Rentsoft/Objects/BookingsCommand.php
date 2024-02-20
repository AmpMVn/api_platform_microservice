<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Article\Booking;
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
    name: 'do:sync:rs:objects:bookings',
    description: 'Do sync with old Rentsoft API',
)]
class BookingsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects, Bookings</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;
        $counter = 0;
        $removedArray = [];

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var \Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group $client */

            $clientRentsoftId = $client->getRentsoftClientId();

            $postFields = [
                "sql" => "SELECT * FROM calendar WHERE client_id = " . $clientRentsoftId . " AND object_id >= 1 AND (start >= " . time() . " OR end >= " . time() . ")",
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

            foreach ($response as $remoteEntry) {

                $syncedObject = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy(['remoteAction' => 'object-command', 'remoteId' => $remoteEntry->object_id]);

                if (!$syncedObject) {
                    $z++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedObject->getLocaleId());

                    if (!$article) {
                        $z++;
                        continue;
                    }
                }

                # REMOVE EXISTING ENTRIES FOR CLEAN UP
                if (!in_array($remoteEntry->object_id, $removedArray)) {
                    foreach ($article->getBookings() as $booking) {
                        $this->em->remove($booking);
                        $y++;
                    }

                    array_push($removedArray, $remoteEntry->object_id);
                }

                $start = \DateTime::createFromFormat('U', $remoteEntry->start);
                $end = \DateTime::createFromFormat('U', $remoteEntry->end);

                $booking = new Booking();
                $booking->setBookingStart($start);
                $booking->setBookingEnd($end);
                $booking->setArticle($article);
                $booking->setOptionalData(
                    json_encode(
                        [
                            'customerName' => $remoteEntry->customer_name,
                        ]
                    )
                );

                $this->em->persist($booking);
                $x++;

                $counter++;

                if ($counter >= 100) {
                    $this->em->flush();
                    $counter = 0;
                }
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, Bookings (" . $x . " created, " . $y . " removed, " . $z . " skipped [no article found])</>");

        return Command::SUCCESS;

    }

}
