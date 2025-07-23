<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
use MongoDB\Client as MongoClient;

#[Route('/api/admin/dashboard', name: 'admin_dashboard_')]
final class AdminDashboardApiController extends AbstractController
{
    #[Route('/rides-per-day', name: 'rides_per_day', methods: ['GET'])]
    // Fonction qui récupère le nombre de trajets par jour depuis la base MySQL
    public function getRidesPerDay(Connection $connection): JsonResponse
    {
        // Requête SQL pour :
        // - Extraire la date (sans heure) de la colonne created_at
        // - Compter le nombre de trajets (lignes)
        // - Grouper par jour
        // - Trier les jours du plus ancien au plus récent
        $sql = "
            SELECT DATE(created_at) AS jour, COUNT(*) AS total
            FROM ride
            GROUP BY jour
            ORDER BY jour ASC
        ";
        //Exécute la requête SQL et récupère un tableau associatif (clé = nom de colonne)
        $result = $connection->fetchAllAssociative($sql);
        return new JsonResponse($result);
    }

    #[Route('/credits-per-day', name: 'credits_per_day', methods: ['GET'])]
    public function getCreditsPerDay(): JsonResponse
    {
        //connection à la base de donnée mongodb
         $mongo = new MongoClient($_ENV['MONGODB_URL']);
        //séléction de la collection trésorerie,dans la base covoiturage
        $tresorerie = $mongo->selectCollection("covoiturage", "tresorerie");

        //Utilisation de l'agrégation MongoDB pour faire des calculs groupés
        $cursor = $tresorerie->aggregate([
            [
                '$group' => [
                    //'_id': On utilise $dateToString pour transformer le champ date en chaîne formatée par jour "2025-05-21"
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$date']],
                    //'credits': On fait la somme de tous les montant pour ce jour-là.
                    'credits' => ['$sum' => '$montant']
                ]
            ],
            // Tri des résultats par date croissante (du plus ancien au plus récent)
            ['$sort' => ['_id' => 1]]
        ]);

        //Transformation du résultat brut de MongoDB (le cursor) en tableau PHP lisible, avec les clés 
        //'jour' = date  et 'credits' = somme des montants ce jour-là
        $results = [];
        foreach ($cursor as $entry) {
            $results[] = [
                'jour' => $entry['_id'],
                'credits' => $entry['credits']
            ];
        }

        return new JsonResponse($results);
    }

  
#[Route('/rides-stats', name: 'rides_stats', methods: ['GET'])]
public function getRidesStats(): JsonResponse
{
    // Connexion à la base MongoDB avec l’URL définie dans le fichier .env
    $mongo = new MongoClient($_ENV['MONGODB_URL']);

    // Sélection de la base de données "eco_ride" et de la collection "stats"
    $collection = $mongo->selectCollection('eco_ride', 'stats');

     // Requête MongoDB : récupère tous les documents triés par date croissante
    $cursor = $collection->find([], ['sort' => ['date' => 1]]);

    // Initialisation d’un tableau pour stocker les résultats formatés
    $results = [];

    // Parcours des résultats MongoDB
    foreach ($cursor as $entry) {
        // Stocke les données utiles dans un tableau associatif lisible
        $results[] = [
            'jour' => $entry['date'],// Date du jour
            'nb' => $entry['nb'],// Nombre de trajets ce jour-là
            'gain' => $entry['gain']// Gain total pour ce jour

        ];
    }

     // Retourne les résultats sous forme de réponse JSON pour l’API
    return new JsonResponse($results);
}
}