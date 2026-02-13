<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Repository\CompanyRepository;
use App\Service\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPdfController extends AbstractController
{
    public function __construct(private PdfGeneratorService $pdf, private CompanyRepository $companies) {}

    #[Route('/pdf/{id}', name: 'app_appointment_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function customer(Appointment $appointment): Response
    {
        if (!$appointment) {
            throw $this->createNotFoundException("Le rendez-vous demandé n'existe pas");
        }

        if ($appointment->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException("Le rendez-vous demandé n'existe pas");
        }

        if ($appointment->getStatus() !== AppointmentStatus::CONFIRMED) {
            throw $this->createNotFoundException("Le rendez-vous n'est pas payé");
        }

        $company = $this->companies->findOneBy([]);

        $html = $this->renderView('appointment/pdf.html.twig', [
            'appointment' => $appointment,
            'company' => $company,
            'tz' => 'Europe/Paris',
        ]);

        $ref = method_exists($appointment, 'getNumber') && $appointment->getNumber()
            ? $appointment->getNumber()
            : (string) $appointment->getId();

        $filename = sprintf('facture-rdv-%s.pdf', $ref);

        return $this->pdf->getStreamResponse($html, $filename);
    }

    #[Route('/admin/pdf/{id}', name: 'app_appointment_pdf_admin', methods: ['GET'])]
    public function admin(Appointment $appointment): Response
    {
        if (!$appointment) {
            return $this->redirectToRoute('admin');
        }

        $company = $this->companies->findOneBy([]);

        $html = $this->renderView('appointment/pdf.html.twig', [
            'appointment' => $appointment,
            'company'     => $company,
            'tz'          => 'Europe/Paris',
        ]);

        $ref = method_exists($appointment, 'getNumber') && $appointment->getNumber()
            ? $appointment->getNumber()
            : (string) $appointment->getId();

        $filename = sprintf('facture-rdv-%s.pdf', $ref);

        return $this->pdf->getStreamResponse($html, $filename);
    }
}
