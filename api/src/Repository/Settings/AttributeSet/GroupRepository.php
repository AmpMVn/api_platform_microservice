<?php

declare(strict_types=1);

namespace App\Repository\Settings\AttributeSet;

use App\Entity\Settings\AttributeSet\Group;
use App\Repository\AbstractTreeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Group|null find($id, $lockMode = null, $lockVersion = null)
 * @method Group|null findOneBy(array $criteria, array $orderBy = null)
 * @method Group[]    findAll()
 * @method Group[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Group>
 */
final class GroupRepository extends AbstractTreeRepository
{
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct($manager, $manager->getClassMetadata(Group::class));
    }
}
