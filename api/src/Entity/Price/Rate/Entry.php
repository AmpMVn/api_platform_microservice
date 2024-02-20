<?php

namespace App\Entity\Price\Rate;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Repository\Price\Rate\EntryRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Price\Rate\EntryRepository::class)
 * @Table(name="price_rate_entry")
 */
class Entry extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( ["article", "price_rate"] )]
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
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 10})
     */
    #[Groups( ["article", "price_rate"] )]
    private int $priceType = self::PRICE_RATE_TYPE_PER_UNIT;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 24})
     */
    #[Groups( ["article", "price_rate"] )]
    private int $unit = 24;


    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private int $unitFrom = 0;


    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private int $unitTo = 0;


    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $unitPrice = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $unitFree = 0;

    /**
     * @var float|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( ["article", "price_rate"] )]
    private ?string $unitName = null;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountMon = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountTue = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountWed = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountThu = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountFri = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountSat = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $discountSun = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceMon = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceTue = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceWed = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceThu = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceFri = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceSat = 0;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default": 0})
     */
    #[Groups( ["article", "price_rate"] )]
    private float $fixPriceSun = 0;

    /**
     * @var Group
     * @ORM\ManyToOne(targetEntity="App\Entity\Price\Rate\Group", inversedBy="priceRateEntries")
     * @ORM\JoinColumn(name="price_rate_group_id", referencedColumnName="id")
     */
    private Group $priceRateGroup;

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
     * @return Entry
     */
    public function setId(int $id) : Entry
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
     * @return Entry
     */
    public function setCreatedAt(DateTime $createdAt) : Entry
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     *
     * @return Entry
     */
    public function setUpdatedAt(?DateTime $updatedAt) : Entry
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getUnit() : int
    {
        return $this->unit;
    }

    /**
     * @param int $unit
     *
     * @return Entry
     */
    public function setUnit(int $unit) : Entry
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return int
     */
    public function getUnitFrom() : int
    {
        return $this->unitFrom;
    }

    /**
     * @param int $unitFrom
     *
     * @return Entry
     */
    public function setUnitFrom(int $unitFrom) : Entry
    {
        $this->unitFrom = $unitFrom;

        return $this;
    }

    /**
     * @return int
     */
    public function getUnitTo() : int
    {
        return $this->unitTo;
    }

    /**
     * @param int $unitTo
     *
     * @return Entry
     */
    public function setUnitTo(int $unitTo) : Entry
    {
        $this->unitTo = $unitTo;

        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice() : float|int
    {
        return $this->unitPrice;
    }

    /**
     * @param float $unitPrice
     *
     * @return Entry
     */
    public function setUnitPrice(float|int $unitPrice) : Entry
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getUnitFree() : float|int
    {
        return $this->unitFree;
    }

    /**
     * @param float $unitFree
     *
     * @return Entry
     */
    public function setUnitFree(float|int $unitFree) : Entry
    {
        $this->unitFree = $unitFree;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountMon() : float|int
    {
        return $this->discountMon;
    }

    /**
     * @param float $discountMon
     *
     * @return Entry
     */
    public function setDiscountMon(float|int $discountMon) : Entry
    {
        $this->discountMon = $discountMon;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountTue() : float|int
    {
        return $this->discountTue;
    }

    /**
     * @param float $discountTue
     *
     * @return Entry
     */
    public function setDiscountTue(float|int $discountTue) : Entry
    {
        $this->discountTue = $discountTue;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountWed() : float|int
    {
        return $this->discountWed;
    }

    /**
     * @param float $discountWed
     *
     * @return Entry
     */
    public function setDiscountWed(float|int $discountWed) : Entry
    {
        $this->discountWed = $discountWed;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountThu() : float|int
    {
        return $this->discountThu;
    }

    /**
     * @param float $discountThu
     *
     * @return Entry
     */
    public function setDiscountThu(float|int $discountThu) : Entry
    {
        $this->discountThu = $discountThu;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountFri() : float|int
    {
        return $this->discountFri;
    }

    /**
     * @param float $discountFri
     *
     * @return Entry
     */
    public function setDiscountFri(float|int $discountFri) : Entry
    {
        $this->discountFri = $discountFri;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountSat() : float|int
    {
        return $this->discountSat;
    }

    /**
     * @param float $discountSat
     *
     * @return Entry
     */
    public function setDiscountSat(float|int $discountSat) : Entry
    {
        $this->discountSat = $discountSat;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountSun() : float|int
    {
        return $this->discountSun;
    }

    /**
     * @param float $discountSun
     *
     * @return Entry
     */
    public function setDiscountSun(float|int $discountSun) : Entry
    {
        $this->discountSun = $discountSun;

        return $this;
    }

    /**
     * @return Group
     */
    public function getPriceRateGroup() : Group
    {
        return $this->priceRateGroup;
    }

    /**
     * @param Group $priceRateGroup
     *
     * @return Entry
     */
    public function setPriceRateGroup(Group $priceRateGroup) : Entry
    {
        $this->priceRateGroup = $priceRateGroup;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriceType() : int
    {
        return $this->priceType;
    }

    /**
     * @param int $priceType
     *
     * @return Entry
     */
    public function setPriceType(int $priceType) : Entry
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getUnitName() : float|string|null
    {
        return $this->unitName;
    }

    /**
     * @param float|null $unitName
     *
     * @return Entry
     */
    public function setUnitName(float|string|null $unitName) : Entry
    {
        $this->unitName = $unitName;

        return $this;
    }

    /**
     * @return float
     */
    public function getFixPriceMon(): float|int
    {
        return $this->fixPriceMon;
    }

    /**
     * @param float $fixPriceMon
     */
    public function setFixPriceMon(float|int $fixPriceMon): void
    {
        $this->fixPriceMon = $fixPriceMon;
    }

    /**
     * @return float
     */
    public function getFixPriceTue(): float|int
    {
        return $this->fixPriceTue;
    }

    /**
     * @param float $fixPriceTue
     */
    public function setFixPriceTue(float|int $fixPriceTue): void
    {
        $this->fixPriceTue = $fixPriceTue;
    }

    /**
     * @return float
     */
    public function getFixPriceWed(): float|int
    {
        return $this->fixPriceWed;
    }

    /**
     * @param float $fixPriceWed
     */
    public function setFixPriceWed(float|int $fixPriceWed): void
    {
        $this->fixPriceWed = $fixPriceWed;
    }

    /**
     * @return float
     */
    public function getFixPriceThu(): float|int
    {
        return $this->fixPriceThu;
    }

    /**
     * @param float $fixPriceThu
     */
    public function setFixPriceThu(float|int $fixPriceThu): void
    {
        $this->fixPriceThu = $fixPriceThu;
    }

    /**
     * @return float
     */
    public function getFixPriceFri(): float|int
    {
        return $this->fixPriceFri;
    }

    /**
     * @param float $fixPriceFri
     */
    public function setFixPriceFri(float|int $fixPriceFri): void
    {
        $this->fixPriceFri = $fixPriceFri;
    }

    /**
     * @return float
     */
    public function getFixPriceSat(): float|int
    {
        return $this->fixPriceSat;
    }

    /**
     * @param float $fixPriceSat
     */
    public function setFixPriceSat(float|int $fixPriceSat): void
    {
        $this->fixPriceSat = $fixPriceSat;
    }

    /**
     * @return float
     */
    public function getFixPriceSun(): float|int
    {
        return $this->fixPriceSun;
    }

    /**
     * @param float $fixPriceSun
     */
    public function setFixPriceSun(float|int $fixPriceSun): void
    {
        $this->fixPriceSun = $fixPriceSun;
    }



}
