-- Exported from QuickDBD: https://www.quickdatabasediagrams.com/
-- Link to schema: https://app.quickdatabasediagrams.com/#/d/EmCy13
-- NOTE! If you have used non-SQL datatypes in your design, you will have to change these here.


CREATE TABLE `Utilisateur` (
    `utilisateur_id` INT  NOT NULL ,
    `nom` VARCHAR(50)  NOT NULL ,
    `prenom` VARCHAR(50)  NOT NULL ,
    `email` VARCHAR(50)  NOT NULL ,
    `password` VARCHAR(50)  NOT NULL ,
    `telephone` VARCHAR(50)  NOT NULL ,
    `adresse` VARCHAR(50)  NOT NULL ,
    `date_naissance` VARCHAR(50)  NOT NULL ,
    `photo` BLOB  NOT NULL ,
    `pseudo` VARCHAR(50)  NOT NULL ,
    PRIMARY KEY (
        `utilisateur_id`
    )
);

CREATE TABLE `Role` (
    `role_id` INT  NOT NULL ,
    `libelle` VARCHAR(50)  NOT NULL ,
    PRIMARY KEY (
        `role_id`
    )
);

CREATE TABLE `Avis` (
    `avis_id` INT  NOT NULL ,
    `commentaire` VARCHAR(50)  NOT NULL ,
    `note` VARCHAR(50)  NOT NULL ,
    `statut` VARCHAR(50)  NOT NULL ,
    PRIMARY KEY (
        `avis_id`
    )
);

CREATE TABLE `Voiture` (
    `voiture_id` INT  NOT NULL ,
    `marque` VARCHAR(50)  NOT NULL ,
    `modele` VARCHAR(50)  NOT NULL ,
    `immatriculation` VARCHAR(50)  NOT NULL ,
    `energie` VARCHAR(50)  NOT NULL ,
    `couleur` VARCHAR(50)  NOT NULL ,
    `date_premiere_immatriculation` VARCHAR(50)  NOT NULL ,
    PRIMARY KEY (
        `voiture_id`
    )
);

CREATE TABLE `Covoiturage` (
    `covoiturage_id` INT  NOT NULL ,
    `pseudo` VARCHAR(50)  NOT NULL ,
    `date_depart` DATE  NOT NULL ,
    `heure_depart` DATE  NOT NULL ,
    `lieu_depart` VARCHAR(50)  NOT NULL ,
    `date_arrivee` DATE  NOT NULL ,
    `heure_arrivee` VARCHAR(50)  NOT NULL ,
    `lieu_arrivee` VARCHAR(50)  NOT NULL ,
    `statut` VARCHAR(50)  NOT NULL ,
    `nb_place` INT  NOT NULL ,
    `prix_personne` FLOAT  NOT NULL ,
    PRIMARY KEY (
        `covoiturage_id`
    )
);

CREATE TABLE `Participe` (
    `utilisateur_id` MANY_TO_ONE  NOT NULL ,
    `covoiturage_id` MANY_TO_ONE  NOT NULL 
);

CREATE TABLE `Preference` (
    `preference_id` INT  NOT NULL ,
    `description` VARCHAR(255)  NOT NULL ,
    PRIMARY KEY (
        `preference_id`
    )
);

ALTER TABLE `Participe` ADD CONSTRAINT `fk_Participe_utilisateur_id` FOREIGN KEY(`utilisateur_id`)
REFERENCES `Utilisateur` (`utilisateur_id`);

ALTER TABLE `Participe` ADD CONSTRAINT `fk_Participe_covoiturage_id` FOREIGN KEY(`covoiturage_id`)
REFERENCES `Covoiturage` (`covoiturage_id`);

