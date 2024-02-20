<?php

namespace App\Repository\Article;

use App\Entity\Article\Accessories;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Accessories|null find($id, $lockMode = null, $lockVersion = null)
 * @method Accessories|null findOneBy(array $criteria, array $orderBy = null)
 * @method Accessories[]    findAll()
 * @method Accessories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Accessories>
 */
final class AccessoriesRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accessories::class);
    }
}
