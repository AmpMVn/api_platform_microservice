<?php

namespace App\Entity\Microservice\Article;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Client\Client;
use App\Repository\Microservice\Article\OnlineBookingRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Microservice\Article\OnlineBookingRepository::class)
 * @Table(name="microservice_article_online_booking")
 */
class OnlineBooking extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $msOnlineBookingId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="msOnlineBookings")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    private $article;

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
     * @return OnlineBooking
     */
    public function setId(int $id) : OnlineBooking
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getMsOnlineBookingId() : int
    {
        return $this->msOnlineBookingId;
    }

    /**
     * @param int $msOnlineBookingId
     *
     * @return OnlineBooking
     */
    public function setMsOnlineBookingId(int $msOnlineBookingId) : OnlineBooking
    {
        $this->msOnlineBookingId = $msOnlineBookingId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param mixed $article
     *
     * @return OnlineBooking
     */
    public function setArticle($article)
    {
        $this->article = $article;

        return $this;
    }


}
