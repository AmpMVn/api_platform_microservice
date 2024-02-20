<?php

namespace App\Repository\Price\Sell;

use App\Entity\Price\Sell\Sell;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sell|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sell|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sell[]    findAll()
 * @method Sell[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Sell>
 */
final class SellRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sell::class);
    }
}
