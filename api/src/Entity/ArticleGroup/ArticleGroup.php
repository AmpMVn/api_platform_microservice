<?php

namespace App\Entity\ArticleGroup;

use ApiPlatform\Core\Annotation\ApiFilter;
use App\Entity\Price\Rate\Group;
use App\Repository\ArticleGroup\ArticleGroupRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use App\Entity\AbstractEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ArticleGroup\ArticleGroupRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Loggable
 * @Table(name="article_group")
 */
#[ApiResource(
    shortName: 'ArticleGroups',
    order: ["name" => "ASC"],
)]
#[ApiFilter(SearchFilter::class, properties: ['clientId' => 'exact'])]

class ArticleGroup extends AbstractEntity implements Translatable
{

    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article_group"])]
    protected int $id;

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
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups(["article_group"])]
    private string $name;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    #[Groups(["article_group"])]
    private bool $enableOnlineBooking = false;

    /**
     * @var float|null
     * @ORM\Column (type="float", nullable=true)
     */
    #[Groups(["article_group"])]
    private ?float $priceDisplay = null;

    /**
     * @var float|null
     * @ORM\Column (type="float", nullable=true)
     */
    #[Groups(["article_group"])]
    private ?float $priceDeposit = null;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_group"])]
    private ?string $description = null;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="App\Entity\ArticleGroup\Image", mappedBy="articleGroup")
     */
    #[Groups(["article_group"])]
    private Collection $images;

    /**
     * @var Collection<int,Attribute>
     * @ORM\OneToMany(targetEntity="App\Entity\ArticleGroup\Attribute", mappedBy="articleGroup")
     * @ORM\OrderBy({"priority" = "ASC"})
     */
    #[Groups(["article_group"])]
    private Collection $attributes;

    /**
     * @var Collection<int,Group>
     * @ORM\ManyToMany(targetEntity="App\Entity\Price\Rate\Group", inversedBy="articleGroups")
     * @ORM\JoinTable(name="article_group_price_rate__list")
     */
    #[Groups(["article_group"])]
    private Collection $priceRates;

    /**
     * @var Collection<int,Accessories>
     * @ORM\OneToMany(targetEntity="App\Entity\ArticleGroup\Accessories", mappedBy="articleGroup")
     * @ORM\OrderBy({"priority" = "ASC"})
     * @MaxDepth(1)
     */
    #[Groups(["article_group"])]
    private Collection $accessories;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->attributes = new ArrayCollection();
        $this->priceRates = new ArrayCollection();
        $this->accessories = new ArrayCollection();
    }

    public function addPriceRates(Group $object): void
    {
        if (!$this->priceRates->contains($object)) {
            $this->priceRates[] = $object;
            $this->priceRates->add($object);
        }
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
     */
    public function setId(int $id): void
    {
        $this->id = $id;
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
     * @return bool
     */
    public function isEnableOnlineBooking(): bool
    {
        return $this->enableOnlineBooking;
    }

    /**
     * @param bool $enableOnlineBooking
     */
    public function setEnableOnlineBooking(bool $enableOnlineBooking): void
    {
        $this->enableOnlineBooking = $enableOnlineBooking;
    }

    /**
     * @return Collection
     */
    public function getImages(): ArrayCollection|Collection
    {
        return $this->images;
    }

    /**
     * @param Collection $images
     */
    public function setImages(ArrayCollection|Collection $images): void
    {
        $this->images = $images;
    }

    /**
     * @return Collection
     */
    public function getAttributes(): ArrayCollection|Collection
    {
        return $this->attributes;
    }

    /**
     * @param Collection $attributes
     */
    public function setAttributes(ArrayCollection|Collection $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return float|null
     */
    public function getPriceDisplay(): ?float
    {
        return $this->priceDisplay;
    }

    /**
     * @param float|null $priceDisplay
     */
    public function setPriceDisplay(?float $priceDisplay): void
    {
        $this->priceDisplay = $priceDisplay;
    }

    /**
     * @return float|null
     */
    public function getPriceDeposit(): ?float
    {
        return $this->priceDeposit;
    }

    /**
     * @param float|null $priceDeposit
     */
    public function setPriceDeposit(?float $priceDeposit): void
    {
        $this->priceDeposit = $priceDeposit;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return Collection
     */
    public function getPriceRates(): Collection
    {
        return $this->priceRates;
    }

    /**
     * @param Collection $priceRates
     */
    public function setPriceRates(Collection $priceRates): void
    {
        $this->priceRates = $priceRates;
    }

    /**
     * @return Collection
     */
    public function getAccessories(): ArrayCollection|Collection
    {
        return $this->accessories;
    }

    /**
     * @param Collection $accessories
     */
    public function setAccessories(ArrayCollection|Collection $accessories): void
    {
        $this->accessories = $accessories;
    }


}
