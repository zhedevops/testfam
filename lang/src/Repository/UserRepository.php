<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findCompletedRegistrations(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isVerified = true')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findIncompleteRegistrations(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isVerified = false')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
