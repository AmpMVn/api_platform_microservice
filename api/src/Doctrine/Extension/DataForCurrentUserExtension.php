<?php

declare( strict_types = 1 );

namespace App\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Article\Accessories;
use App\Entity\Article\Article;
use App\Entity\Article\Attribute;
use App\Entity\Article\Booking;
use App\Entity\Article\Image;
use App\Entity\Article\Stock;
use App\Entity\ArticleGroup\ArticleGroup;
use App\Entity\Price\Discount\Discount;
use App\Entity\Price\Rate\Entry;
use App\Entity\Price\Rate\Group;
use App\Entity\Settings\Category;
use App\Entity\Settings\Location\Location;
use App\Entity\Settings\Storage\Storage;
use App\Entity\Sync\UpdatedMicroservice;
use App\Entity\Sync\UpdatedRentsoft;
use Doctrine\ORM\QueryBuilder;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Security;

class DataForCurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    /**
     * @var Security
     */
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null) : void
    {
        $this->addWhereCollection($queryBuilder, $resourceClass, $operationName);
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param array<int, string>          $identifiers
     * @param string|null                 $operationName
     * @param array<int, string>          $context
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []) : void
    {
        $this->addWhereItem($queryBuilder, $resourceClass, $operationName);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     */
    private function addWhereCollection(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null) : void
    {
        /** @var OAuthUser $user */
        $user = $this->security->getUser();
        if ($user && !str_starts_with($user->getUserIdentifier(), '_service') && !str_starts_with($user->getUserIdentifier(), '_microservice')) {
            $rootAlias = $queryBuilder->getRootAliases()[0];

            switch ($resourceClass) {
                case Article::class:
                case Discount::class:
                case Group::class:
                case Category::class:
                case Location::class:
                case Storage::class:
                case ArticleGroup::class:
                    $sprintIfWhere = sprintf('%s.clientId = :current_user', $rootAlias);
                    $queryBuilder->andWhere($sprintIfWhere);
                    $queryBuilder->setParameter('current_user', $user->getUserIdentifier());
                    break;
                case UpdatedMicroservice::class:
                case UpdatedRentsoft::class:
                case \App\Entity\Settings\Location\Image::class:
                case \App\Entity\Settings\Storage\Image::class:
                    break;
                case Attribute::class:
                case Image::class:
                case Stock::class:
                case Booking::class:
                    $queryBuilder->innerJoin($rootAlias . '.article', 'a');
                    $queryBuilder->andWhere('a.clientId = :current_user');
                    $queryBuilder->setParameter('current_user', $user->getUserIdentifier());
                    break;
                case Entry::class:
                    $queryBuilder->innerJoin($rootAlias . '.priceRate', 'pr');
                    $queryBuilder->andWhere('pr.clientId = :current_user');
                    $queryBuilder->setParameter('current_user', $user->getUserIdentifier());
                    break;
                case Accessories::class:
                    $queryBuilder->innerJoin($rootAlias . '.articleParent', 'ap');
                    $queryBuilder->innerJoin($rootAlias . '.articleChildren', 'ac');
                    $queryBuilder->andWhere('ap.clientId = :current_user');
                    $queryBuilder->orWhere('ac.clientId = :current_user');
                    $queryBuilder->setParameter('current_user', $user->getUserIdentifier());
                    break;
                default:
                    throw new Exception("The " . $resourceClass . " class isn't allowed!");
            }
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     */
    private function addWhereItem(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null) : void
    {
        $this->addWhereCollection($queryBuilder, $resourceClass, $operationName);
    }

}
