<?php

namespace App\Repository\Sync;

use App\Entity\Sync\UpdatedMicroservice;
use App\Repository\AbstractRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UpdatedMicroservice|null find($id, $lockMode = null, $lockVersion = null)
 * @method UpdatedMicroservice|null findOneBy(array $criteria, array $orderBy = null)
 * @method UpdatedMicroservice[]    findAll()
 * @method UpdatedMicroservice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<UpdatedMicroservice>
 */
class UpdatedMicroserviceRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpdatedMicroservice::class);
    }
}
