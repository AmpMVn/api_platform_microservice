<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Article\BookingRepository::class)
 * @Table(name="article_booking")
 */
#[ApiResource(
    shortName: 'Article/Bookings',
    denormalizationContext: [ 'groups' => [ 'booking_write' ] ],
    normalizationContext: [ 'groups' => [ 'booking' ] ],
)]
#[ApiFilter(SearchFilter::class, properties: ['article' => 'exact'])]
class Booking extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( ["article", "booking", "booking_write"] )]
    private int $id;

    /**
     * @var DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    #[Groups( ["article", "booking", "booking_write"] )]
    private DateTime $createdAt;

    /**
     * @var DateTime|null
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups( ["article", "booking"] )]
    private ?DateTime $updatedAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    #[Groups( ["article", "booking", "booking_write"] )]
    private DateTime $bookingStart;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    #[Groups( ["article", "booking", "booking_write"] )]
    private DateTime $bookingEnd;


    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups( ["article", "booking", "booking_write"] )]
    private ?string $optionalData = null;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="bookings")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    #[Groups( ["booking_write", "article_full", "booking"] )]
    private Article $article;

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
     * @return Booking
     */
    public function setId(int $id) : Booking
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
     * @return Booking
     */
    public function setCreatedAt(DateTime $createdAt) : Booking
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
     * @return Booking
     */
    public function setUpdatedAt(?DateTime $updatedAt) : Booking
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getBookingStart() : DateTime
    {
        return $this->bookingStart;
    }

    /**
     * @param DateTime $bookingStart
     *
     * @return Booking
     */
    public function setBookingStart(DateTime $bookingStart) : Booking
    {
        $this->bookingStart = $bookingStart;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getBookingEnd() : DateTime
    {
        return $this->bookingEnd;
    }

    /**
     * @param DateTime $bookingEnd
     *
     * @return Booking
     */
    public function setBookingEnd(DateTime $bookingEnd) : Booking
    {
        $this->bookingEnd = $bookingEnd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOptionalData() : ?string
    {
        return $this->optionalData;
    }

    /**
     * @param string|null $optionalData
     *
     * @return Booking
     */
    public function setOptionalData(?string $optionalData) : Booking
    {
        $this->optionalData = $optionalData;

        return $this;
    }

    /**
     * @return Article
     */
    public function getArticle() : Article
    {
        return $this->article;
    }

    /**
     * @param Article $article
     *
     * @return Booking
     */
    public function setArticle(Article $article) : Booking
    {
        $this->article = $article;

        return $this;
    }
}
