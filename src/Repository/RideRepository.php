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
// Méthode pour afficher tous les trajets filtrés selon des critères facultatifs (lieu de départ, arrivée, date)
//public function findByCriteria(?string $depart, ?string $arrivee, ?string $date): array
//{
// Création d’un QueryBuilder pour construire dynamiquement une requête SQL (sur l’entité Ride alias "r")
  //  $qb = $this->createQueryBuilder('r');

  //  if ($depart) {
// ...on ajoute une condition LIKE pour filtrer les trajets qui contiennent ce texte dans le champ "lieu_depart"
  //      $qb->andWhere('r.lieu_depart LIKE :depart')
  //         ->setParameter('depart', '%' . $depart . '%');// %...% = recherche partielle (ex: "Par" trouve "Paris")
  //  }

  //  if ($arrivee) {
  //      $qb->andWhere('r.lieu_arrivee LIKE :arrivee')
  //         ->setParameter('arrivee', '%' . $arrivee . '%');
  //  }

 //   if ($date) {
 // ...on ajoute une condition stricte sur la date de départ (égalité exacte, pas une recherche partielle)
 //       $qb->andWhere('r.date_depart = :date')
 //          ->setParameter('date', new \DateTimeImmutable($date));// conversion de la chaîne en objet DateTime
 //   }
// Trie les résultats par date de départ croissante (la plus ancienne en premier)
  //  return $qb->orderBy('r.date_depart', 'ASC')->getQuery()->getResult();//Exécute la requête et renvoie un tableau de résultats
//}

public function findByCriteria(
    ?string $depart,
    ?string $arrivee,
    ?string $date,
    ?string $energie = null,
    ?string $prixMax = null,
    ?string $dureeMax = null, // en minutes
    ?string $noteMin = null   // optionnel: à brancher si tu as une table d'avis
): array {
    $qb = $this->createQueryBuilder('r');

    // JOIN pour accéder à l’énergie du véhicule
    $qb->leftJoin('r.voiture', 'v')->addSelect('v');

    if ($depart) {
        $qb->andWhere('r.lieu_depart LIKE :depart')
           ->setParameter('depart', "%$depart%");
    }

    if ($arrivee) {
        $qb->andWhere('r.lieu_arrivee LIKE :arrivee')
           ->setParameter('arrivee', "%$arrivee%");
    }

    if ($date) {
        $qb->andWhere('r.date_depart = :date')
           ->setParameter('date', new \DateTimeImmutable($date));
    }

    // === Filtres supplémentaires ===
    if ($energie) {
        $qb->andWhere('LOWER(v.energie) = LOWER(:energie)')
           ->setParameter('energie', $energie);
    }

    if ($prixMax !== null && $prixMax !== '') {
        $qb->andWhere('r.prix_personne <= :prixMax')
           ->setParameter('prixMax', (float)$prixMax);
    }

    if ($dureeMax !== null && $dureeMax !== '') {
        // supposition: heure_depart / heure_arrivee sont de type TIME (MySQL)
        // On compare en secondes: TIME_TO_SEC(arrivee) - TIME_TO_SEC(depart) <= dureeMax * 60
        $qb->andWhere("(FUNCTION('TIME_TO_SEC', r.heure_arrivee) - FUNCTION('TIME_TO_SEC', r.heure_depart)) <= :dureeMaxSec")
           ->setParameter('dureeMaxSec', (int)$dureeMax * 60);
    }

    // NOTE MIN (si tu as une relation Avis => calcule la moyenne en SQL, sinon laisse tomber pour l’instant)
    /*
    if ($noteMin !== null && $noteMin !== '') {
        $qb->leftJoin('r.conducteur', 'u')
           ->leftJoin('u.avisRecus', 'av') // adapte les noms
           ->groupBy('r.id')
           ->having('AVG(av.note) >= :noteMin')
           ->setParameter('noteMin', (float)$noteMin);
    }
    */

    return $qb->orderBy('r.date_depart', 'ASC')
              ->getQuery()
              ->getResult();
}

}