<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Service\TaskService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    #[Route('/tasks', name: 'app_task_list', methods: ['GET'])]
    public function listAction(ManagerRegistry $doctrine): Response
    {
        return $this->render('task/list.html.twig', ['tasks' => $doctrine->getRepository(Task::class)->findAll()]);
    }

    #[Route('/tasks/create', name: 'app_task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->addTask($task);

            $this->addFlash('success', 'La tâche a bien été ajoutée.');

            return $this->redirectToRoute('app_task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/tasks/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function editAction(Task $task, Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->editTask($task);

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('app_task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/toggle', name: 'app_task_toggle', methods: ['GET'])]
    public function toggleAction(Task $task): RedirectResponse
    {
        $task->toggle(!$task->isDone());

        $task = $this->taskService->toggleTask($task);

        $message = $task->getIsDone() ? sprintf('La tâche %s a bien été marquée comme terminée.',
            $task->getTitle()) : sprintf('La tâche %s a bien été marquée comme non terminée.', $task->getTitle());

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_task_list');
    }

    #[Route('/tasks/{id}/delete', name: 'app_task_delete', methods: ['GET'])]
    public function deleteAction(Task $task): RedirectResponse
    {
        $this->taskService->deleteTask($task);

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('app_task_list');
    }
}
