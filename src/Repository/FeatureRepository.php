<?php

namespace App\Repository;

use App\Entity\Feature;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class FeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feature::class);
    }

    /**
     * @return Feature[]
     */
    public function findByProjectActive(Project $project) : array
    {
        return $this->createQueryBuilder('f')
                    ->andWhere('f.project = :project')
                    ->andWhere('f.deletedAt IS NULL')
                    ->setParameter('project', $project)
                    ->orderBy('f.updatedAt', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @return Feature[]
     */
    public function findDeletedByProject(Project $project) : array
    {
        return $this->createQueryBuilder('f')
                    ->andWhere('f.project = :project')
                    ->andWhere('f.deletedAt IS NOT NULL')
                    ->setParameter('project', $project)
                    ->orderBy('f.deletedAt', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
}
