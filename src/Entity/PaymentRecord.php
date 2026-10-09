<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Nullable for legacy/manual appointments: absence is never evidence of payment. */
#[ORM\Embeddable]
class PaymentRecord
{
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $intentId = null;

    #[ORM\Column(nullable: true)]
    public ?int $amount = null;

    #[ORM\Column(length: 3, nullable: true)]
    public ?string $currency = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $attemptKey = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $issue = null;

    // Durable notification outbox, committed together with the confirmation.
    #[ORM\Column(length: 16, nullable: true)]
    public ?string $notificationState = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $notificationError = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $notificationAttemptedAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $notificationSentAt = null;
}
