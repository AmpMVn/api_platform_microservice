<?php

namespace App\Repository\Price\Rate;

use App\Entity\Price\Rate\Entry;
use App\Entity\Price\Rate\Group;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Entry|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Entry|null findOneBy( array $criteria, array $orderBy = null )
 * @method Entry[]    findAll()
 * @method Entry[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 * @extends AbstractRepository<Entry>
 */
final class EntryRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entry::class);
    }

    public function findForArticleBetweenStartAndRentaEnd(int $articleId, \DateTime $middleOfTheDay, int $rentalDays) : array
    {
        $queryBuilder = $this->createQueryBuilder('price_rate_entry');

        $queryBuilder
            ->select('price_rate_entry.unitPrice AS price, price_rate_entry.unit AS unit, price_rate_entry.unitFree AS unitFree, price_rate_group.name AS group_name, price_rate_group.id AS groupId, price_rate_entry.id AS entryId, article_price_rate.id AS articleId')

            ->leftJoin(Group::class, 'price_rate_group', 'WITH', 'price_rate_group = price_rate_entry.priceRateGroup')
            ->leftJoin('price_rate_group.articles', 'article_price_rate', 'WITH', 'article_price_rate.id = (:articleId)')

            ->andWhere(':datetime BETWEEN price_rate_group.validFrom AND price_rate_group.validTo')
            ->setParameter(':datetime', $middleOfTheDay)

            ->andWhere('price_rate_entry.unitFrom < :rentalDays AND price_rate_entry.unitTo >= :rentalDays')
            ->setParameter(':rentalDays', $rentalDays)

            //   ->andWhere('price_rate_group.client = :client')
            //   ->setParameter(':client', 2)

            ->andWhere('price_rate_group.enabledMsOnlineBooking = :enabledMsOnlineBooking')
            ->setParameter(':enabledMsOnlineBooking', true)

            ->andWhere('price_rate_group.defaultPriceRate = :defaultPriceRate')
            ->setParameter(':defaultPriceRate', true)

            ->andWhere('article_price_rate.id = :articleId')
            ->setParameter(':articleId', $articleId)

            // ->andWhere('article_price_rate.id IN (:articleIds)')
            // ->setParameter(':articleIds', $articleIds)

            //     ->groupBy('article_price_rate.id, price_rate_entry.unitPrice, price_rate_entry.unit, price_rate_group.name, price_rate_group.id, price_rate_entry.id')
            ->orderBy('price_rate_entry.unitPrice', 'DESC');



        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function findForArticleGroupBetweenStartAndRentaEnd(int $articleGroupId, \DateTime $middleOfTheDay, int $rentalDays) : array
    {
        $queryBuilder = $this->createQueryBuilder('price_rate_entry');

        $queryBuilder
            ->select('price_rate_entry.unitPrice AS price, price_rate_entry.unit AS unit, price_rate_entry.unitFree AS unitFree, price_rate_group.name AS group_name, price_rate_group.id AS groupId, price_rate_entry.id AS entryId, article_group_price_rate.id AS articleId')

            ->leftJoin(Group::class, 'price_rate_group', 'WITH', 'price_rate_group = price_rate_entry.priceRateGroup')
            ->leftJoin('price_rate_group.articleGroups', 'article_group_price_rate', 'WITH', 'article_group_price_rate.id = (:articleGroupId)')

            ->andWhere(':datetime BETWEEN price_rate_group.validFrom AND price_rate_group.validTo')
            ->setParameter(':datetime', $middleOfTheDay)

            ->andWhere('price_rate_entry.unitFrom < :rentalDays AND price_rate_entry.unitTo >= :rentalDays')
            ->setParameter(':rentalDays', $rentalDays)

            //   ->andWhere('price_rate_group.client = :client')
            //   ->setParameter(':client', 2)

            ->andWhere('price_rate_group.enabledMsOnlineBooking = :enabledMsOnlineBooking')
            ->setParameter(':enabledMsOnlineBooking', true)

            ->andWhere('price_rate_group.defaultPriceRate = :defaultPriceRate')
            ->setParameter(':defaultPriceRate', true)

            ->andWhere('article_group_price_rate.id = :articleGroupId')
            ->setParameter(':articleGroupId', $articleGroupId)

            // ->andWhere('article_price_rate.id IN (:articleIds)')
            // ->setParameter(':articleIds', $articleIds)

            //     ->groupBy('article_price_rate.id, price_rate_entry.unitPrice, price_rate_entry.unit, price_rate_group.name, price_rate_group.id, price_rate_entry.id')
            ->orderBy('price_rate_entry.unitPrice', 'DESC');



        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

}
