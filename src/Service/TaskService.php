<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class TaskService
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    private function setTask(Task $task): Task
    {
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($task);
        $entityManager->flush();

        return $task;
    }

    public function addTask(Task $task, User $user): void
    {
        $task->setUser($user);

        $this->setTask($task);
    }

    public function editTask(Task $task): void
    {
        $this->setTask($task);
    }

    public function toggleTask(Task $task): Task
    {
        return $this->setTask($task);
    }

    public function deleteTask(Task $task): void
    {
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($task);
        $entityManager->flush();
    }
}
