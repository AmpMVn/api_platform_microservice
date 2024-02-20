<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Article\Booking;
use App\Entity\Article\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

class ArticleLocationFilter extends AbstractContextAwareFilter
{

    private $searchParameterName;
    private $em;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     *
     * @param string $searchParameterName The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $searchParameterName = '')
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->searchParameterName = $searchParameterName;
        $this->em = $em;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $value || $property !== $this->searchParameterName) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->andWhere($alias . '.location = :locationParam')
            ->setParameter('locationParam', $value);
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass): array
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
                    'description' => 'Set location id',
                ],
            ],
        ];
    }

}
