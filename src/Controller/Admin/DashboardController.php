<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\About;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\AppointmentType;
use App\Controller\Admin\UserCrudController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle("France TUNCQ")
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Formateur', 'fa-regular fa-address-card', About::class);
        yield MenuItem::linkToCrud('Entreprise', 'fa-solid fa-building', Company::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Contacts', 'fa-regular fa-envelope', Contact::class);
        yield MenuItem::subMenu('Gestion RDVs', 'fa-solid fa-calendar-check')->setSubItems([
            MenuItem::linkToCrud('Types de rendez-vous', 'fas fa-calendar', AppointmentType::class),
        ]);
        yield MenuItem::linkToUrl('Retour au site', 'fas fa-home', $this->generateUrl('app_home'));
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
