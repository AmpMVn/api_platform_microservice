<?php

namespace App\Entity\Settings\Storage;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Entity\Article\Stock;
use App\Entity\Client\Client;
use App\Entity\Settings\Location\Location;
use App\Repository\Settings\Storage\StorageRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Settings\Storage\StorageRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Table(name="settings_storage")
 */
#[ApiResource(
    shortName: "Settings/Storages",
    normalizationContext: ['groups' => ['settings_storage', 'article']],
    collectionOperations: ['get'],
    itemOperations: ['get'],
)]
class Storage extends AbstractEntity
{

    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article", "settings_storage"])]
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
    #[Groups(["article", "settings_storage"])]
    private string $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article", "settings_storage"])]
    private ?string $street = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article", "settings_storage"])]
    private ?string $houseNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article", "settings_storage"])]
    private ?string $zip = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article", "settings_storage"])]
    private ?string $city = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups(["article", "settings_storage"])]
    private ?string $country = null;

    /**
     * @var Collection<int,Stock>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Stock", mappedBy="storage")
     */
    private Collection $stocks;

    /**
     * @var Location|null
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Location\Location", inversedBy="storages")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=true)
     * @ApiSubresource
     */
    #[Groups(["article"])]
    private ?Location $location = null;

    /**
     * @var Collection<int,Article>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Article", mappedBy="storage")
     */
    private Collection $articles;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Settings\Storage\Image", mappedBy="storage")
     * @ApiSubresource
     */
    #[Groups(["article", "settings_storage"])]
    private Collection $images;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->images = new ArrayCollection();
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
     * @return Storage
     */
    public function setId(int $id): Storage
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
     * @return Storage
     */
    public function setCreatedAt(DateTime $createdAt): Storage
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
     * @return Storage
     */
    public function setUpdatedAt(?DateTime $updatedAt): Storage
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
     * @return Storage
     */
    public function setName(string $name): Storage
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     *
     * @return Storage
     */
    public function setStreet(?string $street): Storage
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * @param string|null $houseNumber
     *
     * @return Storage
     */
    public function setHouseNumber(?string $houseNumber): Storage
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string|null $zip
     *
     * @return Storage
     */
    public function setZip(?string $zip): Storage
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     *
     * @return Storage
     */
    public function setCity(?string $city): Storage
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     *
     * @return Storage
     */
    public function setCountry(?string $country): Storage
    {
        $this->country = $country;

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
     * @return Storage
     */
    public function setStocks(ArrayCollection|Collection $stocks): Storage
    {
        $this->stocks = $stocks;

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
     * @return Storage
     */
    public function setLocation(?Location $location): Storage
    {
        $this->location = $location;

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
     * @return Storage
     */
    public function setArticles(ArrayCollection|Collection $articles): Storage
    {
        $this->articles = $articles;

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
     * @return Storage
     */
    public function setImages(ArrayCollection|Collection $images): Storage
    {
        $this->images = $images;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Storage
    {
        $this->clientId = $clientId;
        return $this;
    }

}
