<?php

namespace App\Entity\Settings;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Article\Article;
use App\Entity\Client\Client;
use App\Repository\Settings\CategoryRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Settings\CategoryRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Tree(type="nested")
 * @Gedmo\Loggable
 * @Table(name="settings_category")
 */
#[ApiResource(
    normalizationContext: ['groups' => ['settings_category', 'article_minimum']],
    shortName: "Settings/Categories",
    collectionOperations: ['get'],
    itemOperations: ['get'],
    order: ["name" => "ASC"],
)]
#[ApiFilter(SearchFilter::class, properties: ['clientId' => 'exact', 'parent' => 'exact', 'enableOnlineBooking' => 'exact'])]
class Category extends AbstractEntity
{
    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article_minimum", "settings_category"])]
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
    #[Groups(["article_minimum", "settings_category"])]
    private string $name;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\Versioned
     */
    #[Groups(["settings_category"])]
    private int $lft;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\Versioned
     */
    #[Groups(["settings_category"])]
    private int $lvl;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\Versioned
     */
    #[Groups(["settings_category"])]
    private int $rgt;

    /**
     * @var Category
     * @MaxDepth(2)
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    #[Groups(["settings_category"])]
    private Category $root;

    /**
     * @var null|Category
     * @MaxDepth(2)
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    private ?Category $parent;

    /**
     * @var Collection<int, Category>
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ORM\OrderBy({"lft": "ASC"})
     * @ApiSubresource
     */
    #[Groups(["settings_category"])]
    private Collection $children;

    /**
     * @var Collection<int,Article>
     * @ORM\OneToMany(targetEntity="App\Entity\Article\Article", mappedBy="category")
     * @ORM\OrderBy({"id": "ASC"})
     */
    private ?Collection $articles;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    #[Groups(["article_minimum", "settings_category"])]
    private bool $enableOnlineBooking = false;

    /**
     * @param DateTime|array $createdAt
     */
    public function setCreatedAt(DateTime|array $createdAt): void
    {
        if(is_array($createdAt)) {
            $createdAt = new DateTime($createdAt['date']);
        }

        $this->createdAt = $createdAt;
    }

    /**
     * @param DateTime|array|null $updatedAt
     */
    public function setUpdatedAt(DateTime|array|null $updatedAt): void
    {
        if(is_array($updatedAt)) {
            $updatedAt = new DateTime($updatedAt['date']);
        }

        $this->updatedAt = $updatedAt;
    }


    public function __construct()
    {
        $this->articles = new ArrayCollection();
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
     * @return Category
     */
    public function setId(int $id): Category
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
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
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
     * @return Category
     */
    public function setName(string $name): Category
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getLft(): int
    {
        return $this->lft;
    }

    /**
     * @param int $lft
     *
     * @return Category
     */
    public function setLft(int $lft): Category
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * @return int
     */
    public function getLvl(): int
    {
        return $this->lvl;
    }

    /**
     * @param int $lvl
     *
     * @return Category
     */
    public function setLvl(int $lvl): Category
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @return int
     */
    public function getRgt(): int
    {
        return $this->rgt;
    }

    /**
     * @param int $rgt
     *
     * @return Category
     */
    public function setRgt(int $rgt): Category
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * @return Category
     */
    public function getRoot(): Category
    {
        return $this->root;
    }

    /**
     * @param Category $root
     *
     * @return Category
     */
    public function setRoot(Category $root): Category
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * @param Category|null $parent
     *
     * @return Category
     */
    public function setParent(?Category $parent): Category
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection $children
     *
     * @return Category
     */
    public function setChildren(Collection $children): Category
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getArticles(): ArrayCollection|Collection|null
    {
        return $this->articles;
    }

    /**
     * @param Collection $articles
     *
     * @return Category
     */
    public function setArticles(ArrayCollection|Collection|null $articles): Category
    {
        $this->articles = $articles;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnableOnlineBooking(): bool
    {
        return $this->enableOnlineBooking;
    }

    /**
     * @param bool $enableOnlineBooking
     *
     * @return Category
     */
    public function setEnableOnlineBooking(bool $enableOnlineBooking): Category
    {
        $this->enableOnlineBooking = $enableOnlineBooking;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Category
    {
        $this->clientId = $clientId;
        return $this;
    }

}
