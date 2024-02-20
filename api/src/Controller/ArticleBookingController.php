<?php

namespace App\Controller;

use App\Entity\Article\Article;
use App\Entity\Article\Booking;
use App\Repository\Article\ArticleRepository;
use App\Repository\Article\BookingRepository;
use App\Repository\Price\Rate\EntryRepository;
use App\Repository\Price\Rate\GroupRepository;
use Doctrine\ORM\EntityManager;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleBookingController extends AbstractController
{
    #[Route( '/articles/{id}/bookings.json', name: 'article_bookings' )]
    public function index(Request $request, EntryRepository $priceRateRepository, int $id, ArticleRepository $articleRepository, BookingRepository $bookingRepository) : Response
    {
        $article = $articleRepository->find($id);
        $bookings = $bookingRepository->findBy([ 'article' => $article ]);

        $returnArray = [];
        /** @var Booking $entry */

        foreach ($bookings as $entry) {

            $data = array();
            $data['bookingStart'] = $entry->getBookingStart()->format("d.m.Y");
            $data['bookingEnd'] = $entry->getBookingEnd()->format("d.m.Y");

            $start = $entry->getBookingStart()->getTimestamp();
            $end = $entry->getBookingEnd()->getTimestamp();
            $days = array();

            while ($start <= $end)
            {
                $days[] = date("d.m.Y", $start);
                $start = strtotime("+1 day", $start);
            }

            $data['days'] = $days;
            $data['id'] = $entry->getId();

            $returnArray[] = $data;
        }

        $response = new Response(json_encode($returnArray));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
