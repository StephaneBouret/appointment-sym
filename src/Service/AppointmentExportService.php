<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AppointmentExportService
{
    /** @param Appointment[] $appointments */
    public function streamCsv(array $appointments, string $suffix = 'export'): StreamedResponse
    {
        $filename = sprintf(
            'appointments_%s_%s.csv',
            (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Ymd_His'),
            $suffix
        );

        return $this->stream(function () use ($appointments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Subject', 'Start Date', 'Start Time', 'End Date', 'End Time', 'Description']);
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

                fputcsv($out, [
                    $subject,
                    $start?->format('m/d/Y') ?? '',
                    $start?->format('H:i') ?? '',
                    $end?->format('m/d/Y') ?? '',
                    $end?->format('H:i') ?? '',
                    $desc
                ]);
            }
            fclose($out);
        }, 'text/csv; charset=UTF-8', $filename);
    }

    /** @param Appointment[] $appointments */
    public function streamIcs(array $appointments, string $suffix = 'export'): StreamedResponse
    {
        $filename = sprintf(
            'appointments_%s_%s.ics',
            (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Ymd_His'),
            $suffix
        );

        return $this->stream(function () use ($appointments) {
            $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $w = static fn(string $s) => print $s;

            $w("BEGIN:VCALENDAR\r\n");
            $w("PRODID:-//App Numerologie//Appointments Export//FR\r\n");
            $w("VERSION:2.0\r\n");
            $w("CALSCALE:GREGORIAN\r\n");
            $w("METHOD:PUBLISH\r\n");

            foreach ($appointments as $a) {
                $summary = $this->icsEscape($a->getType()?->getName() ?? 'Rendez-vous');
                $dtStartUtc = $a->getStartAt()?->setTimezone(new \DateTimeZone('UTC'));
                $dtEndUtc   = $a->getEndAt()?->setTimezone(new \DateTimeZone('UTC'));
                if (!$dtStartUtc || !$dtEndUtc) continue;

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
    private function stream(\Closure $callback, string $contentType, string $filename): StreamedResponse
    {
        $response = new StreamedResponse($callback);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
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
