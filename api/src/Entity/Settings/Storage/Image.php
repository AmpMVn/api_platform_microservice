<?php

namespace App\Entity\Settings\Storage;

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
 * @ORM\Entity(repositoryClass=App\Repository\Settings\Storage\ImageRepository::class)
 * @Table(name="settings_storage_image")
 */
#[ApiResource(
    shortName: 'Settings/Storage/Image',
    collectionOperations: [ 'get' ],
    itemOperations: [ 'get' ],
)]
class Image extends AbstractEntity
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["settings_storage"])]
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
    #[Groups(["settings_storage"])]
    private string $filepath;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    private float $filesize;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    #[Groups(["settings_storage"])]
    private bool $mainImage = false;

    /**
     * @var Storage
     * @ORM\ManyToOne(targetEntity="App\Entity\Settings\Storage\Storage", inversedBy="images")
     * @ORM\JoinColumn(name="stoage_id", referencedColumnName="id")
     */
    private Storage $storage;

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
     * @return Storage
     */
    public function getStorage() : Storage
    {
        return $this->storage;
    }

    /**
     * @param Storage $storage
     *
     * @return Image
     */
    public function setStorage(Storage $storage) : Image
    {
        $this->storage = $storage;

        return $this;
    }



}
