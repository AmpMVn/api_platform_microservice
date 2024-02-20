<?php

namespace App\Repository\ArticleGroup;

use App\Entity\ArticleGroup\ArticleGroup;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ArticleGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleGroup[]    findAll()
 * @method ArticleGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<ArticleGroup>
 */
final class ArticleGroupRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleGroup::class);
    }
}
