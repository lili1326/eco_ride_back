<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\User;
use App\Entity\Car;
use App\Entity\Ride;
use App\Entity\Preference;
use App\Entity\Review;
use App\Entity\Participe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppDataFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Admin
        $admin = new Admin();
        $admin->setEmail('admin@ecoride.com');
        $admin->setPseudo('AdminEcoride');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $admin->setApiToken(bin2hex(random_bytes(20)));
        $manager->persist($admin);

        // Users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user$i@test.com");
            $user->setPseudo($faker->userName());
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setPassword($this->hasher->hashPassword($user, 'userpass'));
            $manager->persist($user);
            $users[] = $user;

            // Car for each user
            $car = new Car();
            $car->setMarque($faker->company());
            $car->setModele($faker->word());
            $car->setDatePremiereImmatriculation($faker->dateTimeThisDecadeImmutable());
            $car->setImmatriculation(strtoupper($faker->bothify('??-###-??')));
            $car->setCouleur($faker->safeColorName());
            $car->setEnergie('Essence');
            $car->setNbPlaces($faker->numberBetween(3, 5));
            $car->setOwner($user);
            $car->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($car);

            // Preference
            $preference = new Preference();
            $preference->setUtilisateur($user);
            $preference->setDescription($faker->sentence());
            $preference->setMusique($faker->randomElement(['classique', 'pop', 'rock']));
            $preference->setFumeur($faker->randomElement(['oui', 'non']));
            $preference->setAnimaux($faker->randomElement(['oui', 'non']));
            $preference->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($preference);
        }

        // Rides
        $rides = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 2; $i++) {
                $ride = new Ride();
                $ride->setConducteur($user);
                $ride->setVoiture($user->getCars()->first());
                $ride->setDateDepart($faker->dateTimeBetween('+1 days', '+1 month'));
                $ride->setHeureDepart($faker->dateTimeBetween('08:00', '10:00'));
                $ride->setHeureArrivee($faker->dateTimeBetween('11:00', '13:00'));
                $ride->setLieuDepart($faker->city());
                $ride->setLieuArrivee($faker->city());
                $ride->setNbPlace($faker->numberBetween(1, 4));
                $ride->setPrixPersonne($faker->numberBetween(5, 25));
                $ride->setStatut('en_attente');
                $ride->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($ride);
                $rides[] = $ride;
            }
        }

        // Participations + Reviews
        foreach ($users as $user) {
            $randomRide = $faker->randomElement($rides);
            if ($randomRide->getConducteur() !== $user) {
                $participe = new Participe();
                $participe->setUtilisateur($user);
                $participe->setCovoiturage($randomRide);
                $participe->setStatut('confirmé');
                $manager->persist($participe);

                $review = new Review();
                $review->setAuteur($user);
                $review->setConducteur($randomRide->getConducteur());
                $review->setCovoiturage($randomRide);
                $review->setCommentaire($faker->sentence());
                $review->setNote($faker->numberBetween(3, 5));
                $review->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($review);
            }
        }

        $manager->flush();
    }
}