<?php

declare(strict_types=1);

namespace App\Repository\Settings;

use App\Entity\Settings\Category;
use App\Repository\AbstractTreeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractTreeRepository<Category>
 */
final class CategoryRepository extends AbstractTreeRepository
{
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct($manager, $manager->getClassMetadata(Category::class));
    }
}
