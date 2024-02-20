<?php

namespace App\Repository\Sync;

use App\Entity\Sync\UpdatedRentsoft;
use App\Repository\AbstractRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UpdatedRentsoft|null find($id, $lockMode = null, $lockVersion = null)
 * @method UpdatedRentsoft|null findOneBy(array $criteria, array $orderBy = null)
 * @method UpdatedRentsoft[]    findAll()
 * @method UpdatedRentsoft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<UpdatedRentsoft>
 */
class UpdatedRentsoftRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpdatedRentsoft::class);
    }
}
