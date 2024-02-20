<?php

namespace App\Command\Do\Sync\Rentsoft\Objects;

use App\Entity\Article\Article;
use App\Entity\Microservice\Article\OnlineBooking;
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
    name: 'do:sync:rs:objects:online-bookings',
    description: 'Do sync with old Rentsoft API',
)]
class OnlineBookingsCommand extends Command
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
        $output->writeln("\n<info>Rentsoft => Objects, OnlineBookings</>");
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

            # Build string of article ids
            $articleIdString = "";
            foreach ($remoteObjects as $remoteArticle) {
                $articleIdString .= $remoteArticle->id . ",\n";
            }

            $articleIdString = substr($articleIdString, 0, ( strlen($articleIdString) - 2 ));

            $postFields = [
                "sql" => "SELECT
                        object_export.*
                    FROM object_export
                    WHERE object_export.object_id IN (" . $articleIdString . ")",
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

            $removedArray = [];
            foreach ($response as $fetch) {

                $syncedArticle = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'object-command', 'remoteId' => $fetch->object_id ]);

                if (!$syncedArticle) {
                    $z ++;
                    continue;
                } else {
                    /** @var Article $article */
                    $article = $this->em->getRepository(Article::class)->find($syncedArticle->getLocaleId());
                }

                if (!in_array($fetch->object_id, $removedArray)) {

                    foreach ($article->getMsOnlineBookings() as $entry) {
                        $this->em->remove($entry);
                        $y ++;
                    }

                    array_push($removedArray, $fetch->object_id);
                }

                $msOnlineBookingUpdated = $this->apiGateway->getMsOnlineBooking()->getUpdatedRentsofts([ 'remoteAction' => 'online-booking', 'remoteId' => $fetch->config_hp_integration_id ])[0];

                if ($msOnlineBookingUpdated) {

                    $msArticleOnlineBooking = new OnlineBooking();
                    $msArticleOnlineBooking->setMsOnlineBookingId($msOnlineBookingUpdated->getLocaleId());
                    $msArticleOnlineBooking->setArticle($article);

                    $this->em->persist($msArticleOnlineBooking);
                    $x ++;
                }
            }

            $this->em->flush();
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Objects, OnlineBookings (".$x." created, ".$y." removed, ".$z." skipped [no object found])</>");

        return Command::SUCCESS;

    }

}
