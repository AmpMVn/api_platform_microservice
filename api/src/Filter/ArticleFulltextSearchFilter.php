<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Article\Article;
use App\Entity\Article\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints\Date;

class ArticleFulltextSearchFilter extends AbstractContextAwareFilter
{
    private $searchQuery;
    private $em;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     *
     * @param string $searchQuery The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $searchQuery = 'searchQuery')
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->searchQuery = $searchQuery;
        $this->em = $em;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {

        if (null === $value || $property !== $this->searchQuery) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $value = strtolower($value);

        $queryBuilder->andWhere('(LOWER('.$alias.'.description) LIKE :searchParam OR LOWER('.$alias.'.descriptionTeaser) LIKE :searchParam OR LOWER('.$alias.'.name) LIKE :searchParam OR LOWER('.$alias.'.model) LIKE :searchParam OR LOWER('.$alias.'.manufacturer) LIKE :searchParam OR LOWER('.$alias.'.modelDescription) LIKE :searchParam)')
            ->setParameter('searchParam', "%".$value."%");
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass) : array
    {
        $props = $this->getProperties();

        if (null === $props) {
            throw new InvalidArgumentException('Properties must be specified');
        }

        return [
            $this->searchQuery => [
                'property' => implode(', ', array_keys($props)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Set OnlineBooking ID',
                ],
            ],
        ];
    }

}
