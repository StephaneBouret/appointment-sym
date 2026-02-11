<?php

namespace App\Entity;

use App\Repository\UnavailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UnavailabilityRepository::class)]
class Unavailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Début et fin (sur une même journée)
    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'La date/heure de début est obligatoire.')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'La date/heure de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'startAt',
        message: "L'heure de fin doit être strictement postérieure à l'heure de début"
    )]
    private ?\DateTimeImmutable $endAt = null;

    // Coche "journée entière" => on normalise (00:00:00 / 23:59:59)
    #[ORM\Column(type: 'boolean')]
    private ?bool $allDay = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    #[Assert\Callback()]
    public function validateSameDay(ExecutionContextInterface $context, mixed $payload): void
    {
        if (!$this->startAt || !$this->endAt) return;

        $paris = new \DateTimeZone('Europe/Paris');
        $s = $this->startAt->setTimezone($paris);
        $e = $this->endAt->setTimezone($paris);

        if ($s->format('Y-m-d') !== $e->format('Y-m-d')) {
            $context->buildViolation("L'indisponibilité doit se situer sur une seule journée")
                ->atPath('endAt') // cible le champ "fin"
                ->addViolation();
        }
    }
}
