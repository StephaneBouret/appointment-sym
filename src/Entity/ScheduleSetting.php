<?php

namespace App\Entity;

use App\Repository\ScheduleSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ScheduleSettingRepository::class)]
class ScheduleSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank()]
    private ?string $setting_key = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): ?string
    {
        return $this->setting_key;
    }

    public function setSettingKey(string $setting_key): static
    {
        $this->setting_key = $setting_key;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    #[Assert\Callback]
    public function validateValueByKey(ExecutionContextInterface $context, mixed $payload): void
    {
        $key = $this->getSettingKey();
        $val = $this->getValue();

        if ($key === null || $val === null) {
            return;
        }

        switch ($key) {
            case 'fixed_slots':
                if (!in_array($val, ['0', '1'], true)) {
                    $context->buildViolation('fixed_slots doit avoir 0 ou 1.')
                        ->atPath('value')->addViolation();
                }
                break;

            case 'slot_buffer_minutes':
                if (!ctype_digit($val)) {
                    $context->buildViolation('slot_buffer_minutes doit être un entier positif (minutes).')
                        ->atPath('value')->addViolation();
                    break;
                }
                $int = (int) $val;
                if ($int < 0 || $int > 240) {
                    $context->buildViolation('slot_buffer_minutes doit être compris entre 0 et 240 minutes.')
                        ->atPath('value')->addViolation();
                }
                break;

            case 'morning_start':
            case 'morning_end':
            case 'afternoon_start':
            case 'afternoon_end':
                if (!preg_match('/^\d{2}:\d{2}$/', $val)) {
                    $context->buildViolation(sprintf('%s doit être au format HH:MM.', $key))
                        ->atPath('value')->addViolation();
                }
                break;

            case 'open_days':
                if (!preg_match('/^[1-7](?:,[1-7]){0,6}$/', $val)) {
                    $context->buildViolation('open_days doit être une liste de 1..7 séparés par des virgules (ex: 1,2,3,4,5')
                        ->atPath('value')->addViolation();
                }
                break;

            default:
                break;
        }
    }
}
