<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return Project[]
     */
    public function findAllActive() : array
    {
        return $this->createQueryBuilder('p')
                    ->andWhere('p.deletedAt IS NULL')
                    ->orderBy('p.updatedAt', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @return Project[]
     */
    public function findAllDeleted() : array
    {
        return $this->createQueryBuilder('p')
                    ->andWhere('p.deletedAt IS NOT NULL')
                    ->orderBy('p.deletedAt', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
}
