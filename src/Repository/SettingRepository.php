<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function all() : array
    {
        $settings = [];
        $entities = $this->findAll();

        foreach ($entities as $entity) {
            $settings[$entity->getProperty()] = $entity->getValue();
        }

        return $settings;
    }

    public function get(string $property, ?string $default = null) : ?string
    {
        $entity = $this->findOneByProperty($property);

        if ($entity) {
            return $entity->getValue();
        }

        return $default;
    }

    public function set(string $property, ?string $value) : void
    {
        $entity = $this->findOneByProperty($property);

        if (!$entity) {
            $entity = new Setting($property, null);
            $this->getEntityManager()->persist($entity);
        }

        $entity->setValue($value);
        $this->getEntityManager()->flush();
    }

    public function remove(string $property) : void
    {
        $entity = $this->findOneByProperty($property);

        if ($entity) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        }
    }
}