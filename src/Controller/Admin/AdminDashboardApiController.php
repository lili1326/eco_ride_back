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
    public function getRidesPerDay(Connection $connection): JsonResponse
    {
        $sql = "
            SELECT DATE(created_at) AS jour, COUNT(*) AS total
            FROM ride
            GROUP BY jour
            ORDER BY jour ASC
        ";
        $result = $connection->fetchAllAssociative($sql);
        return new JsonResponse($result);
    }

    #[Route('/credits-per-day', name: 'credits_per_day', methods: ['GET'])]
    public function getCreditsPerDay(): JsonResponse
    {
        $mongo = new MongoClient("mongodb://localhost:27017");
        $tresorerie = $mongo->selectCollection("covoiturage", "tresorerie");

        $cursor = $tresorerie->aggregate([
            [
                '$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$date']],
                    'credits' => ['$sum' => '$montant']
                ]
            ],
            ['$sort' => ['_id' => 1]]
        ]);

        $results = [];
        foreach ($cursor as $entry) {
            $results[] = [
                'jour' => $entry['_id'],
                'credits' => $entry['credits']
            ];
        }

        return new JsonResponse($results);
    }
}