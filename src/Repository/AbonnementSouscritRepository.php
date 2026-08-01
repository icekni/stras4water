<?php

namespace App\Repository;

use App\Entity\AbonnementSouscrit;
use App\Entity\Discipline;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbonnementSouscrit>
 */
class AbonnementSouscritRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbonnementSouscrit::class);
    }

    public function findAllActif(): array
    {
        return $this->createQueryBuilder('abonnementsSouscrits')
            ->innerJoin('abonnementsSouscrits.abonnement', 'a')
            ->andWhere('a.isActif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AbonnementSouscrit[]
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
    //     * @return AbonnementSouscrit[] Returns an array of AbonnementSouscrit objects
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

    //    public function findOneBySomeField($value): ?AbonnementSouscrit
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
