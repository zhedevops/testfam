<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\NewsItemRepository;
use App\Repository\VerificationCodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DashboardController extends AbstractController
{
    public function index(
        UserRepository $userRepository,
    ): Response {
        if (!$this->checkAccess()) {
            return $this->render('admin/dashboard/error403.html.twig');
        }
        $completedRegistrations = $userRepository->findCompletedRegistrations();
        return $this->render('admin/dashboard/completed.html.twig', [
            'completed_registrations' => $completedRegistrations,
        ]);
    }

    public function incompleted(
        UserRepository $userRepository,
    ): Response {
        if (!$this->checkAccess()) {
            return $this->render('admin/dashboard/error403.html.twig');
        }
        $incompleteRegistrations = $userRepository->findIncompleteRegistrations();
        return $this->render('admin/dashboard/incompleted.html.twig', [
            'incomplete_registrations' => $incompleteRegistrations,
        ]);
    }

    public function codes(
        VerificationCodeRepository $codeRepository,
    ): Response {
        if (!$this->checkAccess()) {
            return $this->render('admin/dashboard/error403.html.twig');
        }
        $verificationCodes = $codeRepository->findAll();
        return $this->render('admin/dashboard/codes.html.twig', [
            'verification_codes' => $verificationCodes,
        ]);
    }

    public function news(
        NewsItemRepository $newsRepository,
        Request $request
    ): Response {
        if (!$this->checkAccess()) {
            return $this->render('admin/dashboard/error403.html.twig');
        }
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $maxPages = 10;
        $news = $newsRepository->getPaginatedNews($page, $limit);

        return $this->render('admin/dashboard/news.html.twig', [
            'news' => $news,
            'max_pages' => $maxPages,
            'current_page' => $page,
        ]);
    }

    private function checkAccess()
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            return true;
        } catch (AccessDeniedException $e) {
            return false;
        }
    }
}
