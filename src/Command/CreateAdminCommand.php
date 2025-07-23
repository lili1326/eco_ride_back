<?php
// Déclare le namespace de la commande
namespace App\Command;

// Importe l'entité Admin que l'on va créer
use App\Entity\Admin;

// Importation des classes nécessaires pour créer une commande Symfony
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
// Importation de Doctrine pour gérer la base de données
use Doctrine\ORM\EntityManagerInterface;

// Importation du service de hashage de mot de passe
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Attribut PHP qui déclare la commande avec le nom à utiliser dans le terminal
 #[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
   // Constructeur avec injection de dépendances
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        // Appel du constructeur de la classe Command
        parent::__construct();
    }
  // Méthode exécutée lorsque la commande est appelée dans le terminal
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
    // Création d’un nouvel objet Admin
        $admin = new Admin();
        $admin->setEmail('admin@ecoride.mail');
        $admin->setPseudo('ecoadmin');
        $admin->setRoles(['ROLE_ADMIN']);

    // Hashage du mot de passe pour sécuriser les données     
        $admin->setPassword($this->hasher->hashPassword($admin, 'Ecoride21+'));

    // Génération d’un token API aléatoire 40 caractères hexadécimaux
         $admin->setApiToken(bin2hex(random_bytes(20)));  

    // Enregistrement de l’admin dans la base de données
        $this->em->persist($admin);// Marque l’objet pour enregistrement
        $this->em->flush();// Exécute l’enregistrement en base

        $output->writeln('Admin créé avec succès.');
        return Command::SUCCESS;
    }
}