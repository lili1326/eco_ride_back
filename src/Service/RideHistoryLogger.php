<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

class RideHistoryLogger
{
    private $collection;

    public function __construct()
    {
        $client = new Client('mongodb://localhost:27017');
        $this->collection = $client->selectCollection('eco_ride', 'ride_history');
    }

    /**
     * Enregistre une participation dans MongoDB avec des infos claires
     */
    public function logRideParticipation(Ride $ride, User $user): void
    {
        dump('appel MongoDB OK');
        
        $this->collection->insertOne([
            'ride_id' => $ride->getId(),
            'lieu_depart' => $ride->getLieuDepart(),
            'lieu_arrivee' => $ride->getLieuArrivee(),
            'date_depart' => $ride->getDateDepart()?->format('Y-m-d'),
            'user_id' => $user->getId(),
            'pseudo' => $user->getPseudo(),
            'timestamp' => new UTCDateTime(),
            'action' => 'user_joined',
            'details' => [
                'places_restantes' => $ride->getNbPlace()
            ]
        ]);
    }
}
 