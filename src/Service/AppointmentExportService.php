<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\HttpFoundation\Response;

final class AppointmentExportService
{
    /** @param Appointment[] $appointments */
    public function streamCsv(array $appointments, string $suffix = 'export'): Response
    {
        $filename = sprintf(
            'appointments_%s_%s.csv',
            (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Ymd_His'),
            $suffix
        );

        return $this->prepare(function ($out) use ($appointments) {
            $this->writeCsv($out, ['Subject', 'Start Date', 'Start Time', 'End Date', 'End Time', 'Description']);
            $tz = new \DateTimeZone('Europe/Paris');

            foreach ($appointments as $a) {
                $subject = $a->getType()?->getName() ?? 'Rendez-vous';
                $start   = $a->getStartAt()?->setTimezone($tz);
                $end     = $a->getEndAt()?->setTimezone($tz);

                $clientTxt = $a->getUser()
                    ? sprintf('Client : %s %s', $a->getUser()->getFirstName() ?? '', $a->getUser()->getLastName() ?? '')
                    : '';

                $p1 = $a->getEvaluatedPerson();
                $p1Txt = $p1 ? sprintf(
                    'Personne : %s %s%s, naissance %s',
                    $p1->getFirstname() ?? '',
                    $p1->getLastname() ?? '',
                    $p1->getPatronyms() ? ' (' . $p1->getPatronyms() . ')' : '',
                    $p1->getBirthdate() ? $p1->getBirthdate()->format('d/m/Y') : '—'
                ) : '';

                $p2Txt = '';
                $type = $a->getType();
                $isCouple = $type && ($type->getSlug() === 'analyse-couple' || $type->getId() === 6);
                if ($isCouple && $a->getPartner()) {
                    $p2 = $a->getPartner();
                    $p2Txt = sprintf(
                        'Partenaire : %s %s%s, naissance %s',
                        $p2->getFirstname() ?? '',
                        $p2->getLastname() ?? '',
                        $p2->getPatronyms() ? ' (' . $p2->getPatronyms() . ')' : '',
                        $p2->getBirthdate() ? $p2->getBirthdate()->format('d/m/Y') : '—'
                    );
                }

                $desc = trim(implode(' | ', array_filter([$clientTxt, $p1Txt, $p2Txt])));

                $this->writeCsv($out, [
                    $subject,
                    $start?->format('m/d/Y') ?? '',
                    $start?->format('H:i') ?? '',
                    $end?->format('m/d/Y') ?? '',
                    $end?->format('H:i') ?? '',
                    $desc
                ]);
            }
        }, 'text/csv; charset=UTF-8', $filename);
    }

    /** @param Appointment[] $appointments */
    public function streamIcs(array $appointments, string $suffix = 'export'): Response
    {
        $filename = sprintf(
            'appointments_%s_%s.ics',
            (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Ymd_His'),
            $suffix
        );

        return $this->prepare(function ($out) use ($appointments) {
            $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $w = fn(string $s) => $this->writeAll($out, $s);

            $w("BEGIN:VCALENDAR\r\n");
            $w("PRODID:-//App Numerologie//Appointments Export//FR\r\n");
            $w("VERSION:2.0\r\n");
            $w("CALSCALE:GREGORIAN\r\n");
            $w("METHOD:PUBLISH\r\n");

            foreach ($appointments as $a) {
                $summary = $this->icsEscape($a->getType()?->getName() ?? 'Rendez-vous');
                $dtStartUtc = $a->getStartAt()?->setTimezone(new \DateTimeZone('UTC'));
                $dtEndUtc   = $a->getEndAt()?->setTimezone(new \DateTimeZone('UTC'));
                if (!$dtStartUtc || !$dtEndUtc) {
                    throw new \UnexpectedValueException('Impossible d’exporter un rendez-vous sans dates en ICS.');
                }

                $clientTxt = $a->getUser()
                    ? sprintf('Client : %s %s', $a->getUser()->getFirstName() ?? '', $a->getUser()->getLastName() ?? '')
                    : '';

                $p1 = $a->getEvaluatedPerson();
                $p1Txt = $p1 ? sprintf(
                    'Personne : %s %s%s, naissance %s',
                    $p1->getFirstname() ?? '',
                    $p1->getLastname() ?? '',
                    $p1->getPatronyms() ? ' (' . $p1->getPatronyms() . ')' : '',
                    $p1->getBirthdate() ? $p1->getBirthdate()->format('d/m/Y') : '—'
                ) : '';

                $p2Txt = '';
                $type = $a->getType();
                $isCouple = $type && ($type->getSlug() === 'analyse-couple' || $type->getId() === 6);
                if ($isCouple && $a->getPartner()) {
                    $p2 = $a->getPartner();
                    $p2Txt = sprintf(
                        'Partenaire : %s %s%s, naissance %s',
                        $p2->getFirstname() ?? '',
                        $p2->getLastname() ?? '',
                        $p2->getPatronyms() ? ' (' . $p2->getPatronyms() . ')' : '',
                        $p2->getBirthdate() ? $p2->getBirthdate()->format('d/m/Y') : '—'
                    );
                }

                $desc = $this->icsEscape(trim(implode(' | ', array_filter([$clientTxt, $p1Txt, $p2Txt]))));
                $uid  = sprintf('appointment-%d@luniversdesnombres.com', $a->getId());

                $w("BEGIN:VEVENT\r\n");
                $w("UID:{$uid}\r\n");
                $w("DTSTAMP:".$this->icsDateUtc($nowUtc)."\r\n");
                $w("DTSTART:".$this->icsDateUtc($dtStartUtc)."\r\n");
                $w("DTEND:".$this->icsDateUtc($dtEndUtc)."\r\n");
                $w("SUMMARY:{$summary}\r\n");
                if ($desc !== '') { $w("DESCRIPTION:{$desc}\r\n"); }
                $w("END:VEVENT\r\n");
            }

            $w("END:VCALENDAR\r\n");
        }, 'text/calendar; charset=UTF-8', $filename);
    }

    // --------------- Helpers ---------------
    /** @param \Closure(resource): void $writer */
    private function prepare(\Closure $writer, string $contentType, string $filename): Response
    {
        // The selection is already materialized. Prepare it before any HTTP output or marking.
        $out = fopen('php://memory', 'w+');
        if ($out === false) {
            throw new \RuntimeException('Impossible de préparer l’export calendrier.');
        }
        try {
            $writer($out);
            if (!fflush($out)) {
                throw new \RuntimeException('Impossible de finaliser l’export calendrier.');
            }
            $size = ftell($out);
            if ($size === false || !rewind($out)) {
                throw new \RuntimeException('Impossible de relire l’export calendrier.');
            }
            $content = stream_get_contents($out);
            if ($content === false || strlen($content) !== $size) {
                throw new \RuntimeException('Lecture incomplète de l’export calendrier.');
            }
            if (!fclose($out)) {
                throw new \RuntimeException('Impossible de fermer l’export calendrier.');
            }
        } finally {
            if (is_resource($out)) {
                try {
                    fclose($out);
                } catch (\Throwable) {
                    // Cleanup must never replace the original generation/close exception.
                }
            }
        }
        $response = new Response($content);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    /** @param resource $out
     *  @param list<string> $fields
     */
    private function writeCsv($out, array $fields): void
    {
        $before = ftell($out);
        // Explicitly preserve PHP's historical comma, quote, backslash and LF defaults.
        $written = fputcsv($out, $fields, ',', '"', '\\', "\n");
        $after = ftell($out);
        if ($before === false || $written === false || $written === 0 || $after === false || $after - $before !== $written) {
            throw new \RuntimeException('Impossible de sérialiser une ligne CSV complète.');
        }
    }

    /** @param resource $out */
    private function writeAll($out, string $text): void
    {
        $offset = 0;
        $length = strlen($text);
        while ($offset < $length) {
            $written = fwrite($out, substr($text, $offset));
            if ($written === false || $written === 0) {
                throw new \RuntimeException('Impossible d’écrire l’export calendrier.');
            }
            $offset += $written;
        }
    }

    private function icsEscape(string $text): string
    {
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace("\r", "", $text);
        $text = str_replace(",", "\\,", $text);
        $text = str_replace(";", "\\;", $text);
        return $text;
    }

    private function icsDateUtc(\DateTimeImmutable $dt): string
    {
        return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }
}
