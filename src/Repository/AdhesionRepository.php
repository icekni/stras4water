<?php

namespace App\Repository;

use App\Entity\Adhesion;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Adhesion>
 */
class AdhesionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adhesion::class);
    }

    /**
     * @return Adhesion[]
     */
    public function findBetweenDates(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($from) {
            $qb
                ->andWhere('a.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb
                ->andWhere('a.createdAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Adhesion[] Returns an array of Adhesion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Adhesion
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
