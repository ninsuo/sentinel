<?php

namespace App\Repository;

use App\Entity\Feature;
use App\Entity\FeatureRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class FeatureRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureRun::class);
    }

    public function findLatestRunsForFeature(Feature $feature, int $limit = 20) : array
    {
        return $this->createQueryBuilder('r')
                    ->andWhere('r.feature = :feature')
                    ->setParameter('feature', $feature)
                    ->orderBy('r.createdAt', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }
}
