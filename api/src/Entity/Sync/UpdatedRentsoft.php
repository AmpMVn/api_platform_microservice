<?php

namespace App\Entity\Sync;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Entity\Article\Stock;
use App\Entity\Client\Client;
use App\Entity\Location\Location;
use App\Filter\FiltersSearchFilter;
use App\Repository\Sync\UpdatedRentsoftRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UpdatedRentsoftRepository::class)
 * @Table(name="sync_rentsoft")
 */
#[ApiResource(
    normalizationContext: [ 'groups' => [ 'sync_rentsoft' ] ],
    shortName: 'Sync/Rentsoft',
    collectionOperations: [ 'get' ],
    itemOperations: [ 'get' ],
)]
#[ApiFilter( SearchFilter::class, properties: [ 'remoteAction' => 'exact', 'localeId' => 'exact'] )]
class UpdatedRentsoft extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["sync_rentsoft"])]
    private int $id;

    /**
     * @var DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    #[Groups(["sync_rentsoft"])]
    private string $remoteAction;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    #[Groups(["sync_rentsoft"])]
    private string $remoteId;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    #[Groups(["sync_rentsoft"])]
    private string $localeClass;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    #[Groups(["sync_rentsoft"])]
    private string $localeId;

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return UpdatedRentsoft
     */
    public function setId(int $id) : UpdatedRentsoft
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return UpdatedRentsoft
     */
    public function setCreatedAt(DateTime $createdAt) : UpdatedRentsoft
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteAction() : string
    {
        return $this->remoteAction;
    }

    /**
     * @param string $remoteAction
     *
     * @return UpdatedRentsoft
     */
    public function setRemoteAction(string $remoteAction) : UpdatedRentsoft
    {
        $this->remoteAction = $remoteAction;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteId() : string
    {
        return $this->remoteId;
    }

    /**
     * @param string $remoteId
     *
     * @return UpdatedRentsoft
     */
    public function setRemoteId(string $remoteId) : UpdatedRentsoft
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocaleClass() : string
    {
        return $this->localeClass;
    }

    /**
     * @param string $localeClass
     *
     * @return UpdatedRentsoft
     */
    public function setLocaleClass(string $localeClass) : UpdatedRentsoft
    {
        $this->localeClass = $localeClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocaleId() : string
    {
        return $this->localeId;
    }

    /**
     * @param string $localeId
     *
     * @return UpdatedRentsoft
     */
    public function setLocaleId(string $localeId) : UpdatedRentsoft
    {
        $this->localeId = $localeId;

        return $this;
    }



}
