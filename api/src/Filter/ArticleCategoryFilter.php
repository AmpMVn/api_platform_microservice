<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Settings\Category;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

class ArticleCategoryFilter extends AbstractContextAwareFilter
{

    private $searchParameterName;

    /** @var Category */
    private $category;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     *
     * @param string $searchParameterName The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $searchParameterName = 'category')
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->searchParameterName = $searchParameterName;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $value || $property !== $this->searchParameterName) {
            return;
        }
        $this->category = $this->managerRegistry->getRepository(Category::class)->find($value);

        $categorys = $value;

//        dd($value);

        $alias = $queryBuilder->getRootAliases()[0];
//dd(111);
        $queryBuilder->join(Category::class, 'cat','WITH', $alias. '.category = cat.id')
            ->andWhere('cat.lft >= :lft')
            ->andWhere('cat.rgt <= :rgt')
            ->andWhere('cat.root = :rootId');

        $queryBuilder
            ->setParameter('lft', $this->category->getLft())
            ->setParameter('rgt', $this->category->getRgt())
            ->setParameter('rootId', $this->category->getRoot()->getId());
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass) : array
    {
        $props = $this->getProperties();

        if (null === $props) {
            throw new InvalidArgumentException('Properties must be specified');
        }

        return [
            $this->searchParameterName => [
                'property' => implode(', ', array_keys($props)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Set Category Array',
                ],
            ],
        ];
    }

}
