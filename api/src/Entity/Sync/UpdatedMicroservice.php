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
use App\Repository\Sync\UpdatedMicroserviceRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UpdatedMicroserviceRepository::class)
 * @Table(name="sync_microservice")
 */
class UpdatedMicroservice extends AbstractEntity
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
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
    private string $remoteAction;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    private string $remoteId;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
    private string $localeClass;

    /**
     * @var string
     * @ORM\Column (type="string", nullable=false)
     */
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
     * @return UpdatedMicroservice
     */
    public function setId(int $id) : UpdatedMicroservice
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
     * @return UpdatedMicroservice
     */
    public function setCreatedAt(DateTime $createdAt) : UpdatedMicroservice
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
     * @return UpdatedMicroservice
     */
    public function setRemoteAction(string $remoteAction) : UpdatedMicroservice
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
     * @return UpdatedMicroservice
     */
    public function setRemoteId(string $remoteId) : UpdatedMicroservice
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
     * @return UpdatedMicroservice
     */
    public function setLocaleClass(string $localeClass) : UpdatedMicroservice
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
     * @return UpdatedMicroservice
     */
    public function setLocaleId(string $localeId) : UpdatedMicroservice
    {
        $this->localeId = $localeId;

        return $this;
    }


}
