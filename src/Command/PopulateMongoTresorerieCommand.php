<?php

namespace App\Command;

use MongoDB\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use MongoDB\BSON\UTCDateTime;

#[AsCommand(
    name: 'app:populate-mongo-tresorerie',
    description: 'Ajoute des crédits initiaux et des retraits simulés dans MongoDB.'
)]
class PopulateMongoTresorerieCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$client = new Client("mongodb://localhost:27017");
        $client = new \MongoDB\Client($_SERVER['MONGODB_URL']);
        $collection = $client->selectCollection('covoiturage', 'tresorerie');

        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $userId = $user->getId();
            $now = new \DateTime();

            // +20 crédits à l'inscription (simulation)
            $collection->insertOne([
                'user_id' => $userId,
                'montant' => 20,
                'date' => new UTCDateTime($now->getTimestamp() * 1000)
            ]);

            // -2 crédits pour 2 trajets réservés (simulation)
            for ($i = 0; $i < 2; $i++) {
                $collection->insertOne([
                    'user_id' => $userId,
                    'montant' => -2,
                    'date' => new UTCDateTime($now->modify('+1 minute')->getTimestamp() * 1000)
                ]);
            }
        }

        $output->writeln(' Crédits ajoutés dans MongoDB pour chaque utilisateur.');
        return Command::SUCCESS;
    }
}