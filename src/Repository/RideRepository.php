<?php

namespace App\Repository;

use App\Entity\Ride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ride>
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

//    /**
//     * @return Ride[] Returns an array of Ride objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ride
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


//POUR AFFICHER TOUS LES TRAJETS
public function findByCriteria(?string $depart, ?string $arrivee, ?string $date): array
{
    $qb = $this->createQueryBuilder('r');

    if ($depart) {
        $qb->andWhere('r.lieu_depart LIKE :depart')
           ->setParameter('depart', '%' . $depart . '%');
    }

    if ($arrivee) {
        $qb->andWhere('r.lieu_arrivee LIKE :arrivee')
           ->setParameter('arrivee', '%' . $arrivee . '%');
    }

    if ($date) {
        $qb->andWhere('r.date_depart = :date')
           ->setParameter('date', new \DateTimeImmutable($date));
    }

    return $qb->orderBy('r.date_depart', 'ASC')->getQuery()->getResult();
}

}