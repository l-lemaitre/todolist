<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;

    private TranslatorInterface $translator;

    public function __construct(UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator)
    {
        $this->urlGenerator = $urlGenerator;

        $this->translator = $translator;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): RedirectResponse
    {
        $currentRoute = $request->attributes->get('_route');

        $message = $this->translator->trans('Access denied. You do not have sufficient rights to view this page.');
        if ($currentRoute == 'app_user_list') {
            $message = $this->translator->trans('Access denied. You do not have sufficient rights to display the list of users.');
        } else if ($currentRoute == 'app_user_create') {
            $message = $this->translator->trans('Access denied. You do not have sufficient rights to create a user.');
        } else if ($currentRoute == 'app_user_edit') {
            $message = $this->translator->trans('Access denied. The user cannot be modified.');
        } else if ($currentRoute == 'app_user_delete') {
            $message = $this->translator->trans('Access denied. The user cannot be deleted.');
        } else if ($currentRoute == 'app_task_delete') {
            $message = $this->translator->trans('Access denied. The task cannot be deleted.');
        }

        $request->getSession()->getFlashBag()->add('error', $message);

        $redirectUrl = $this->urlGenerator->generate('app_homepage');

        return new RedirectResponse($redirectUrl);
    }
}
