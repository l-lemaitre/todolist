<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHash;

    public const USERS = [
        [
            'username' => 'Ludovic',
            'password' => 'admin_5_5',
            'email' => 'admin@orange.fr',
            'roles' => ['ROLE_ADMIN']
        ],
        [
            'username' => 'Nerofaust',
            'password' => 'user_5_5',
            'email' => 'user@orange.fr',
            'roles' => ["ROLE_USER"]
        ]
    ];

    public const USERS_ROLE_USER_REFERENCE = 'ROLE_USER';

    public const USERS_ROLE_ADMIN_REFERENCE = 'ROLE_ADMIN';

    public function __construct(UserPasswordHasherInterface $userPasswordHash)
    {
        $this->userPasswordHash = $userPasswordHash;
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $user) {
            $userEntity = new User();
            $userEntity->setUsername($user['username']);
            $userEntity->setPassword(
                $this->userPasswordHash->hashPassword(
                    $userEntity,
                    $user['password']
                )
            );
            $userEntity->setEmail($user['email']);
            $userEntity->setRoles($user['roles']);
            $manager->persist($userEntity);

            if (in_array('ROLE_ADMIN', $userEntity->GetRoles())) {
                $this->addReference(self::USERS_ROLE_ADMIN_REFERENCE, $userEntity);
            } else {
                $this->addReference(self::USERS_ROLE_USER_REFERENCE, $userEntity);
            }
        }

        $manager->flush();
    }
}
