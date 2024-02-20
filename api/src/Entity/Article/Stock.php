<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Settings\Storage\Storage;
use App\Repository\Article\StockRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Article\StockRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Loggable
 * @Table(name="article_stock")
 */
#[ApiResource(
    shortName: 'Article/Stocks',
)]
class Stock extends AbstractEntity
{
    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_full" ] )]
    private int $id;

    /**
     * @var DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    #[Groups( [ "article_full" ] )]
    private DateTime $createdAt;

    /**
     * @var DateTime|null
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    private ?DateTime $updatedAt;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    private ?string $refrenceNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    private ?string $serialCode = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    private ?string $description = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    private ?string $codeContent = null;

    /**
     * @var int
     * @ORM\Column (type="smallint", nullable=false)
     */
    #[Groups( [ "article_full" ] )]
    private int $status = 0;

    /**
     * @var Storage
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Storage\Storage", inversedBy="stocks")
     * @ORM\JoinColumn(name="storage_id", referencedColumnName="id")
     */
    #[Groups( [ "article_full" ] )]
    private Storage $storage;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="stocks")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
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
     */
    public function setId(int $id) : void
    {
        $this->id = $id;
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
     */
    public function setCreatedAt(DateTime $createdAt) : void
    {
        $this->createdAt = $createdAt;
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
     */
    public function setUpdatedAt(?DateTime $updatedAt) : void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string|null
     */
    public function getRefrenceNumber() : ?string
    {
        return $this->refrenceNumber;
    }

    /**
     * @param string|null $refrenceNumber
     */
    public function setRefrenceNumber(?string $refrenceNumber) : void
    {
        $this->refrenceNumber = $refrenceNumber;
    }

    /**
     * @return string|null
     */
    public function getSerialCode() : ?string
    {
        return $this->serialCode;
    }

    /**
     * @param string|null $serialCode
     */
    public function setSerialCode(?string $serialCode) : void
    {
        $this->serialCode = $serialCode;
    }

    /**
     * @return string|null
     */
    public function getDescription() : ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description) : void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getCodeContent() : ?string
    {
        return $this->codeContent;
    }

    /**
     * @param string|null $codeContent
     */
    public function setCodeContent(?string $codeContent) : void
    {
        $this->codeContent = $codeContent;
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status) : void
    {
        $this->status = $status;
    }

    /**
     * @return Storage
     */
    public function getStorage() : Storage
    {
        return $this->storage;
    }

    /**
     * @param Storage $storage
     */
    public function setStorage(Storage $storage) : void
    {
        $this->storage = $storage;
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
     */
    public function setArticle(Article $article) : void
    {
        $this->article = $article;
    }



}
