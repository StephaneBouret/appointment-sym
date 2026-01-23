<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Avatar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AvatarService
{
    protected $em;
    protected $params;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->params = $params;
    }

    public function createAndAssignAvatar(User $user): Avatar
    {
        $avatar = new Avatar();
        $initial = mb_strtoupper(mb_substr($user->getFirstname(), 0, 1, 'UTF-8'), 'UTF-8');
        $avatarsDirectory = $this->params->get('avatars_directory');
        $outputPath = $avatarsDirectory . '/' . uniqid() . '.png';
        $avatar->createDefaultAvatar($initial, $outputPath);
        $avatar->setImageName(basename($outputPath));
        $avatar->setUser($user);
        $avatar->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($avatar);
        $this->em->flush();
        return $avatar;
    }

    public function handleAvatarForm($avatarForm, User $user, $avatar)
    {
        if ($avatarForm->isSubmitted() && $avatarForm->isValid()) {
            if ($avatar === null || $avatar->getImageName() === null) {
                $avatar = $avatarForm->getData();
                $initial = mb_strtoupper(mb_substr($user->getFirstname(), 0, 1, 'UTF-8'), 'UTF-8');
                $avatarsDirectory = $this->params->get('avatars_directory');
                $outputPath = $avatarsDirectory . '/' . uniqid() . '.png';
                $avatar->createDefaultAvatar($initial, $outputPath);
                $avatar->setImageName(basename($outputPath));
                $avatar->setUser($user);
                $avatar->setUpdatedAt(new \DateTimeImmutable());
                $this->em->persist($avatar);
            }
            $this->em->flush();
            return true;
        }
        return false;
    }
}
