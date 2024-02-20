<?php

namespace App\Repository\Price\Deal;

use App\Entity\Price\Deal\Deal;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Deal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deal[]    findAll()
 * @method Deal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Deal>
 */
final class DealRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    public function findAllBetweenDate(int $articleId, \DateTime $date, bool $msOnlineBookingOnly): array
    {
        $queryBuilder = $this->createQueryBuilder('price_deal');

        $queryBuilder
            ->leftJoin('price_deal.articles', 'article_price_deal', 'WITH')
            ->andWhere('article_price_deal.id = :articleId')
            ->andWhere(':date BETWEEN price_deal.validStart AND price_deal.validEnd')
            ->setParameter(':articleId', $articleId)
            ->setParameter(':date', $date);

        if ($msOnlineBookingOnly) {
            $queryBuilder
                ->andWhere('price_deal.enabledMsOnlineBooking = :enabledMsOnlineBooking')
                ->setParameter(':enabledMsOnlineBooking', $msOnlineBookingOnly);
        }

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }
}
