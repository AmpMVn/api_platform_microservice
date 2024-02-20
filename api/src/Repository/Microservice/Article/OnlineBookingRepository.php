<?php

namespace App\Repository\Microservice\Article;

use App\Entity\Microservice\Article\OnlineBooking;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OnlineBooking|null find($id, $lockMode = null, $lockVersion = null)
 * @method OnlineBooking|null findOneBy(array $criteria, array $orderBy = null)
 * @method OnlineBooking[]    findAll()
 * @method OnlineBooking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<OnlineBooking>
 */
final class OnlineBookingRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnlineBooking::class);
    }
}
