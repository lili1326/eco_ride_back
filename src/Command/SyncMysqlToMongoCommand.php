<?php
use App\Repository\RideRepository;
use MongoDB\Client as MongoClient;

#[AsCommand(name: 'sync:mysql-to-mongo')]
class SyncMysqlToMongoCommand extends Command
{
    public function __construct(
        private RideRepository $rideRepo,
        private MongoClient $mongoClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->mongoClient->selectDatabase('eco_ride');
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