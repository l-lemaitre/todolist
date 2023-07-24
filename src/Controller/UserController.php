<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    private ManagerRegistry $doctrine;

    private UserService $userService;

    public function __construct(ManagerRegistry $doctrine, UserService $userService)
    {
        $this->doctrine = $doctrine;

        $this->userService = $userService;
    }

    #[Route('/users', name: 'app_user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listAction(): Response
    {
        return $this->render('user/list.html.twig', ['users' => $this->doctrine->getRepository(User::class)->findAll()]);
    }

    #[Route('/users/create', name: 'app_user_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $userConnected = $this->getUser();

        if ($userConnected && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->addUser($user);

            $this->addFlash('success', 'L\'utilisateur a bien été ajouté.');

            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, int $id): RedirectResponse|Response
    {
        $userConnected = $this->getUser();

        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (!$user || ($user !== $userConnected && !$this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->editUser($user);

            $this->addFlash('success', 'L\'utilisateur a bien été modifié.');

            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    #[Route('/users/{id}/delete', name: 'app_user_delete', methods: ['GET'])]
    public function deleteAction(int $id): RedirectResponse
    {
        $userConnected = $this->getUser();

        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createAccessDeniedException();
        } elseif ($user == $userConnected) {
            $this->userService->deleteUser($user);

            $session = new Session();
            $session->invalidate();

            return $this->redirectToRoute('app_logout');
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $this->userService->deleteUser($user);

            $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

            return $this->redirectToRoute('app_user_list');
        } else {
            throw $this->createAccessDeniedException();
        }
    }
}
