<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Avatar;
use App\Form\AvatarFormType;
use App\Service\AvatarService;
use App\Form\UpdateUserFormType;
use App\Form\UpdatePasswordUserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function index(Request $request, EntityManagerInterface $em, AvatarService $avatarService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Gestion de l'avatar
        $avatar = $user->getAvatar();

        // Formulaire pour éditer l'avatar existant ou en créer un nouveau si null ou imageName est null
        $avatarForm = $this->createForm(AvatarFormType::class, $avatar ?: new Avatar());
        $avatarForm->handleRequest($request);

        if ($avatarService->handleAvatarForm($avatarForm, $user, $avatar)) {
            $this->addFlash('success', 'La photo a bien été modifiée !');

            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(UpdateUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstname = ucfirst($form->get('firstname')->getData());
            $lastname = mb_strtoupper($form->get('lastname')->getData());

            $user->setFirstname($firstname)
                ->setLastname($lastname);
            $em->flush();

            $this->addFlash('success', 'Vos modifications ont bien été prises en compte');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'formAvatar' => $avatarForm,
            'user' => $user
        ]);
    }

    #[Route('/profile/editPassword', name: 'app_edit_password')]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function editPassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em)
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UpdatePasswordUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $newPassword = $form->get('newPassword')->getData();
                $password = $userPasswordHasher->hashPassword($user, $newPassword);

                $user->setPassword($password);
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a bien été mis à jour');
                return $this->redirectToRoute('app_home');
            } else {
                // En cas d'erreurs de validation, rediriger vers la même page
                $this->addFlash('warning', 'Une erreur est survenue !');
                return $this->redirectToRoute('app_edit_password');
            }
        }

        return $this->render('profile/credentials.html.twig', [
            'form' => $form,
            'user' => $user
        ]);
    }

    #[Route('profile/user/{id}/delete', name: 'app_user_delete')]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function delete(Request $request, User $user, EntityManagerInterface $em)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->container->get('security.token_storage')->setToken(null);
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Votre compte a bien été supprimé !');
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}
