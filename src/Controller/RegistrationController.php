<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Google\GoogleService;
use App\Service\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(protected RequestStack $requestStack) {}

    #[Route('/inscription', name: 'app_register')]
    public function index(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $em, AvatarService $avatarService, GoogleService $googleService): Response
    {
        $session = $this->requestStack->getSession();
        $targetPath = $request->query->get('target', $session->get('_security.main.target_path'));
        if ($targetPath) {
            $session->set('_security.main.target_path', $targetPath);
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $firstname = $form->get('firstname')->getData();
            $lastname = $form->get('lastname')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Transform first and lastname
            $user->setFirstname(ucfirst($firstname))
                ->setLastname(mb_strtoupper($lastname));

            $user->setAvatar($avatarService->createAndAssignAvatar($user));

            $em->persist($user);
            $em->flush();

            // do anything else you need here, like send an email
            $redirectResponse = $security->login($user, 'security.authenticator.form_login.main', 'main');
            return $redirectResponse;
        }
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'google_api_key' => $googleService->getGoogleKey(),
        ]);
    }
}
