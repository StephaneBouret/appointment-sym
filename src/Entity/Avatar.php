<?php

namespace App\Entity;

use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AvatarRepository;
use Doctrine\ORM\Mapping\PostUpdate;
use Doctrine\ORM\Mapping\PostPersist;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvatarRepository::class)]
#[HasLifecycleCallbacks]
#[Vich\Uploadable]
class Avatar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Image(
        maxSize: '2M',
        maxSizeMessage: 'L\'image est trop lourde ({{ size }} {{ suffix }}).
        Le maximum autorisé est {{ limit }} {{ suffix }}',
        minWidth: 100,
        minWidthMessage: 'La largeur de l\'image est trop petite ({{ width }}px).
        Le minimum est {{ min_width }}px.',
        minHeight: 100,
        minHeightMessage: 'La hauteur est trop faible ({{ height }}px).
        Le minimum est {{ min_height }}px.',
        mimeTypes: [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp'
        ],
        mimeTypesMessage: 'Le type MIME du fichier n\'est pas valide ({{ type }}). Les formats autorisés sont {{ types }}'
    )]
    #[Vich\UploadableField(mapping: 'avatars_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'avatar', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    public function __toString()
    {
        return $this->imageName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    #[PostPersist]
    #[PostUpdate]
    public function resize()
    {
        if (null === $this->imageFile) {
            return;
        }

        // create smaller image
        $width = 500;
        $height = 500;
        $imagine = new Imagine();
        $image = $imagine->open($this->imageFile->getRealPath());
        $size = $image->getSize();
        $image->resize($size->widen($width))
            ->resize($size->heighten($height));
        $realpath = $this->imageFile->getRealPath();
        $image->save($realpath);
    }

    public function createDefaultAvatar(string $initial, string $outputPath): void
    {
        $imagine = new Imagine();
        $size = new Box(720, 720);
        $palette = new RGB();
        $backgroundColor = $palette->color('#232323', 100);

        $image = $imagine->create($size, $backgroundColor);

        // Chemin absolu vers le fichier de police
        $fontPath = __DIR__ . '/../../assets/fonts/Roboto-Regular.ttf';

        // Vérification si le fichier de police existe
        if (!file_exists($fontPath)) {
            throw new \RuntimeException("Le fichier de police n'a pas été trouvé à l'emplacement : $fontPath");
        }

        $fontSize = 300;
        $fontColor = $palette->color('#FFFFFF', 100);

        $font = $imagine->font($fontPath, $fontSize, $fontColor);

        // Calcul des dimensions du texte pour le centrer
        $textBox = $font->box($initial);
        $x = ($size->getWidth() - $textBox->getWidth()) / 2;
        $y = ($size->getHeight() - $textBox->getHeight()) / 2;

        // Dessin du texte sur l'image
        $image->draw()->text($initial, $font, new Point($x, $y));

        // Enregistrement de l'image
        $image->save($outputPath);
    }

    // Méthodes de sérialisation pour PHP 7.4+ et compatibilité avec PHP 8
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'imageName' => $this->imageName,
            'updatedAt' => $this->updatedAt,
            'user' => $this->user
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->imageName = $data['imageName'];
        $this->updatedAt = $data['updatedAt'];
        $this->user = $data['user'];
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
