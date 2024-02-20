<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use App\Entity\AbstractEntity;
use App\Entity\Price\Deal\Deal;
use App\Entity\Price\Discount\Discount;
use App\Entity\Settings\Category;
use App\Entity\Settings\Location\Location;
use App\Entity\Price\Rate\Group;
use App\Entity\Settings\Storage\Storage;
use App\Filter\ArticleCategoryFilter;
use App\Filter\ArticleLocationFilter;
use App\Filter\ArticlesByOnlineBookingIdFilter;
use App\Filter\FiltersSearchFilter;
use App\Filter\ArticleFulltextSearchFilter;
use App\Filter\RentalDatesFilter;
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
 * @ORM\Entity(repositoryClass=App\Repository\Article\ArticleRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Loggable
 * @Table(name="article")
 */
#[ApiResource(
    shortName: 'Articles',
    order: ["name" => "ASC"],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['article_full'], 'enable_max_depth' => true],
        ],
    ],
    collectionOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['article_minimum']],
        ],
    ],
    attributes: [
        "force_eager" => false,
        "pagination_items_per_page" => 24,
        "pagination_maximum_items_per_page" => 24,
        "pagination_enabled" => true,
    ]
)]
#[ApiFilter(ArticlesByOnlineBookingIdFilter::class, properties: ['onlineBookingId' => 'exact'])]
#[ApiFilter(FiltersSearchFilter::class, properties: ['filters' => 'exact'])]
#[ApiFilter(ArticleFulltextSearchFilter::class, properties: ['searchQuery' => 'exact'])]
#[ApiFilter(ArticleCategoryFilter::class, properties: ['category' => 'exact'])]
#[ApiFilter(ArticleLocationFilter::class, properties: ['location' => 'exact'])]
#[ApiFilter(SearchFilter::class, properties: ['location' => 'exact', 'manufacturer' => 'exact', 'clientId' => 'exact'])]
#[ApiFilter(RentalDatesFilter::class, properties: ['rentalDates' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['manufacturer', 'model', 'name', 'articleId', 'relevance'], arguments: ['orderParameterName' => 'order'])]
#[ApiFilter(GroupFilter::class, arguments: ['overrideDefaultGroups' => true, 'whitelist' => null])]
class Article extends AbstractEntity implements Translatable
{

    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article_minimum", "article_full", "booking"])]
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
    #[Groups(["article_minimum", "article_full"])]
    private string $name;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $manufacturer = null;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $model = null;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $modelDescription = null;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    #[Groups(["article_minimum", "article_full"])]
    private string $articleId;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"default" : 0})
     */
    #[Groups(["article_minimum", "article_full"])]
    private int $quantityType = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    #[Groups(["article_minimum", "article_full"])]
    private int $quantity = 0;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $description = null;

    /**
     * @var string|null
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $descriptionTeaser = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $serialNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $serialCode = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $codeContent = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleUse = null;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups(["article_minimum", "article_full"])]
    private string $articleType;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleCounter = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleCounterType = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleValue1 = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleValue2 = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $articleValue3 = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $tags = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?string $relevance = null;

    /**
     * @var int|null
     * @ORM\Column (type="smallint", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?int $defaultPriceCalculation = null;

    /**
     * @var int|null
     * @ORM\Column (type="smallint", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?int $defaultPriceCalculationType = null;

    /**
     * @var float|null
     * @ORM\Column (type="float", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?float $priceFix = null;

    /**
     * @var float|null
     * @ORM\Column (type="float", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?float $priceDisplay = null;

    /**
     * @var float|null
     * @ORM\Column (type="float", nullable=true)
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?float $priceDeposit = null;

    /**
     * @var int
     * @ORM\Column (type="smallint", nullable=false, options={"default" : 10})
     */
    #[Groups(["article_minimum", "article_full"])]
    private int $status = Article::STATUS_ACTIVE;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Image", mappedBy="article", cascade={"remove"})
     */
    #[Groups(["article_minimum", "article_full"])]
    private Collection $images;

    /**
     * @var Collection<int,Attribute>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Attribute", mappedBy="article")
     * @ORM\OrderBy({"priority" = "ASC"})
     */
    #[Groups(["article_minimum", "article_full"])]
    private Collection $attributes;

    /**
     * @var null|Category
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Category", inversedBy="articles")
     */
    #[Groups(["article_minimum", "article_full"])]
    private ?Category $category = null;

    /**
     * @var null|Storage
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Storage\Storage", inversedBy="articles")
     */
    #[Groups(["article_full"])]
    private ?Storage $storage = null;

    /**
     * @var null|Location
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Location\Location", inversedBy="articles")
     */
    #[Groups(["article_full", "article_minimum"])]
    private ?Location $location = null;

    /**
     * @var Collection<int,Stock>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Stock", mappedBy="article")
     * @MaxDepth(1)
     */
    #[Groups(["article_full"])]
    private Collection $stocks;

    /**
     * @var Collection<int,Accessories>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Accessories", mappedBy="articleParent")
     * @ORM\OrderBy({"priority" = "ASC"})
     * @MaxDepth(1)
     */
    #[Groups(["article_full"])]
    private Collection $accessories;

    /**
     * @var Collection<int,Group>
     * @ORM\ManyToMany(targetEntity="App\Entity\Price\Rate\Group", inversedBy="articles")
     * @ORM\JoinTable(name="article_price_rate__list")
     */
    private Collection $priceRates;

    /**
     * @var Collection<int,Discount>
     * @ORM\ManyToMany(targetEntity="App\Entity\Price\Discount\Discount", inversedBy="articles")
     * @ORM\JoinTable(name="article_price_discount__list")
     */
    #[Groups(["article_full"])]
    private Collection $priceDiscounts;

    /**
     * @var Collection<int,Deal>
     * @ORM\ManyToMany(targetEntity="App\Entity\Price\Deal\Deal", inversedBy="articles")
     * @ORM\JoinTable(name="article_price_deal__list")
     */
    #[Groups(["article_full"])]
    private Collection $priceDeals;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Booking", mappedBy="article")
     */
    private Collection $bookings;

    /**
     * @var array<int, string>
     * @ORM\Column(type="json", options={"jsonb"=true})
     */
    #[Groups(["article_minimum", "article_full"])]
    protected array $filters;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Microservice\Article\OnlineBooking", mappedBy="article", fetch="EXTRA_LAZY")
     */
    private $msOnlineBookings;

    /**
     * @Gedmo\Locale
     */
    private $locale;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->accessories = new ArrayCollection();
        $this->priceRates = new ArrayCollection();
        $this->priceDeals = new ArrayCollection();
        $this->priceDiscounts = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->attributes = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->msOnlineBookings = new ArrayCollection();
        $this->filters = [];
    }

    public function addPriceRates(Group $object): void
    {
        if (!$this->priceRates->contains($object)) {
            $this->priceRates[] = $object;
            $this->priceRates->add($object);
        }
    }

    public function addPriceDiscount(Discount $object): void
    {
        if (!$this->priceDiscounts->contains($object)) {
            $this->priceDiscounts[] = $object;
            $this->priceDiscounts->add($object);
        }
    }

    public function addPriceDeal(Deal $object): void
    {
        if (!$this->priceDeals->contains($object)) {
            $this->priceDeals[] = $object;
            $this->priceDeals->add($object);
        }
    }

    public function addFilter($objectId): void
    {
        if (!in_array($objectId, $this->filters)) {
            $this->filters[] = $objectId;
        }
    }

    /**
     * @param mixed $locale
     *
     * @return Article
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
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
     * @return Article
     */
    public function setId(int $id): Article
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
     * @return Article
     */
    public function setCreatedAt(DateTime $createdAt): Article
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
     * @return Article
     */
    public function setUpdatedAt(?DateTime $updatedAt): Article
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
     * @return Article
     */
    public function setName(string $name): Article
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    /**
     * @param string|null $manufacturer
     *
     * @return Article
     */
    public function setManufacturer(?string $manufacturer): Article
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @param string|null $model
     *
     * @return Article
     */
    public function setModel(?string $model): Article
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModelDescription(): ?string
    {
        return $this->modelDescription;
    }

    /**
     * @param string|null $modelDescription
     *
     * @return Article
     */
    public function setModelDescription(?string $modelDescription): Article
    {
        $this->modelDescription = $modelDescription;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleId(): string
    {
        return $this->articleId;
    }

    /**
     * @param string $articleId
     *
     * @return Article
     */
    public function setArticleId(string $articleId): Article
    {
        $this->articleId = $articleId;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantityType(): int
    {
        return $this->quantityType;
    }

    /**
     * @param int $quantityType
     *
     * @return Article
     */
    public function setQuantityType(int $quantityType): Article
    {
        $this->quantityType = $quantityType;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     *
     * @return Article
     */
    public function setQuantity(int $quantity): Article
    {
        $this->quantity = $quantity;

        return $this;
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
     *
     * @return Article
     */
    public function setDescription(?string $description): Article
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptionTeaser(): ?string
    {
        return $this->descriptionTeaser;
    }

    /**
     * @param string|null $descriptionTeaser
     *
     * @return Article
     */
    public function setDescriptionTeaser(?string $descriptionTeaser): Article
    {
        $this->descriptionTeaser = $descriptionTeaser;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    /**
     * @param string|null $serialNumber
     *
     * @return Article
     */
    public function setSerialNumber(?string $serialNumber): Article
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSerialCode(): ?string
    {
        return $this->serialCode;
    }

    /**
     * @param string|null $serialCode
     *
     * @return Article
     */
    public function setSerialCode(?string $serialCode): Article
    {
        $this->serialCode = $serialCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCodeContent(): ?string
    {
        return $this->codeContent;
    }

    /**
     * @param string|null $codeContent
     *
     * @return Article
     */
    public function setCodeContent(?string $codeContent): Article
    {
        $this->codeContent = $codeContent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArticleUse(): ?string
    {
        return $this->articleUse;
    }

    /**
     * @param string|null $articleUse
     *
     * @return Article
     */
    public function setArticleUse(?string $articleUse): Article
    {
        $this->articleUse = $articleUse;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleType(): string
    {
        return $this->articleType;
    }

    /**
     * @param string $articleType
     *
     * @return Article
     */
    public function setArticleType(string $articleType): Article
    {
        $this->articleType = $articleType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArticleCounter(): ?string
    {
        return $this->articleCounter;
    }

    /**
     * @param string|null $articleCounter
     *
     * @return Article
     */
    public function setArticleCounter(?string $articleCounter): Article
    {
        $this->articleCounter = $articleCounter;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArticleCounterType(): ?string
    {
        return $this->articleCounterType;
    }

    /**
     * @param string|null $articleCounterType
     *
     * @return Article
     */
    public function setArticleCounterType(?string $articleCounterType): Article
    {
        $this->articleCounterType = $articleCounterType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * @param string|null $tags
     *
     * @return Article
     */
    public function setTags(?string $tags): Article
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param string[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return int|null
     */
    public function getDefaultPriceCalculation(): ?int
    {
        return $this->defaultPriceCalculation;
    }

    /**
     * @param int|null $defaultPriceCalculation
     *
     * @return Article
     */
    public function setDefaultPriceCalculation(?int $defaultPriceCalculation): Article
    {
        $this->defaultPriceCalculation = $defaultPriceCalculation;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceFix(): ?float
    {
        return $this->priceFix;
    }

    /**
     * @param float|null $priceFix
     *
     * @return Article
     */
    public function setPriceFix(?float $priceFix): Article
    {
        $this->priceFix = $priceFix;

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
     * @return Article
     */
    public function setStatus(int $status): Article
    {
        $this->status = $status;

        return $this;
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
     *
     * @return Article
     */
    public function setImages(ArrayCollection|Collection $images): Article
    {
        $this->images = $images;

        return $this;
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
     *
     * @return Article
     */
    public function setAttributes(ArrayCollection|Collection $attributes): Article
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     *
     * @return Article
     */
    public function setCategory(?Category $category): Article
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Storage|null
     */
    public function getStorage(): ?Storage
    {
        return $this->storage;
    }

    /**
     * @param Storage|null $storage
     *
     * @return Article
     */
    public function setStorage(?Storage $storage): Article
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * @param Location|null $location
     *
     * @return Article
     */
    public function setLocation(?Location $location): Article
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getStocks(): ArrayCollection|Collection
    {
        return $this->stocks;
    }

    /**
     * @param Collection $stocks
     *
     * @return Article
     */
    public function setStocks(ArrayCollection|Collection $stocks): Article
    {
        $this->stocks = $stocks;

        return $this;
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
     *
     * @return Article
     */
    public function setAccessories(ArrayCollection|Collection $accessories): Article
    {
        $this->accessories = $accessories;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPriceRates(): ArrayCollection|Collection
    {
        return $this->priceRates;
    }

    /**
     * @param Collection $priceRates
     *
     * @return Article
     */
    public function setPriceRates(ArrayCollection|Collection $priceRates): Article
    {
        $this->priceRates = $priceRates;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPriceDiscounts(): Collection
    {
        return $this->priceDiscounts;
    }

    /**
     * @param Collection $priceDiscounts
     *
     * @return Article
     */
    public function setPriceDiscounts(Collection $priceDiscounts): Article
    {
        $this->priceDiscounts = $priceDiscounts;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getBookings(): ArrayCollection|Collection
    {
        return $this->bookings;
    }

    /**
     * @param ArrayCollection|Collection $bookings
     *
     * @return Article
     */
    public function setBookings(ArrayCollection|Collection $bookings): Article
    {
        $this->bookings = $bookings;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMsOnlineBookings()
    {
        return $this->msOnlineBookings;
    }

    /**
     * @param mixed $msOnlineBookings
     *
     * @return Article
     */
    public function setMsOnlineBookings($msOnlineBookings)
    {
        $this->msOnlineBookings = $msOnlineBookings;

        return $this;
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
     *
     * @return Article
     */
    public function setPriceDisplay(?float $priceDisplay): Article
    {
        $this->priceDisplay = $priceDisplay;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRelevance(): ?string
    {
        return $this->relevance;
    }

    /**
     * @param string|null $relevance
     *
     * @return Article
     */
    public function setRelevance(?string $relevance): Article
    {
        $this->relevance = $relevance;

        return $this;
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
     *
     * @return Article
     */
    public function setPriceDeposit(?float $priceDeposit): Article
    {
        $this->priceDeposit = $priceDeposit;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArticleValue1(): ?string
    {
        return $this->articleValue1;
    }

    /**
     * @param string|null $articleValue1
     */
    public function setArticleValue1(?string $articleValue1): void
    {
        $this->articleValue1 = $articleValue1;
    }

    /**
     * @return string|null
     */
    public function getArticleValue2(): ?string
    {
        return $this->articleValue2;
    }

    /**
     * @param string|null $articleValue2
     */
    public function setArticleValue2(?string $articleValue2): void
    {
        $this->articleValue2 = $articleValue2;
    }

    /**
     * @return string|null
     */
    public function getArticleValue3(): ?string
    {
        return $this->articleValue3;
    }

    /**
     * @param string|null $articleValue3
     */
    public function setArticleValue3(?string $articleValue3): void
    {
        $this->articleValue3 = $articleValue3;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Article
    {
        $this->clientId = $clientId;
        return $this;
    }
    /**
     * @return int|null
     */
    public function getDefaultPriceCalculationType(): ?int
    {
        return $this->defaultPriceCalculationType;
    }

    /**
     * @param int|null $defaultPriceCalculationType
     */
    public function setDefaultPriceCalculationType(?int $defaultPriceCalculationType): void
    {
        $this->defaultPriceCalculationType = $defaultPriceCalculationType;
    }

    /**
     * @return Collection
     */
    public function getPriceDeals(): Collection
    {
        return $this->priceDeals;
    }

    /**
     * @param Collection $priceDeals
     */
    public function setPriceDeals(Collection $priceDeals): void
    {
        $this->priceDeals = $priceDeals;
    }


}
