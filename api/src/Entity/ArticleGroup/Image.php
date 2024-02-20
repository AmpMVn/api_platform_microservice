<?php

namespace App\Entity\ArticleGroup;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\AbstractEntity;
use App\Repository\ArticleGroup\ImageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ArticleGroup\ImageRepository::class)
 * @Table(name="article_group_image")
 */
class Image extends AbstractEntity
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article_group"])]
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
    #[Groups(["article_group"])]
    private string $filepath;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    #[Groups(["article_group"])]
    private float $filesize;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    #[Groups(["article_group"])]
    private bool $mainImage = false;

    /**
     * @var ArticleGroup
     * @ORM\ManyToOne(targetEntity="App\Entity\ArticleGroup\ArticleGroup", inversedBy="images")
     */
    private ArticleGroup $articleGroup;

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
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * @param string $filepath
     */
    public function setFilepath(string $filepath): void
    {
        $this->filepath = $filepath;
    }

    /**
     * @return float
     */
    public function getFilesize(): float
    {
        return $this->filesize;
    }

    /**
     * @param float $filesize
     */
    public function setFilesize(float $filesize): void
    {
        $this->filesize = $filesize;
    }

    /**
     * @return bool
     */
    public function isMainImage(): bool
    {
        return $this->mainImage;
    }

    /**
     * @param bool $mainImage
     */
    public function setMainImage(bool $mainImage): void
    {
        $this->mainImage = $mainImage;
    }

    /**
     * @return ArticleGroup
     */
    public function getArticleGroup(): ArticleGroup
    {
        return $this->articleGroup;
    }

    /**
     * @param ArticleGroup $articleGroup
     */
    public function setArticleGroup(ArticleGroup $articleGroup): void
    {
        $this->articleGroup = $articleGroup;
    }


}
