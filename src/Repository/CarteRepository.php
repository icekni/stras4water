<?php

namespace App\Repository;

use App\Entity\Carte;
use App\Entity\Discipline;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carte>
 */
class CarteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carte::class);
    }

    public function getCartesDisponiblesPourUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActif = true')
            ->andWhere(':now BETWEEN c.validFrom AND c.validUntil')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findByDiscipline(Discipline $discipline): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.disciplines', 'd')
            ->where('d = :discipline')
            ->setParameter('discipline', $discipline)
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Carte[] Returns an array of Carte objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Carte
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
