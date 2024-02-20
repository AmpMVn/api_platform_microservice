<?php

namespace App\Entity\ArticleGroup;

use App\Entity\AbstractEntity;
use App\Repository\ArticleGroup\AttributeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ArticleGroup\AttributeRepository::class)
 * @Gedmo\Loggable
 * @Table(name="article_group_attribute")
 */
class Attribute extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_group" ] )]
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
    #[Groups( [ "article_group" ] )]
    protected string $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_group" ] )]
    protected string $type;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_group" ] )]
    protected string $value;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( [ "article_group" ] )]
    protected int $priority;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_group" ] )]
    protected ?string $icon = null;

    /**
     * @var ArticleGroup
     * @ORM\ManyToOne(targetEntity="App\Entity\ArticleGroup\ArticleGroup", inversedBy="attributes")
     */
    protected ArticleGroup $articleGroup;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
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
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     */
    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
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
