<?php

namespace App\Entity\Settings\AttributeSet;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\AbstractEntity;
use App\Repository\Settings\AttributeSet\EntryRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=App\Repository\Settings\AttributeSet\EntryRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @Gedmo\Loggable
 * @Table(name="settings_attribute_set_entry")
 */
class Entry extends AbstractEntity
{
    use SoftDeleteableEntity;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups( ["article", "settings_attribute_set"] )]
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
    #[Groups( ["article", "settings_attribute_set"] )]
    protected string $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( ["article", "settings_attribute_set"] )]
    protected string $type;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( ["article", "settings_attribute_set"] )]
    protected string $valueForSelect;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    #[Groups( ["article", "settings_attribute_set"] )]
    protected string $valueForInput;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default" : 1})
     */
    #[Groups( ["article", "settings_attribute_set"] )]
    protected int $priority = 1;

    /**
     * @var Group
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\AttributeSet\Group", inversedBy="entries")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected Group $group;


}
