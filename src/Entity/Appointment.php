<?php

namespace App\Entity;

use App\Entity\EvaluatedPerson;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AppointmentType $type = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(enumType: AppointmentStatus::class)]
    private AppointmentStatus $status = AppointmentStatus::PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Embedded(class: EvaluatedPerson::class)]
    #[Assert\Valid(groups: ['Default', 'couple'])]   // ✅ cascade sur les deux groupes
    private EvaluatedPerson $evaluatedPerson;

    #[ORM\Embedded(class: EvaluatedPerson::class, columnPrefix: 'partner_')]
    #[Assert\Valid(groups: ['couple'])]              // ✅ cascade seulement en couple
    private ?EvaluatedPerson $partner = null;

    #[ORM\Column(length: 191, nullable: true, unique: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isSent = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminder7SentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminder24SentAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Veuillez saisir une URL valide (https://...).')]
    private ?string $visioUrl = null;

    #[Assert\File(
        extensions: ['pdf'],
        extensionsMessage: 'Merci de télécharger un PDF valide'
    )]
    #[Vich\UploadableField(mapping: 'appointment_pdf', fileNameProperty: 'pdfName')]
    private ?File $pdfFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pdfUpdatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->evaluatedPerson = new EvaluatedPerson();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?AppointmentType
    {
        return $this->type;
    }

    public function setType(?AppointmentType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function setStatus(AppointmentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEvaluatedPerson(): EvaluatedPerson
    {
        return $this->evaluatedPerson;
    }

    public function setEvaluatedPerson(EvaluatedPerson $evaluatedPerson): static
    {
        $this->evaluatedPerson = $evaluatedPerson;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getPartner(): ?EvaluatedPerson
    {
        return $this->partner;
    }

    public function setPartner(?EvaluatedPerson $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    public function isSent(): ?bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): static
    {
        $this->isSent = $isSent;

        return $this;
    }

    #[Assert\Callback]
    public function validatePersons(ExecutionContextInterface $ctx): void
    {
        // Toujours vérifier la personne principale
        $p = $this->evaluatedPerson ?? null;
        if (!$p || !$p->getFirstname() || !$p->getLastname() || !$p->getPatronyms() || !$p->getBirthdate()) {
            $ctx->buildViolation('Tous les champs de la personne principale sont obligatoires.')
                ->atPath('evaluatedPerson.firstname')
                ->addViolation();
        }

        // Si type couple -> vérifier le partenaire
        $type = $this->getType();
        $isCouple = $type && (method_exists($type, 'isCouple') ? $type->isCouple() : ((int)$type->getParticipants() === 2));
        if ($isCouple) {
            $q = $this->partner ?? null;
            if (!$q || !$q->getFirstname() || !$q->getLastname() || !$q->getPatronyms() || !$q->getBirthdate()) {
                $ctx->buildViolation('Tous les champs du partenaire sont obligatoires.')
                    ->atPath('partner.firstname')
                    ->addViolation();
            }
        }
    }

    public function getReminder7SentAt(): ?\DateTimeImmutable
    {
        return $this->reminder7SentAt;
    }

    public function setReminder7SentAt(?\DateTimeImmutable $reminder7SentAt): static
    {
        $this->reminder7SentAt = $reminder7SentAt;

        return $this;
    }

    public function getReminder24SentAt(): ?\DateTimeImmutable
    {
        return $this->reminder24SentAt;
    }

    public function setReminder24SentAt(?\DateTimeImmutable $reminder24SentAt): static
    {
        $this->reminder24SentAt = $reminder24SentAt;

        return $this;
    }

    public function getVisioUrl(): ?string
    {
        return $this->visioUrl;
    }

    public function setVisioUrl(?string $visioUrl): static
    {
        $this->visioUrl = $visioUrl;

        return $this;
    }

    public function setPdfFile(?File $file = null): void
    {
        $this->pdfFile = $file;

        if (null !== $file) {
            $this->pdfUpdatedAt = new \DateTimeImmutable();
        }
    }

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function getPdfName(): ?string
    {
        return $this->pdfName;
    }

    public function setPdfName(?string $pdfName): void
    {
        $this->pdfName = $pdfName;
    }

    public function getPdfUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->pdfUpdatedAt;
    }

    public function setPdfUpdatedAt(?\DateTimeImmutable $date): void
    {
        $this->pdfUpdatedAt = $date;
    }
}
