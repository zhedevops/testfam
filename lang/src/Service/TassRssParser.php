<?php

namespace App\Service;

use App\Entity\NewsItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;

class TassRssParser
{
    private const RSS_URL = 'https://tass.ru/rss/v2.xml';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function parseAndSaveNews(): int
    {
        try {
            $count = 0;
            $httpClient = HttpClient::create();
            $response = $httpClient->request('GET', self::RSS_URL);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('Ошибка при запросе RSS ленты');
            }

            $content = $response->getContent();

            // Парсим XML
            $xml = new \SimpleXMLElement($content);

            foreach ($xml->channel->item as $item) {
                // Обрабатываем CDATA-поля
                $title = (string)$item->title;
                $link = (string)$item->link;
                $description = (string)$item->description;
                $pubDate = new \DateTimeImmutable((string)$item->pubDate);

                // Проверяем дубликаты по URL
                $existingNews = $this->entityManager
                    ->getRepository(NewsItem::class)
                    ->findOneBy(['source' => $link]);

                if ($existingNews) {
                    $existingNews->setContent($description);
                    $existingNews->setPublishedAt($pubDate);
                    $this->entityManager->persist($existingNews);
                } else {
                    $newsItem = new NewsItem();
                    $newsItem->setTitle($title);
                    $newsItem->setContent($description);
                    $newsItem->setSource($link);
                    $newsItem->setPublishedAt($pubDate);

                    $this->entityManager->persist($newsItem);
                    $count++;
                }
            }

            $this->entityManager->flush();
            return $count;
        } catch (\Exception $e) {
            throw new \RuntimeException('Ошибка: '.$e->getMessage());
        }
    }
}