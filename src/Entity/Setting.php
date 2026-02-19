<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'setting')]
#[ORM\UniqueConstraint(name: 'uniq_setting_key', columns: ['setting_key'])]
class Setting
{
    public const KEY_MAINTENANCE = 'maintenance';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'setting_key', length: 64, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $value = false;

    public function __construct(string $settingKey, bool $value = false)
    {
        $this->settingKey = $settingKey;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): self
    {
        $this->settingKey = $settingKey;
        return $this;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): self
    {
        $this->value = $value;
        return $this;
    }
}
