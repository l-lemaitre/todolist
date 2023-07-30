<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function getCurrentTasks(): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->where('t.isDone = :is_done')
            ->setParameter('is_done', 0);
        return $queryBuilder->getQuery()->getResult();
    }

    public function getDoneTasks(): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->where('t.isDone = :is_done')
            ->setParameter('is_done', 1);
        return $queryBuilder->getQuery()->getResult();
    }
}