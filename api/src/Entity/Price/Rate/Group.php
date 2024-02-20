<?php

namespace App\Entity\Price\Rate;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Entity\ArticleGroup\ArticleGroup;
use App\Repository\Price\Rate\GroupRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Price\Rate\GroupRepository::class)
 * @Table(name="price_rate_group")
 */

#[ApiResource(

)]

class Group extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article", "price_rate"])]
    private int $id;

    /**
     * @ORM\Column(type="uuid")
     */
    private string $clientId;

    /**
     * @var DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @var DateTime|null
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $updatedAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups(["article", "price_rate"])]
    private string $name;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    #[Groups(["article", "price_rate"])]
    private DateTime $validFrom;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    #[Groups(["article", "price_rate"])]
    private DateTime $validTo;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    #[Groups(["article", "price_rate"])]
    private int $status = 0;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    #[Groups(["article", "price_rate"])]
    private bool $defaultPriceRate = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default", false})
     */
    #[Groups(["article", "price_rate"])]
    private bool $enabledMsOnlineBooking = false;

    /**
     * @var Collection<int,Entry>
     * @ORM\OneToMany(targetEntity="App\Entity\Price\Rate\Entry", mappedBy="priceRateGroup", cascade={"persist", "remove"})
     */
    #[Groups(["article", "price_rate"])]
    private Collection $priceRateEntries;

    /**
     * @var Collection<int,Article>
     * @ORM\ManyToMany(targetEntity="App\Entity\Article\Article", mappedBy="priceRates")
     */
    private Collection $articles;

    /**
     * @var Collection<int,ArticleGroup>
     * @ORM\ManyToMany(targetEntity="App\Entity\ArticleGroup\ArticleGroup", mappedBy="priceRates")
     */
    private Collection $articleGroups;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->articleGroups = new ArrayCollection();
        $this->priceRateEntries = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Group
     */
    public function setId(int $id): Group
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return Group
     */
    public function setCreatedAt(DateTime $createdAt): Group
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     *
     * @return Group
     */
    public function setUpdatedAt(?DateTime $updatedAt): Group
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Group
     */
    public function setName(string $name): Group
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getValidFrom(): DateTime
    {
        return $this->validFrom;
    }

    /**
     * @param DateTime $validFrom
     *
     * @return Group
     */
    public function setValidFrom(DateTime $validFrom): Group
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getValidTo(): DateTime
    {
        return $this->validTo;
    }

    /**
     * @param DateTime $validTo
     *
     * @return Group
     */
    public function setValidTo(DateTime $validTo): Group
    {
        $this->validTo = $validTo;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return Group
     */
    public function setStatus(int $status): Group
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultPriceRate(): bool
    {
        return $this->defaultPriceRate;
    }

    /**
     * @param bool $defaultPriceRate
     *
     * @return Group
     */
    public function setDefaultPriceRate(bool $defaultPriceRate): Group
    {
        $this->defaultPriceRate = $defaultPriceRate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledMsOnlineBooking(): bool
    {
        return $this->enabledMsOnlineBooking;
    }

    /**
     * @param bool $enabledMsOnlineBooking
     *
     * @return Group
     */
    public function setEnabledMsOnlineBooking(bool $enabledMsOnlineBooking): Group
    {
        $this->enabledMsOnlineBooking = $enabledMsOnlineBooking;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPriceRateEntries(): ArrayCollection|Collection
    {
        return $this->priceRateEntries;
    }

    /**
     * @param Collection $priceRateEntries
     *
     * @return Group
     */
    public function setPriceRateEntries(ArrayCollection|Collection $priceRateEntries): Group
    {
        $this->priceRateEntries = $priceRateEntries;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getArticles(): ArrayCollection|Collection
    {
        return $this->articles;
    }

    /**
     * @param Collection $articles
     *
     * @return Group
     */
    public function setArticles(ArrayCollection|Collection $articles): Group
    {
        $this->articles = $articles;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Group
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getArticleGroups(): ArrayCollection|Collection
    {
        return $this->articleGroups;
    }

    /**
     * @param Collection $articleGroups
     */
    public function setArticleGroups(ArrayCollection|Collection $articleGroups): void
    {
        $this->articleGroups = $articleGroups;
    }


}
