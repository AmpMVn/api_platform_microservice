<?php

namespace App\Entity\Settings\Location;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Entity\Article\Stock;
use App\Entity\Client\Client;
use App\Entity\Settings\Storage\Storage;
use App\Filter\LocationFulltextSearchFilter;
use App\Repository\Settings\Location\LocationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Settings\Location\LocationRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Table(name="settings_location")
 */
#[ApiResource(
    normalizationContext: [ 'groups' => [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] ],
    shortName: "Settings/Location",
    collectionOperations: ['get'],
    itemOperations: ['get']
)]
#[ApiFilter(LocationFulltextSearchFilter::class, properties: ['searchQuery' => 'exact'])]
#[ApiFilter(SearchFilter::class, properties: ['clientId' => 'exact', 'status' => 'exact'])]
#[ApiFilter( OrderFilter::class, properties: [ 'name', 'street', 'zip', 'city', 'country' ], arguments: [ 'orderParameterName' => 'order' ] )]
class Location extends AbstractEntity
{

    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
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
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
    private string $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
    private string $street;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
    private string $houseNumber;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ 'settings_location', 'settings_storage' , 'article_full', 'article_minimum'] )]
    private string $zip;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
    private string $city;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full', 'article_minimum' ] )]
    private string $country;

    /**
     * @var float|null
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full' ] )]
    private ?float $lat = null;

    /**
     * @var float|null
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups( [ 'settings_location', 'settings_storage', 'article_full' ] )]
    private ?float $lng = null;

    /**
     * @var int
     * @ORM\Column (type="smallint", nullable=false, options={"default" : 10})
     */
    #[Groups(["article_minimum", "article_full"])]
    private int $status = self::STATUS_ACTIVE;

    /**
     * @var Collection<int,Storage>
     * @ORM\OneToMany(targetEntity="App\Entity\Settings\Storage\Storage", mappedBy="location")
     */
    private Collection $storages;

    /**
     * @var Collection<int,Article>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Article", mappedBy="location")
     */
    private Collection $articles;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Settings\Location\Image", mappedBy="location")
     * @ApiSubresource
     */
    #[Groups(["settings_storage", "settings_location"])]
    private Collection $images;

    public function __construct()
    {
        $this->storages = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->images = new ArrayCollection();
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
     * @return Location
     */
    public function setImages(ArrayCollection|Collection $images): Location
    {
        $this->images = $images;

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
     * @return Location
     */
    public function setId(int $id): Location
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
     * @return Location
     */
    public function setCreatedAt(DateTime $createdAt): Location
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
     * @return Location
     */
    public function setUpdatedAt(?DateTime $updatedAt): Location
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
     * @return Location
     */
    public function setName(string $name): Location
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     *
     * @return Location
     */
    public function setStreet(string $street): Location
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     *
     * @return Location
     */
    public function setHouseNumber(string $houseNumber): Location
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     *
     * @return Location
     */
    public function setZip(string $zip): Location
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Location
     */
    public function setCity(string $city): Location
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Location
     */
    public function setCountry(string $country): Location
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getStorages(): ArrayCollection|Collection
    {
        return $this->storages;
    }

    /**
     * @param Collection $storages
     *
     * @return Location
     */
    public function setStorages(ArrayCollection|Collection $storages): Location
    {
        $this->storages = $storages;

        return $this;
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
     *
     * @return Location
     */
    public function setArticles(Collection $articles): Location
    {
        $this->articles = $articles;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLat(): ?float
    {
        return $this->lat;
    }

    /**
     * @param float|null $lat
     *
     * @return Location
     */
    public function setLat(?float $lat): Location
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLng(): ?float
    {
        return $this->lng;
    }

    /**
     * @param float|null $lng
     *
     * @return Location
     */
    public function setLng(?float $lng): Location
    {
        $this->lng = $lng;

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
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Location
    {
        $this->clientId = $clientId;
        return $this;
    }

}
