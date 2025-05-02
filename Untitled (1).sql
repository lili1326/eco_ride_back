CREATE TABLE `User` (
  `utilisateur_id` INT PRIMARY KEY,
  `pseudo` VARCHAR(50),
  `email` VARCHAR(50),
  `password` VARCHAR(255),
  `photo` VARCHAR(255)  DEFAULT 'default-avatar.png',
  `role_id` INT
);

CREATE TABLE `Role` (
  `role_id` INT PRIMARY KEY,
  `libelle` VARCHAR(50)
);

CREATE TABLE `Ride` (
  `covoiturage_id` INT PRIMARY KEY,
  `date_depart` DATE,
  `heure_depart` TIME,
  `lieu_depart` VARCHAR(50),
  `date_arrivee` DATE,
  `heure_arrivee` TIME,
  `lieu_arrivee` VARCHAR(50),
  `note_conducteur` INT,
  `nb_place` INT,
  `prix_personne` FLOAT,
  `conducteur_id` INT
);

CREATE TABLE `Review` (
  `avis_id` INT PRIMARY KEY,
  `commentaire` TEXT,
  `note` INT,
  `auteur_id` INT,
  `conducteur_id` INT,
  `covoiturage_id` INT,
  `statut` VARCHAR(20),
  `valide_par` INT,
  `date_creation` DATETIME
);

CREATE TABLE `Car` (
  `voiture_id` INT PRIMARY KEY,
  `marque` VARCHAR(50),
  `modele` VARCHAR(50),
  `immatriculation` VARCHAR(50),
  `energie` VARCHAR(50),
  `couleur` VARCHAR(50),
  `date_premiere_immatriculation` DATE,
  `owner_id` INT
);

CREATE TABLE `Participe` (
  `utilisateur_id` INT,
  `covoiturage_id` INT,
  `statut` VARCHAR(20),
  PRIMARY KEY (`utilisateur_id`, `covoiturage_id`)
);

CREATE TABLE `Preference` (
  `preference_id` INT PRIMARY KEY,
  `description` VARCHAR(255),
  `utilisateur_id` INT
);

ALTER TABLE `User` ADD FOREIGN KEY (`role_id`) REFERENCES `Role` (`role_id`);

ALTER TABLE `Ride` ADD FOREIGN KEY (`conducteur_id`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Participe` ADD FOREIGN KEY (`utilisateur_id`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Review` ADD FOREIGN KEY (`auteur_id`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Review` ADD FOREIGN KEY (`conducteur_id`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Review` ADD FOREIGN KEY (`valide_par`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Review` ADD FOREIGN KEY (`covoiturage_id`) REFERENCES `Ride` (`covoiturage_id`);

ALTER TABLE `Car` ADD FOREIGN KEY (`owner_id`) REFERENCES `User` (`utilisateur_id`);

ALTER TABLE `Participe` ADD FOREIGN KEY (`covoiturage_id`) REFERENCES `Ride` (`covoiturage_id`);

ALTER TABLE `Preference` ADD FOREIGN KEY (`utilisateur_id`) REFERENCES `User` (`utilisateur_id`);
