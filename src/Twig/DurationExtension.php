<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DurationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_duration', [$this, 'formatDuration']),
            new TwigFilter('format_minutes', [$this, 'formatMinutes']),
        ];
    }

    public function formatDuration(int $seconds): string
    {
        $totalMinutes = ceil($seconds / 60); // Arrondi à la minute supérieure
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        $formatted = [];

        if ($hours > 0) {
            $formatted[] = sprintf('%d heure%s', $hours, $hours > 1 ? 's' : '');
        }

        if ($minutes > 0) {
            $formatted[] = sprintf('%d minute%s', $minutes, $minutes > 1 ? 's' : '');
        }

        return implode(' et ', $formatted);
    }

    public function formatMinutes(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        $formatted = [];

        if ($hours > 0) {
            $formatted[] = sprintf('%d hr%s', $hours, $hours > 1 ? 's' : '');
        }

        if ($minutes > 0) {
            $formatted[] = sprintf('%d min%s', $minutes, $minutes > 1 ? 's' : '');
        }

        if ($remainingSeconds > 0) {
            $formatted[] = sprintf('%d sec', $remainingSeconds);
        }

        return implode(' ', $formatted);
    }
}