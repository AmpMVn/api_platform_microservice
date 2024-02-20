<?php

namespace App\Entity\Settings\AttributeSet;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Entity\Client\Client;
use App\Repository\Settings\AttributeSet\GroupRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Settings\AttributeSet\GroupRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Loggable
 * @Table(name="settings_attribute_set_group")
 */
class Group extends AbstractEntity
{
    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["article", "settings_attribute_set"])]
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
    #[Groups(["article", "settings_attribute_set"])]
    private string $name;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    #[Groups(["article", "settings_attribute_set"])]
    private int $status = self::STATUS_ACTIVE;

    /**
     * @var Collection<int>
     * @ORM\OneToMany(targetEntity="App\Entity\Settings\AttributeSet\Entry", mappedBy="group")
     */
    #[Groups(["article", "settings_attribute_set"])]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Group
    {
        $this->id = $id;
        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): Group
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): Group
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): Group
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Group
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Group
    {
        $this->status = $status;
        return $this;
    }

    public function getEntries(): ArrayCollection|Collection
    {
        return $this->entries;
    }

    public function setEntries(ArrayCollection|Collection $entries): Group
    {
        $this->entries = $entries;
        return $this;
    }

}
