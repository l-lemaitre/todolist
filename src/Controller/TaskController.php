<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Service\TaskService;
use App\Repository\TaskRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaskController extends AbstractController
{
    private ManagerRegistry $doctrine;

    private TaskRepository $taskRepository;

    private TaskService $taskService;

    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $doctrine, TaskRepository $taskRepository, TaskService $taskService, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;

        $this->taskRepository = $taskRepository;

        $this->taskService = $taskService;

        $this->translator = $translator;
    }

    #[Route('/tasks', name: 'app_task_list', methods: ['GET'])]
    #[Route('/tasks/current', name: 'app_task_current_list', methods: ['GET'])]
    #[Route('/tasks/done', name: 'app_task_done_list', methods: ['GET'])]
    public function listAction(Request $request): Response
    {
        $userId = $this->getUser()->getId();

        $routeName = $request->attributes->get('_route');
        if ($routeName == 'app_task_current_list') {
            $task = $this->taskRepository->getCurrentTasks();
        } else if ($routeName == 'app_task_done_list') {
            $task = $this->taskRepository->getDoneTasks();
        } else {
            $task = $this->taskRepository->findAll();
        }

        return $this->render('task/list.html.twig', [
            'tasks' => $task,
            'userId' => $userId
        ]);
    }

    #[Route('/tasks/create', name: 'app_task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $user = $this->getUser();

        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->addTask($task, $user);

            $message = $this->translator->trans('The task has been successfully added.');
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_task_current_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/tasks/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, int $id): RedirectResponse|Response
    {
        $task = $this->doctrine->getRepository(Task::class)->find($id);

        if (!$task) {
            $message = $this->translator->trans('The task was not found.');
            $this->addFlash('error', $message);

            return $this->redirectToRoute('app_task_list');
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->editTask($task);

            $message = $this->translator->trans('The task has been successfully edited.');
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/toggle', name: 'app_task_toggle', methods: ['GET'])]
    public function toggleAction(int $id): RedirectResponse
    {
        $task = $this->doctrine->getRepository(Task::class)->find($id);

        if (!$task) {
            $message = $this->translator->trans('The task was not found.');
            $this->addFlash('error', $message);

            return $this->redirectToRoute('app_task_list');
        }

        $task->toggle(!$task->getIsDone());

        $task = $this->taskService->toggleTask($task);

        $message = $task->getIsDone() ? 'Task %s was successfully marked as completed.'
            : 'Task %s was successfully marked as incomplete.';
        $message = $this->translator->trans($message, ['%s' => $task->getTitle()]);
        $this->addFlash('success', $message);

        if ($task->getIsDone()) {
            $redirection = $this->redirectToRoute('app_task_done_list');
        } else {
            $redirection = $this->redirectToRoute('app_task_current_list');
        }

        return $redirection;
    }

    #[Route('/tasks/{id}/delete', name: 'app_task_delete', methods: ['GET'])]
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $user = $this->getUser();

        $task = $this->doctrine->getRepository(Task::class)->find($id);

        if (!$task) {
            throw $this->createAccessDeniedException();
        } elseif (($task->getUser() == $user) || (!$task->getUser() && $this->isGranted('ROLE_ADMIN'))) {
            $this->taskService->deleteTask($task);

            $message = $this->translator->trans('The task has been successfully deleted.');
            $this->addFlash('success', $message);

            $route = $request->headers->get('referer');
            return $this->redirect($route);
        } else {
            throw $this->createAccessDeniedException();
        }
    }
}
