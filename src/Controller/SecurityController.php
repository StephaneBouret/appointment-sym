<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(protected RequestStack $requestStack) {}

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $session = $this->requestStack->getSession();

        $targetPath = $request->query->get('target', $session->get('_security.main.target_path'));
        if ($targetPath) {
            $session->set('_security.main.target_path', $targetPath);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/login/success', name: 'app_login_success')]
    public function loginSuccess(): Response
    {
        $session = $this->requestStack->getSession();

        // Récupérer l'URL de redirection stockée
        $targetPath = $session->get('_security.main.target_path', $this->generateUrl('app_home'));

        // Supprimer la valeur de la session pour éviter une redirection non voulue plus tard
        $session->remove('_security.main.target_path');

        return $this->redirect($targetPath);
    }
}
