<?php

namespace App\Command\Do\Sync\Microservice\OnlineBooking;

use App\Entity\Article\Article;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\Filter\Filter;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\Filter\Group;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\OnlineBooking\OnlineBooking;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'do:sync:microservice:online-booking:filters',
    description: 'Sync Filters from ms-article',
)]
class FiltersCommand extends Command
{

    private $apiGateway;
    private $em;

    public function __construct(ApiGatewayKeycloakHttpClient $apiGateway, EntityManagerInterface $em)
    {
        $this->apiGateway = $apiGateway;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure() : void
    {

    }

    public function getOnlineBookingByIdFromArrayCollection(ArrayCollection $arrayCollection, int $id)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq("id", $id));
        $results = $arrayCollection->matching($criteria);

        return $results->first();
    }

    public function getFilterByOBIdAndValueFilter(ArrayCollection $arrayCollection, int $idOnlineBooking, $value)
    {
        $byObs = $arrayCollection->filter(function(Filter $filter) use ($idOnlineBooking) {
            return $filter->getGroup()->getOnlineBooking()->getId() === $idOnlineBooking;
        });

        $criteria = Criteria::create()->where(Criteria::expr()->eq("name", $value));
        $results = $byObs->matching($criteria);

        return $results->first();
    }

    public function getFiltersByOBIdAndValueTypeFilter(ArrayCollection $arrayCollection, int $idOnlineBooking, int $valueType)
    {
        $results = $arrayCollection->filter(function(Filter $filter) use ($idOnlineBooking, $valueType) {
            return $filter->getGroup()->getOnlineBooking()->getId() === $idOnlineBooking && $filter->getGroup()->getValueType() === $valueType;
        });

        return $results;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln("\n<info>MS Onlinebooking => Tagfilters</>");

        /** @var ArrayCollection<int, Article> $articleResults */
        $articleResults = $this->em->getRepository(Article::class)->findAll();

        /** @var ArrayCollection<int, OnlineBooking> $onlineBookings */
        $onlineBookingResults = $this->apiGateway->getMsOnlineBooking()->getOnlineBookings();

        /** @var ArrayCollection<int, Filter> $filter */
        $filterResults = $this->apiGateway->getMsOnlineBooking()->getFilters();

        $x = 0;
        $y = 0;

        foreach ($articleResults as $article){
            /** @var Article $article*/

            $articleTags = explode(',', $article->getTags());
            foreach ($articleTags as $articleTag) {
                foreach ($article->getMsOnlineBookings() as $ob) {
                    /** @var \App\Entity\Microservice\Article\OnlineBooking $ob */
                    //$onlineBooking = $this->getOnlineBookingByIdFromArrayCollection($onlineBookingResults, $ob->getMsOnlineBookingId());
//                    dd($onlineBooking);
                    if (is_numeric($articleTag)) {
                        $filters = $this->getFiltersByOBIdAndValueTypeFilter($filterResults, $ob->getMsOnlineBookingId(), Group::VALUE_TYPE_RANGE);
                        foreach ($filters as $remoteFilter) {
                            $filterValueArray = explode(":", $remoteFilter->getValue());
                            if ($articleTag >= $filterValueArray[0] && $articleTag <= $filterValueArray[1]) {
                                $article->addFilter([ 'id' => $remoteFilter->getId() ]);
                                $y ++;
                            }
                        }
                    } else {

                        echo ".";

                        $filter = $this->getFilterByOBIdAndValueFilter($filterResults, $ob->getMsOnlineBookingId(), $articleTag);
                        if(!$filter) continue;

                        $article->addFilter([ 'id' => $filter->getId() ]);
                        $y ++;
                    }
                }
            }
            $this->em->flush();
        }
        $this->em->clear();

        $output->writeln("<info>MS Onlinebooking => Tagfilters (" . $x . " created, " . $y . " updated)</>");

        return Command::SUCCESS;
    }

//    protected function executeaa(InputInterface $input, OutputInterface $output) : int
//    {
//        $progressBar = new ProgressBar($output);
//        $progressBar->setBarCharacter('<fg=green>⚬</>');
//        $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
//        $progressBar->setProgressCharacter("<fg=green>➤</>");
//        $progressBar->setMessage("Microservice => OnlineBooking, Tagfilters", 'status');
//        $progressBar->setFormat(
//            "<fg=white;bg=gray> %status:-45s%</>\n%current% [%bar%]\n %memory:20s%"
//        );
//        $progressBar->start();
//
//        $articleResults = $this->em->getRepository(Article::class)->findAll();
//
//        /** @var ArrayCollection $onlineBookings */
//        $onlineBookingResults = $this->apiGateway->getMsOnlineBooking()->getOnlineBookings();
//
//        $x = 0;
//        $y = 0;
//
//        /** @var Article $article */
//        foreach ($articleResults as $article) {
//
//            $onlineBookings = $article->getMsOnlineBookings();
//            $x ++;
//
//            /** @var \App\Entity\Microservice\Article\OnlineBooking $onlineBooking */
//            foreach ($onlineBookings as $onlineBooking) {
//
////                $remoteOnlineBooking = $this->apiGateway->getMsOnlineBooking()->getOnlineBookingById($onlineBooking->getMsOnlineBookingId());
//
//                /** @var OnlineBooking $remoteOnlineBooking */
//                $remoteOnlineBooking = $this->getOnlineBookingByIdFromArrayCollection($onlineBookingResults, $onlineBooking->getMsOnlineBookingId());
//
//                $remoteGroups = $remoteOnlineBooking->getFilterGroups();
//
//                $articleTags = explode(',', $article->getTags());
//                $article->setFilters([]);
//
//                foreach ($articleTags as $articleTag) {
//
//                    if (is_numeric($articleTag)) {
//
//                        foreach ($remoteGroups as $remoteGroup) {
//                            /** @var Group $remoteGroup */
//                            if ($remoteGroup->getValueType() == Group::VALUE_TYPE_RANGE) {
//                                foreach ($remoteGroup->getFilters() as $remoteFilter) {
//                                    $filterValueArray = explode(":", $remoteFilter->getValue());
//                                    if ($articleTag >= $filterValueArray[0] && $articleTag <= $filterValueArray[1]) {
//                                        $article->addFilter([ 'id' => $remoteFilter->getId() ]);
//                                        $y ++;
//
//                                        $progressBar->advance();
//                                    }
//                                }
//                            }
//                        }
//                    } else {
//
//                        /** @var Group $remoteGroup */
//                        foreach ($remoteGroups as $remoteGroup) {
//
//                            /** @var Filter $remoteFilter */
//                            foreach ($remoteGroup->getFilters() as $remoteFilter) {
//
//                                if ($remoteFilter->getName() == $articleTag) {
//                                    $article->addFilter([ 'id' => $remoteFilter->getId() ]);
//                                    $y ++;
//
//                                    $progressBar->advance();
//                                }
//                            }
//                        }
//                    }
//
//                    $progressBar->advance();
//                }
//            }
//
//            $this->em->flush();
//        }
//
//        $progressBar->setMessage("Microservice => OnlineBooking, Tagfilters (" . $x . " created, " . $y . " updated)", 'status');
//        $progressBar->finish();
//
//        return Command::SUCCESS;
//
//    }

}
