<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Service\EventPublisher;
use App\Service\TassRssParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class NewsController extends AbstractController
{
    public function __construct(
        private TassRssParser $rssParser,
        private LoggerInterface $logger
    ) {}

    public function parseNews(
        EventPublisher $publisher
    ): Response
    {
        try {
            $count = $this->rssParser->parseAndSaveNews();

            $publisher->publish('data_update', [
                'time' => time()
            ]);

            $this->logger->info("Added {$count} news");

            return new JsonResponse([
                'date' => date('d-m-y h:i:s'),
                'status' => 'success',
                'message' => "Added {$count} news",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Ошибка парсинга: '.$e->getMessage());

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request, NewsItemRepository $newsRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $news = $newsRepository->getPaginatedNews($page, $limit);
        $maxPages = 10;

        return $this->render('news/index.html.twig', [
            'news_items' => $news,
            'max_pages' => $maxPages,
            'current_page' => $page,
        ]);
    }

    public function show(int $id, NewsItemRepository $newsRepository): Response
    {
        $newsItem = $newsRepository->find($id);

        if (!$newsItem) {
            throw $this->createNotFoundException('Новость не найдена');
        }

        return $this->render('news/show.html.twig', [
            'news' => $newsItem,
        ]);
    }

    public function search(Request $request, NewsItemRepository $newsRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $news = $newsRepository->searchByTitleAndContent($query);

        return $this->json([
            'news' => array_map(function ($item) {
                return [
                    'id' => $item->getId(),
                    'title' => $item->getTitle(),
                    'content' => $item->getContent(),
                    'publishedAt' => $item->getPublishedAt()->format('d.m.Y H:i'),
                    'url' => $this->generateUrl('app_news_show', ['id' => $item->getId()])
                ];
            }, $news)
        ]);
    }
}
