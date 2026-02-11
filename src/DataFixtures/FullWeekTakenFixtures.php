<?php

namespace App\DataFixtures;

use App\Entity\Appointment;
use DateTimeZone;
use App\Entity\AppointmentType;
use App\Entity\EvaluatedPerson;
use App\Enum\AppointmentStatus;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Repository\AppointmentTypeRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

final class FullWeekTakenFixtures extends Fixture implements FixtureGroupInterface
{
    // Id du type de RDV à tester
    private const TYPE_ID = 3;

    public function __construct(private AppointmentTypeRepository $typeRepo, private UserRepository $userRepo) {}

    public static function getGroups(): array
    {
        return ['fullWeek'];
    }

    public function load(ObjectManager $em): void
    {
        $tzParis = new DateTimeZone('Europe/Paris');
        $tzUtc = new DateTimeZone('UTC');

        // Récupération du type & user
        /** @var AppointmentType|null $type */
        $type = $this->typeRepo->find(self::TYPE_ID);
        if (!$type) {
            throw new \RuntimeException(sprintf('AppointmentType #%d introuvable.', self::TYPE_ID));
        }

        $user = $this->userRepo->findOneBy([]); // Le premier user trouvé
        if (!$user) {
            throw new \RuntimeException('Aucun utilisateur trouvé pour affecter les RDV.');
        }

        // Paramètres
        // Lundi-Vendredi / 09:00-12:00 et 14:00-18:00
        // Créneaux fixes: durée = 60 minutes, buffer = 15 min => démarrages: 09:00, 10:15, 14:00, 15:15, 16:30
        $durationMinutes = 60;
        $bufferMinutes = 15;

        // Fenêtre: aujourd'hui -> +6 jours (semaine glissante de 7 jours)
        $todayParis = (new DateTimeImmutable("now", $tzParis))->setTime(0, 0, 0, 0);
        $endParis = $todayParis->modify('+6 days');

        // Génération
        $created = 0;

        for ($d = $todayParis; $d <= $endParis; $d = $d->modify('+1 day')) {
            $dow = (int) $d->format('N'); // 1=Lundi... 7=dimanche
            if ($dow >= 6) {
                continue; // on saute samedi/dimanche
            }

            // Matin 09:00-12:00
            $created += $this->createDaySlots($em, $user, $type, $d, '09:00', '12:00', $durationMinutes, $bufferMinutes, $tzUtc);

            // Après-midi 14:00-18:00
            $created += $this->createDaySlots($em, $user, $type, $d, '14:00', '18:00', $durationMinutes, $bufferMinutes, $tzUtc);
        }

        $em->flush();

        echo sprintf("Fixtures: %d rendez-vous créés (semaine pleine).\n", $created);
    }

    /**
     * Génère et persiste les RDV pour une plage avec créneaux fixes
     *
     * @param ObjectManager $em
     * @param object $user
     * @param AppointmentType $type
     * @param DateTimeImmutable $dayParis
     * @param string $windowStart
     * @param string $windowEnd
     * @param integer $durationMinutes
     * @param integer $bufferMinutes
     * @param DateTimeZone $tzUtc
     * @return integer
     */
    private function createDaySlots(
        ObjectManager $em,
        object $user,
        AppointmentType $type,
        DateTimeImmutable $dayParis,
        string $windowStart, // "HH:MM"
        string $windowEnd, // "HH:MM"
        int $durationMinutes,
        int $bufferMinutes,
        DateTimeZone $tzUtc
    ): int {
        [$hStart, $mStart] = array_map('intval', explode(':', $windowStart));
        [$hEnd, $mEnd] = array_map('intval', explode(':', $windowEnd));

        $startParis = $dayParis->setTime($hStart, $mStart, 0, 0);
        $endParis = $dayParis->setTime($hEnd, $mEnd, 0, 0);

        $stepMinutes = $durationMinutes + $bufferMinutes; // 60 + 15 = 75
        $step = new DateInterval('PT' . $stepMinutes . 'M');

        $count = 0;

        for ($cursor = $startParis; ($cursor->getTimestamp() + $durationMinutes * 60) <= $endParis->getTimestamp(); $cursor = $cursor->add($step)) {
            $slotStartParis = $cursor;
            $slotEndParis = $cursor->modify('+' . $durationMinutes . ' minutes');

            // conversion UTC pour stockage
            $slotStartUtc = $slotStartParis->setTimezone($tzUtc);
            $slotEndUtc   = $slotEndParis->setTimezone($tzUtc);

            $a = new Appointment();
            $a->setUser($user)
                ->setType($type)
                ->setStartAt($slotStartUtc)
                ->setEndAt($slotEndUtc)
                ->setStatus(AppointmentStatus::PENDING);

            // champs "personne évaluée" (obligatoires dans le schéma)
            // Embeddable EvaluatedPerson
            // On gère les deux patterns possibles :
            // - soit Appointment a un getEvaluatedPerson() (et éventuellement setEvaluatedPerson())
            // - soit on doit injecter un nouveau EvaluatedPerson
            $ev = null;
            if (method_exists($a, 'getEvaluatedPerson')) {
                $ev = $a->getEvaluatedPerson();
            }
            if (!$ev instanceof EvaluatedPerson) {
                $ev = new EvaluatedPerson();
                if (method_exists($a, 'setEvaluatedPerson')) {
                    $a->setEvaluatedPerson($ev);
                }
            }

            // Setters robustes (setFirstname)
            $this->callFirstCallable($ev, ['setFirstname', 'setFirstname'], 'Fixture');
            $this->callFirstCallable($ev, ['setLastname', 'setLastname'], 'Test');
            $this->callFirstCallable($ev, ['setPatronyms', 'setPatronyms'], 'Test');
            $this->callFirstCallable($ev, ['setBirthdate', 'setBirthdate'], new DateTimeImmutable('1968-09-22'));

            // Timestamps
            $nowUtc = new DateTimeImmutable('now', $tzUtc);
            if (method_exists($a, 'setCreatedAt')) $a->setCreatedAt($nowUtc);
            if (method_exists($a, 'setUpdatedAt')) $a->setUpdatedAt($nowUtc);

            $em->persist($a);
            $count++;
        }

        return $count;
    }

    /**
     * Appelle le 1er setter existant parmi $methods
     *
     * @param object $obj
     * @param array $methods
     * @param mixed $value
     * @return void
     */
    private function callFirstCallable(object $obj, array $methods, mixed $value): void
    {
        foreach ($methods as $m) {
            if (is_callable([$obj, $m])) {
                $obj->{$m}($value);
                return;
            }
        }
    }
}
