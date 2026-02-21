<?php

namespace App\Repository;

use App\Entity\CarteSouscrite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarteSouscrite>
 */
class CarteSouscriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarteSouscrite::class);
    }

    public function findAllActif(): array
    {
        return $this->createQueryBuilder('cartesSouscrites')
            ->Where('cartesSouscrites.seancesRestantes > 0')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return CarteSouscrite[] Returns an array of CarteSouscrite objects
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

    //    public function findOneBySomeField($value): ?CarteSouscrite
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
