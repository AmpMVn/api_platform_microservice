<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\MappingException;

/**
 * @template TEntityClass of object
 * @extends ServiceEntityRepository<TEntityClass>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
//    /**
//     * Fetches all records like $key => $value pairs
//     *
//     * @param string|null $key
//     * @param string $value
//     * @param array<string, string|int|array> $criteria
//     * @param array<string, string> $orderBy
//     * @return array<int, TEntityClass>
//     * @throws MappingException
//     */
//    public function findPairs(?string $key, string $value, array $criteria = [], array $orderBy = []): array
//    {
//        if ($key === null) {
//            $key = $this->getClassMetadata()->getSingleIdentifierFieldName();
//        }
//
//        $qb = $this->createQueryBuilder('e')
//            ->select(['e.' . $value, 'e.' . $key])
//            ->resetDQLPart('from')
//            ->from($this->getEntityName(), 'e', 'e.' . $key);
//
//        foreach ($criteria as $kKey => $vValue) {
//            if (is_array($vValue)) {
//                $arrValues = array_values($vValue);
//                $sprintf = sprintf('e.%s IN(:%s)', $key, $key);
//                $qb->andWhere($sprintf)->setParameter($key, $arrValues);
//            } else {
//                $sprintf = sprintf('e.%s = :%s', $key, $key);
//                $qb->andWhere($sprintf)->setParameter($key, $vValue);
//            }
//        }
//
//        foreach ($orderBy as $column => $order) {
//            $qb->addOrderBy($column, $order);
//        }
//
//        return array_map(function ($row) {
//            return reset($row);
//        }, $qb->getQuery()->getArrayResult());
//    }
}
