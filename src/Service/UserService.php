<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private ManagerRegistry $doctrine;

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(ManagerRegistry $doctrine, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->doctrine = $doctrine;

        $this->userPasswordHasher = $userPasswordHasher;
    }

    private function setUser(User $user): User
    {
        //$user->setRoles(['ROLE_USER']);

        if (trim($user->getPassword())) {
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $user->getPassword()
                )
            );
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
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
