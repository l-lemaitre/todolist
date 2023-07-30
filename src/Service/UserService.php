<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private ManagerRegistry $doctrine;

    private UserPasswordHasherInterface $userPasswordHash;

    public function __construct(ManagerRegistry $doctrine, UserPasswordHasherInterface $userPasswordHash)
    {
        $this->doctrine = $doctrine;

        $this->userPasswordHash = $userPasswordHash;
    }

    private function setUser(User $user): void
    {
        if (trim($user->getPassword())) {
            $user->setPassword(
                $this->userPasswordHash->hashPassword(
                    $user,
                    $user->getPassword()
                )
            );
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }

    public function addUser(User $user): void
    {
        $this->setUser($user);
    }

    public function editUser(User $user): void
    {
        $this->setUser($user);
    }

    public function deleteUser(User $user): void
    {
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();
    }
}
