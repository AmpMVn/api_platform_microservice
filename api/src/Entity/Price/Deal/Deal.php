<?php

namespace App\Entity\Price\Deal;

use App\Entity\AbstractEntity;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Price\Deal\DealRepository::class)
 * @Table(name="price_deal")
 */
class Deal extends AbstractEntity
{
    const DEAL_BASE_HOUR = "hour";
    const DEAL_BASE_DAY = "day";
    const DEAL_SSPECIFICATION_TIME = "time";
    const DEAL_SSPECIFICATION_LENGTH = "length";

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_full", "price_deal" ] )]
    private int $id;

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
    #[Groups( [ "article_full", "price_deal" ] )]
    private string $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private string $dealBase;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private string $dealSpecification;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $validStart;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $validEnd;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $spec10Start = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $spec10MaxHours = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $spec10ValidDays = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $spec20HourStart = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $spec20HourEnd = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $spec20ValidDays = null;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    #[Groups( [ "article_full", "price_deal" ] )]
    private float $price;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default", false})
     */
    private bool $enabledMsOnlineBooking = false;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="App\Entity\Article\Article", mappedBy="priceDeals")
     */
    private Collection $articles;

    /**
     * @ORM\Column(type="uuid")
     */
    private string $clientId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
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
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
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
     */
    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDealBase(): string
    {
        return $this->dealBase;
    }

    /**
     * @param string $dealBase
     */
    public function setDealBase(string $dealBase): void
    {
        $this->dealBase = $dealBase;
    }

    /**
     * @return string
     */
    public function getDealSpecification(): string
    {
        return $this->dealSpecification;
    }

    /**
     * @param string $dealSpecification
     */
    public function setDealSpecification(string $dealSpecification): void
    {
        $this->dealSpecification = $dealSpecification;
    }

    /**
     * @return DateTime
     */
    public function getValidStart(): DateTime
    {
        return $this->validStart;
    }

    /**
     * @param DateTime $validStart
     */
    public function setValidStart(DateTime $validStart): void
    {
        $this->validStart = $validStart;
    }

    /**
     * @return DateTime
     */
    public function getValidEnd(): DateTime
    {
        return $this->validEnd;
    }

    /**
     * @param DateTime $validEnd
     */
    public function setValidEnd(DateTime $validEnd): void
    {
        $this->validEnd = $validEnd;
    }

    /**
     * @return string|null
     */
    public function getSpec10Start(): ?string
    {
        return $this->spec10Start;
    }

    /**
     * @param string|null $spec10Start
     */
    public function setSpec10Start(?string $spec10Start): void
    {
        $this->spec10Start = $spec10Start;
    }

    /**
     * @return int|null
     */
    public function getSpec10MaxHours(): ?int
    {
        return $this->spec10MaxHours;
    }

    /**
     * @param int|null $spec10MaxHours
     */
    public function setSpec10MaxHours(?int $spec10MaxHours): void
    {
        $this->spec10MaxHours = $spec10MaxHours;
    }

    /**
     * @return string|null
     */
    public function getSpec10ValidDays(): ?string
    {
        return $this->spec10ValidDays;
    }

    /**
     * @param string|null $spec10ValidDays
     */
    public function setSpec10ValidDays(?string $spec10ValidDays): void
    {
        $this->spec10ValidDays = $spec10ValidDays;
    }

    /**
     * @return int|null
     */
    public function getSpec20HourStart(): ?int
    {
        return $this->spec20HourStart;
    }

    /**
     * @param int|null $spec20HourStart
     */
    public function setSpec20HourStart(?int $spec20HourStart): void
    {
        $this->spec20HourStart = $spec20HourStart;
    }

    /**
     * @return int|null
     */
    public function getSpec20HourEnd(): ?int
    {
        return $this->spec20HourEnd;
    }

    /**
     * @param int|null $spec20HourEnd
     */
    public function setSpec20HourEnd(?int $spec20HourEnd): void
    {
        $this->spec20HourEnd = $spec20HourEnd;
    }

    /**
     * @return string|null
     */
    public function getSpec20ValidDays(): ?string
    {
        return $this->spec20ValidDays;
    }

    /**
     * @param string|null $spec20ValidDays
     */
    public function setSpec20ValidDays(?string $spec20ValidDays): void
    {
        $this->spec20ValidDays = $spec20ValidDays;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
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
     */
    public function setEnabledMsOnlineBooking(bool $enabledMsOnlineBooking): void
    {
        $this->enabledMsOnlineBooking = $enabledMsOnlineBooking;
    }

    /**
     * @return Collection
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    /**
     * @param Collection $articles
     */
    public function setArticles(Collection $articles): void
    {
        $this->articles = $articles;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }


}
