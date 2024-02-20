<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\AbstractEntity;
use App\Repository\Article\ImageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Article\ImageRepository::class)
 * @Table(name="article_image")
 */
class Image extends AbstractEntity
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_minimum", "article_full" ] )]
    protected int $id;

    /**
     * @var DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;

    /**
     * @var DateTime|null
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $updatedAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_minimum", "article_full" ] )]
    protected string $filepath;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    #[Groups( [ "article_minimum", "article_full" ] )]
    protected float $filesize;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    #[Groups( [ "article_minimum", "article_full" ] )]
    protected bool $mainImage = false;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="images")
     */
    protected Article $article;

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
     * @return Image
     */
    public function setId(int $id) : Image
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
     * @return Image
     */
    public function setCreatedAt(DateTime $createdAt) : Image
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
     * @return Image
     */
    public function setUpdatedAt(?DateTime $updatedAt) : Image
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilepath() : string
    {
        return $this->filepath;
    }

    /**
     * @param string $filepath
     *
     * @return Image
     */
    public function setFilepath(string $filepath) : Image
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * @return float
     */
    public function getFilesize() : float
    {
        return $this->filesize;
    }

    /**
     * @param float $filesize
     *
     * @return Image
     */
    public function setFilesize(float $filesize) : Image
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMainImage() : bool
    {
        return $this->mainImage;
    }

    /**
     * @param bool $mainImage
     *
     * @return Image
     */
    public function setMainImage(bool $mainImage) : Image
    {
        $this->mainImage = $mainImage;

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
     * @return Image
     */
    public function setArticle(Article $article) : Image
    {
        $this->article = $article;

        return $this;
    }


}
