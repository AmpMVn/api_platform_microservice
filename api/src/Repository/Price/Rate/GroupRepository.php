<?php

namespace App\Repository\Price\Rate;

use App\Entity\Article\Article;
use App\Entity\Price\Rate\Entry;
use App\Entity\Price\Rate\Group;
use App\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Group|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Group|null findOneBy( array $criteria, array $orderBy = null )
 * @method Group[]    findAll()
 * @method Group[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 * @extends AbstractRepository<Group>
 */
final class GroupRepository extends AbstractRepository
{

    public $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct($registry, Group::class);
    }



}
