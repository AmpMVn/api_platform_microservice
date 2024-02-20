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

class RentalDatesFilter extends AbstractContextAwareFilter
{
    private $rentalDates;
    private $em;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     *
     * @param string $rentalDates The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $rentalDates = 'rentalDates')
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->rentalDates = $rentalDates;
        $this->em = $em;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $value || $property !== $this->rentalDates) {
            return;
        }

        $rentalStart = (new \DateTime())->setTimestamp($value['rentalStart']);
//        $rentalStart = (new \DateTime('28.12.2021 00:00:00'));
        $rentalEnd = (new \DateTime())->setTimestamp($value['rentalEnd']);
//        $rentalEnd = (new \DateTime('29.12.2021 00:00:00'));

        $alias = $queryBuilder->getRootAliases()[0];
        $qb = $this->em->createQueryBuilder();

        $subQueryBuilder = $this->em->createQueryBuilder();
        $subQuery = $subQueryBuilder
            ->select('article.id')
            ->from(Booking::class, 'booking')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->between('booking.bookingStart', ':rentalStart', ':rentalEnd'),
                    $qb->expr()->between('booking.bookingEnd', ':rentalStart', ':rentalEnd'),
                    $qb->expr()->andX(
                        $qb->expr()->lte('booking.bookingStart', ':rentalStart'),
                        $qb->expr()->gte('booking.bookingEnd', ':rentalEnd')
                    )
                ),
            )
            ->having('COUNT(booking.id) >= article.quantity')
            ->innerJoin('booking.article', 'article')
            ->setParameter('rentalStart', $rentalStart)
            ->setParameter('rentalEnd', $rentalEnd)
            ->groupBy('article.id')
            ->getQuery()
        ;


        $queryBuilder
            ->andWhere($queryBuilder->expr()->notIn($alias . '.' . 'id',  $subQuery->getDQL()))
            ->setParameter('rentalStart', $rentalStart)
            ->setParameter('rentalEnd', $rentalEnd)
            ;
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass) : array
    {
        $props = $this->getProperties();

        if (null === $props) {
            throw new InvalidArgumentException('Properties must be specified');
        }

        return [
            $this->rentalDates => [
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
