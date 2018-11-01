<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Upload;
use App\Service\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Table(name="`file`")
 * @ORM\Entity
 * @Vich\Uploadable
 * @ApiResource(
 *     attributes={"access_control"="is_granted('ROLE_USER')"},
 *     itemOperations={
 *          "get",
 * 			"delete",
 *          "put",
 *          "post"={
 *              "method"="POST",
 *              "path"="/files/upload",
 *              "controller"=Upload::class,
 *              "defaults"={"_api_receive"=false},
 *          },
 *     },
 * )
 */
class File
{
    /* Statuses are :
     *      - raw: file was just uploaded and not modified
     *      - ready: file is in its final form before beeing used.
     */
    const STATUS_RAW = "raw";
    const STATUS_READY = "ready";

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Groups({"read_user", "read_message"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Type("integer")
     * @Assert\NotNull()
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string")
     * @Groups({"read_user", "read_message"})
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Groups({"read_user", "read_message"})
     */
    private $status;

    /**
     * @Assert\NotNull()
     * @Vich\UploadableField(mapping="files", fileNameProperty="contentUrl", size="size", mimeType="type")
     */
    private $file;

    /**
     * @ORM\Column(type="string")
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"read_user", "read_message"})
     */
    private $contentUrl;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_user", "read_message"})
     */
    private $fileIndex;

    public function __construct()
    {
        $this->id = Uuid::uuidv4();
        $this->createdAt = time();
        $this->status = self::STATUS_READY;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    // TODO
    public function getPath(): string
    {
        return "/files/".$this->getContentUrl();
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     */
    public function setFile($file = null): self
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->status = self::STATUS_UPLOADED;
        }
        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getContentUrl(): string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(string $contentUrl): self
    {
        $this->contentUrl = $contentUrl;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getFileIndex(): int
    {
        return $this->fileIndex ?? 0;
    }

    public function setFileIndex(int $fileIndex): self
    {
        $this->fileIndex = $fileIndex;
        return $this;
    }
}
