<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\AbstractEntity;
use App\Repository\Article\AccessoriesRepository;
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
 * @ORM\Entity(repositoryClass=App\Repository\Article\AccessoriesRepository::class)
 * @Table(name="article_accessories")
 */
class Accessories extends AbstractEntity
{
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
    #[Groups( [ "article_full" ] )]
    private int $maxCount = 1;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full" ] )]
    protected ?string $groupName = null;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( [ "article_full" ] )]
    protected int $priority;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_full" ] )]
    private bool $enableSingleSelectionRule = false;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_full" ] )]
    private bool $requiredMsOnlineBooking = false;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_full" ] )]
    private bool $enabledMsOnlineBooking = false;

    /**
     * @var bool
     * @ORM\Column (type="boolean", nullable=false, options={"default": false})
     */
    #[Groups( [ "article_full" ] )]
    private bool $takeoverInProcess = false;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="accessories")
     * @ORM\JoinColumn(name="article_id_parent", referencedColumnName="id")
     */
    private Article $articleParent;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article")
     * @ORM\JoinColumn(name="article_id_child", referencedColumnName="id")
     * @MaxDepth(1)
     */
    #[Groups( [ "article_full" ] )]
    private Article $articleChild;

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
     * @return Accessories
     */
    public function setId(int $id) : Accessories
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
     * @return Accessories
     */
    public function setCreatedAt(DateTime $createdAt) : Accessories
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
     * @return Accessories
     */
    public function setUpdatedAt(?DateTime $updatedAt) : Accessories
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxCount() : int
    {
        return $this->maxCount;
    }

    /**
     * @param int $maxCount
     *
     * @return Accessories
     */
    public function setMaxCount(int $maxCount) : Accessories
    {
        $this->maxCount = $maxCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequiredMsOnlineBooking() : bool
    {
        return $this->requiredMsOnlineBooking;
    }

    /**
     * @param bool $requiredMsOnlineBooking
     *
     * @return Accessories
     */
    public function setRequiredMsOnlineBooking(bool $requiredMsOnlineBooking) : Accessories
    {
        $this->requiredMsOnlineBooking = $requiredMsOnlineBooking;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledMsOnlineBooking() : bool
    {
        return $this->enabledMsOnlineBooking;
    }

    /**
     * @param bool $enabledMsOnlineBooking
     *
     * @return Accessories
     */
    public function setEnabledMsOnlineBooking(bool $enabledMsOnlineBooking) : Accessories
    {
        $this->enabledMsOnlineBooking = $enabledMsOnlineBooking;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTakeoverInProcess() : bool
    {
        return $this->takeoverInProcess;
    }

    /**
     * @param bool $takeoverInProcess
     *
     * @return Accessories
     */
    public function setTakeoverInProcess(bool $takeoverInProcess) : Accessories
    {
        $this->takeoverInProcess = $takeoverInProcess;

        return $this;
    }

    /**
     * @return Article
     */
    public function getArticleParent() : Article
    {
        return $this->articleParent;
    }

    /**
     * @param Article $articleParent
     *
     * @return Accessories
     */
    public function setArticleParent(Article $articleParent) : Accessories
    {
        $this->articleParent = $articleParent;

        return $this;
    }

    /**
     * @return Article
     */
    public function getArticleChild() : Article
    {
        return $this->articleChild;
    }

    /**
     * @param Article $articleChild
     *
     * @return Accessories
     */
    public function setArticleChild(Article $articleChild) : Accessories
    {
        $this->articleChild = $articleChild;

        return $this;
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
     * @return bool
     */
    public function isEnableSingleSelectionRule(): bool
    {
        return $this->enableSingleSelectionRule;
    }

    /**
     * @param bool $enableSingleSelectionRule
     */
    public function setEnableSingleSelectionRule(bool $enableSingleSelectionRule): void
    {
        $this->enableSingleSelectionRule = $enableSingleSelectionRule;
    }



}
