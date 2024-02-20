<?php

namespace App\Entity\Article;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Settings\Location;
use App\Repository\Article\AttributeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Table;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Article\AttributeRepository::class)
 * @Gedmo\Loggable
 * @Table(name="article_attribute")
 */
class Attribute extends AbstractEntity implements Translatable
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
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
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
    protected string $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
    protected string $type;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
    protected string $value;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
    protected int $priority;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    #[Groups( [ "article_full", "article_minimum" ] )]
    protected ?string $icon = null;

    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="App\Entity\Article\Article", inversedBy="attributes")
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
     * @return Attribute
     */
    public function setId(int $id) : Attribute
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
     * @return Attribute
     */
    public function setCreatedAt(DateTime $createdAt) : Attribute
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
     * @return Attribute
     */
    public function setUpdatedAt(?DateTime $updatedAt) : Attribute
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Attribute
     */
    public function setName(string $name) : Attribute
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Attribute
     */
    public function setType(string $type) : Attribute
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Attribute
     */
    public function setValue(string $value) : Attribute
    {
        $this->value = $value;

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
     * @return Attribute
     */
    public function setArticle(Article $article) : Attribute
    {
        $this->article = $article;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon() : ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     *
     * @return Attribute
     */
    public function setIcon(?string $icon) : Attribute
    {
        $this->icon = $icon;

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


}
