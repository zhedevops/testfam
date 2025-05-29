<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<NewsItem>
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    public function getPaginatedNews(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('n')
            ->orderBy('n.publishedAt', 'DESC')
            ->getQuery();

        $paginator = new Paginator($query);
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    public function searchByTitleAndContent(string $query): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.title LIKE :query OR n.content LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
