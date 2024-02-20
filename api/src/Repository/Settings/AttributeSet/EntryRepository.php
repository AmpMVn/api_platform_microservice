<?php

declare(strict_types=1);

namespace App\Repository\Settings\AttributeSet;

use App\Entity\Settings\AttributeSet\Entry;
use App\Repository\AbstractTreeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Entry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entry[]    findAll()
 * @method Entry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Entry>
 */
final class EntryRepository extends AbstractTreeRepository
{
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct($manager, $manager->getClassMetadata(Entry::class));
    }
}
