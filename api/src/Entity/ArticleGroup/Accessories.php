<?php

namespace App\Entity\ArticleGroup;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Repository\ArticleGroup\AccessoriesRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ArticleGroup\AccessoriesRepository::class)
 * @Table(name="article_group_accessories")
 */
class Accessories extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_group" ] )]
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
     * @ORM\Column (type="integer", nullable=false, options={"default": 1})
     */
    #[Groups( [ "article_group" ] )]
    private int $maxCount = 1;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_group" ] )]
    private ?string $groupName = null;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( [ "article_group" ] )]
    private int $priority;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_group" ] )]
    private bool $requiredMsOnlineBooking = false;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_group" ] )]
    private bool $enabledMsOnlineBooking = false;

    /**
     * @var ArticleGroup
     * @ORM\ManyToOne(targetEntity="App\Entity\ArticleGroup\ArticleGroup", inversedBy="accessories")
     * @ORM\JoinColumn(name="article_group_id", referencedColumnName="id")
     */
    private ArticleGroup $articleGroup;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article")
     * @ORM\JoinColumn(name="article_child_id", referencedColumnName="id")
     * @MaxDepth(1)
     */
    private Article $articleChild;

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
     * @return int
     */
    public function getMaxCount(): int
    {
        return $this->maxCount;
    }

    /**
     * @param int $maxCount
     */
    public function setMaxCount(int $maxCount): void
    {
        $this->maxCount = $maxCount;
    }

    /**
     * @return string|null
     */
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @param string|null $groupName
     */
    public function setGroupName(?string $groupName): void
    {
        $this->groupName = $groupName;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return bool
     */
    public function isRequiredMsOnlineBooking(): bool
    {
        return $this->requiredMsOnlineBooking;
    }

    /**
     * @param bool $requiredMsOnlineBooking
     */
    public function setRequiredMsOnlineBooking(bool $requiredMsOnlineBooking): void
    {
        $this->requiredMsOnlineBooking = $requiredMsOnlineBooking;
    }

    /**
     * @return bool
     */
    public function isEnabledMsOnlineBooking(): bool
    {
        return $this->enabledMsOnlineBooking;
    }

    /**
     * @param bool $enabledMsOnlineBooking
     */
    public function setEnabledMsOnlineBooking(bool $enabledMsOnlineBooking): void
    {
        $this->enabledMsOnlineBooking = $enabledMsOnlineBooking;
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

    /**
     * @return Article
     */
    public function getArticleChild(): Article
    {
        return $this->articleChild;
    }

    /**
     * @param Article $articleChild
     */
    public function setArticleChild(Article $articleChild): void
    {
        $this->articleChild = $articleChild;
    }



}
