<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture
{
    public const TASKS = [
        [
            'title' => 'Date des courses',
            'content' => 'Aller faire les courses samedi.'
        ],
        [
            'title' => 'Lieu des courses',
            'content' => 'Aller au supermarché.'
        ],
        [
            'title' => 'Récupérer colis',
            'content' => 'Aller au point de retrait.'
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        $userFixturesRoleUser = $this->getReference(UserFixtures::USERS_ROLE_USER_REFERENCE);
        $userFixturesRoleAdmin = $this->getReference(UserFixtures::USERS_ROLE_ADMIN_REFERENCE);

        foreach (self::TASKS as $task) {
            $taskEntity = new Task();
            $taskEntity->setTitle($task['title']);
            $taskEntity->setContent($task['content']);
            if ($task['title'] == 'Lieu des courses') {
                $taskEntity->setUSer($userFixturesRoleUser);
            } elseif ($task['title'] == 'Récupérer colis') {
                $taskEntity->setUser(NULL);
            } else {
                $taskEntity->setUser($userFixturesRoleAdmin);
            }
            $manager->persist($taskEntity);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
