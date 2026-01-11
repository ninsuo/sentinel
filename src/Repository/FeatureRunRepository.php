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

    public function findLatestRunForFeature(Feature $feature) : ?FeatureRun
    {
        return $this->findOneBy(['feature' => $feature], ['createdAt' => 'DESC']);
    }

    /**
     * @param list<Feature> $features
     *
     * @return array<int, FeatureRun> map featureId => latest FeatureRun
     */
    public function findLatestRunsForFeatures(array $features) : array
    {
        if ($features === []) {
            return [];
        }

        // Using MAX(id) is fine for "latest" as long as ids are monotonic.
        // If you want strictness, use MAX(createdAt) + join, but this is good enough for now.
        $sub = $this->createQueryBuilder('r2')
                    ->select('MAX(r2.id)')
                    ->andWhere('r2.feature IN (:features)')
                    ->groupBy('r2.feature');

        $runs = $this->createQueryBuilder('r')
                     ->andWhere('r.id IN ('.$sub->getDQL().')')
                     ->setParameter('features', $features)
                     ->getQuery()
                     ->getResult();

        $map = [];
        foreach ($runs as $run) {
            $map[$run->getFeature()->getId()] = $run;
        }

        return $map;
    }

}
