<?php

namespace App\Command\Do\Sync\Rentsoft\Articles;

use App\Entity\Article\Article;
use App\Entity\Article\Booking;
use App\Entity\Client\Client;
use App\Entity\Sync\UpdatedRentsoft;
use App\Extension\RsApiHttpClient;
use App\Repository\Client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Entity\ClientMicroservice\Group\Group;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'do:sync:rs:articles:bookings',
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

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln("\n<info>Rentsoft => Articles, Bookings</>");
        $clients = $this->apiGateway->getMsClient()->getRentsoftClients();

        $x = 0;
        $y = 0;
        $z = 0;
        $counter = 0;

        foreach ($clients as $client) {

            if ($input->getArgument('client-uuid') && $client->getId() != $input->getArgument('client-uuid'))
            {
                continue;
            }

            /** @var Group $client */

            $clientRentsoftId = $client->getRentsoftClientId();

            $articles = $this->em->getRepository(Article::class)->findBy(array('clientId' => $client->getId(), 'articleType' => Article::ARTICLE_TYPE_ARTICLE));

            /** @var Article $article */
            foreach ($articles as $article) {

                ### GET RENTSOFT ARTICLE ID ###
                /** @var UpdatedRentsoft $syncedRs */
                $syncedRs = $this->em->getRepository(UpdatedRentsoft::class)->findOneBy([ 'remoteAction' => 'article-command', 'localeId' => $article->getId() ]);

                if (!$syncedRs) {
                    $z ++;
                    continue;
                }

                $rentsoftArticleId = $syncedRs->getRemoteId();

                $this->rsApi->getArticleBookings($rentsoftArticleId);
                $bookings = $this->rsApi->getData()->body;

                foreach ($article->getBookings() as $booking)
                {
                    $y ++;
                    $this->em->remove($booking);
                }

                if (!$bookings) {
                    continue;
                }

                foreach ($bookings as $calenderEntry) {

                    $start = \DateTime::createFromFormat('U', $calenderEntry->Start);
                    $end = \DateTime::createFromFormat('U', $calenderEntry->End);

                    $booking = new Booking();
                    $booking->setBookingStart($start);
                    $booking->setBookingEnd($end);
                    $booking->setArticle($article);
                    $booking->setOptionalData(
                        json_encode(
                            [
                                'customerName' => $calenderEntry->CustomerName,
                            ]
                        )
                    );

                    $this->em->persist($booking);
                    $x ++;
                }

                $counter++;
                if ($counter >= 100) {
                    $this->em->flush();
                    $counter = 0;
                }

            }
        }

        $this->em->clear();

        $output->writeln("<info>Rentsoft => Articles, Bookings (".$x." created, ".$y." removed, ".$z." skipped [no article found])</>");

        return Command::SUCCESS;

    }

}
