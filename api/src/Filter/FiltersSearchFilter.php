<?php
namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\Filter\Filter;
use Rentsoft\ApiGatewayConnectorBundle\Entity\OnlineBookingMicroservice\Filter\Group;
use Rentsoft\ApiGatewayConnectorBundle\Extension\ApiGatewayKeycloakHttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

class FiltersSearchFilter extends AbstractContextAwareFilter
{
    private $searchParameterName;

    private $apiGateway;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     * @param string $searchParameterName The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, ApiGatewayKeycloakHttpClient $apiGateway, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $searchParameterName = 'filters')
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->apiGateway = $apiGateway;
        $this->searchParameterName = $searchParameterName;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $value || $property !== $this->searchParameterName) {
            return;
        }
        $filterGroups = $value;
//        dd($filterGroups);
        foreach ($filterGroups as $key => $group) {
            if (empty($group)) continue;

            $this->addWhere($queryBuilder, $group, $queryNameGenerator->generateParameterName($property));
        }

//        dd($queryBuilder->getQuery());
    }

    private function addWhere(QueryBuilder $queryBuilder, $group, $parameterName)
    {
        $alias = $queryBuilder->getRootAliases()[0];

//        dd($group);

        $expOrx = $queryBuilder->expr()->orX();
        foreach ($this->getProperties() as $prop => $ignoored) {

            foreach ($group as $filter)
            {
                /** @var Filter $entityFilter */
//                $entityFilter = $this->managerRegistry->getRepository(Filter::class)->find($filter);
                $entityFilter = $this->apiGateway->getMsOnlineBooking()->getFilterById($filter);
//                dd($entityFilter->getGroup());
//                $entityFilter = $this->api
                if(!$entityFilter) continue;

                if($entityFilter->getGroup()->getValueType() == Group::VALUE_TYPE_ARRAY) {
                    $filterArray = explode(",", $entityFilter->getValue() ?? $entityFilter->getId());
//                    dd($filterArray);
                } else {
                    $filterArray = [];
                    $filterArray[] = $filter;
                }

                foreach ($filterArray as $f) {
                    $expOrx->add("JSONB_CONTAINS(" . $alias . "." . $prop . ", :" . $parameterName . "_" . $f . ") = true");
                    $queryBuilder->setParameter($parameterName . "_" . $f, "[{\"id\":" . $f . "}]");
                }

            }
        }

        $queryBuilder->andWhere($expOrx);

//        dd($queryBuilder);
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass): array
    {
        $props = $this->getProperties();
        if (null===$props) {
            throw new InvalidArgumentException('Properties must be specified');
        }
        return [
            $this->searchParameterName => [
                'property' => implode(', ', array_keys($props)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Selects entities where each search term is found somewhere in at least one of the specified properties',
                ]
            ]
        ];
    }
}
