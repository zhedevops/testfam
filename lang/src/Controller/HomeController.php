<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Service\EventPublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HomeController extends AbstractController
{
    public function index(
        Request $request,
        NewsItemRepository $newsRepository,
        EventPublisher $publisher
    ): Response
    {
        $referer = $request->headers->get('referer');
        $isFromLogin = $referer && parse_url($referer, PHP_URL_PATH) === '/login';
        if ($isFromLogin) {
            $user = $this->getUser();
            $publisher->publish('login', [
                'email' => $user ? $user->getEmail() : '',
                'time' => time()
            ]);
        }
        $page = 1;
        $limit = 10;
        $news = $newsRepository->getPaginatedNews($page, $limit);

        return $this->render('home/index.html.twig', [
            'news_items' => $news,
        ]);
    }
}
