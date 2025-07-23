<?php

namespace App\Command;

use App\Repository\RideRepository;
use MongoDB\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sync:mysql-to-mongo')]// Nom de la commande personnalisée
class SyncMysqlToMongoCommand extends Command
{
    public function __construct(
        private RideRepository $rideRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Connexion à MongoDB via l'URL stockée dans .env
        $mongoClient = new \MongoDB\Client(getenv('MONGODB_URL'), [
    'tls' => true,
    'tlsAllowInvalidCertificates' => true
]);
        // Sélection de la base et de la collection MongoDB
        $db = $mongoClient->selectDatabase('eco_ride');
        $collection = $db->selectCollection('stats');

         // Récupération de tous les trajets depuis MySQL
        $rides = $this->rideRepo->findAll();
        $stats = [];

         // Parcours de chaque trajet
        foreach ($rides as $ride) {
            // Format de la date du trajet (jour seulement)
            $day = $ride->getDateDepart()->format('Y-m-d');

             // Calcul du gain = prix * nombre de participants
            $gain = $ride->getPrixPersonne() * count($ride->getParticipes());

            // Si ce jour n'est pas encore dans le tableau, on l'initialise
            if (!isset($stats[$day])) {
                $stats[$day] = ['date' => $day, 'nb' => 0, 'gain' => 0];
            }

            // Incrément du nombre de trajets et ajout du gain pour ce jour
            $stats[$day]['nb']++;
            $stats[$day]['gain'] += $gain;
        }

         // Enregistrement des stats dans MongoDB (ajout ou mise à jour)
        foreach ($stats as $data) {
            $collection->updateOne(
                ['date' => $data['date']],
                ['$set' => $data],
                ['upsert' => true]
            );
        }
        
        // Message de confirmation
        $output->writeln('Statistiques synchronisées dans MongoDB');
        return Command::SUCCESS;
    }
}