<?php

namespace App\Command;

use App\Repository\RideRepository;
use MongoDB\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sync:mysql-to-mongo')]
class SyncMysqlToMongoCommand extends Command
{
    public function __construct(
        private RideRepository $rideRepo
    ) {
        parent::__construct();
    }
private function getMongoClient(): \MongoDB\Client
{
    $uri = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL') ?? trim(shell_exec('echo $MONGODB_URL'));

    if (!$uri) {
        throw new \RuntimeException('MongoDB connection string not found in environment variables.');
    }

    return new \MongoDB\Client($uri);
}
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$mongoClient = new Client(getenv('MONGODB_URL'));
        //$db = $mongoClient->selectDatabase('eco_ride');
        //$collection = $db->selectCollection('stats');
        $client = $this->getMongoClient();
$db = $client->selectDatabase('eco_ride');
$collection = $db->selectCollection('stats');

        // Regrouper les covoiturages par jour
        $rides = $this->rideRepo->findAll();
        $stats = [];

        foreach ($rides as $ride) {
            $day = $ride->getDateDepart()->format('Y-m-d');
            $gain = $ride->getPrixPersonne() * count($ride->getParticipes());

            if (!isset($stats[$day])) {
                $stats[$day] = ['date' => $day, 'nb' => 0, 'gain' => 0];
            }

            $stats[$day]['nb']++;
            $stats[$day]['gain'] += $gain;
        }

        // Insérer ou mettre à jour dans MongoDB
        foreach ($stats as $data) {
            $collection->updateOne(
                ['date' => $data['date']],
                ['$set' => $data],
                ['upsert' => true]
            );
        }

        $output->writeln('Statistiques synchronisées dans MongoDB');
        return Command::SUCCESS;
    }
}