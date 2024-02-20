<?php

namespace App\Repository\Price\Flat;

use App\Entity\Price\Flat\Flat;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Flat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Flat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Flat[]    findAll()
 * @method Flat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Sell>
 */
final class FlatRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flat::class);
    }
}
