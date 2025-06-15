<?php

namespace App\Repository;

use App\Entity\HelloAssoToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HelloAssoToken>
 */
class HelloAssoTokenRepository extends ServiceEntityRepository
{
    private $em;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelloAssoToken::class);
        $this->em = $this->getEntityManager();
    }

    public function getSingleton(): HelloAssoToken
    {
        $token = $this->createQueryBuilder('t')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$token) {
            $token = new HelloAssoToken();
            $token->setAccessToken('')
                  ->setAccessTokenExpiresAt(new \DateTimeImmutable('-1 hour'))
                  ->setRefreshToken('')
                  ->setRefreshTokenExpiresAt(new \DateTimeImmutable('-1 hour'));

            $this->em->persist($token);
            $this->em->flush();
        }

        return $token;
    }

    //    /**
    //     * @return HelloAssoToken[] Returns an array of HelloAssoToken objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?HelloAssoToken
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
