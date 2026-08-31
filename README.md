# Stras4Water

Application web de gestion développée pour l'association Stras4Water.

L'application centralise la gestion des adhérents, activités, abonnements, paiements, dons et opérations administratives de l'association.

## Fonctionnalités principales

* Gestion des utilisateurs et des adhésions
* Gestion des activités, disciplines et abonnements
* Paiement en ligne via Stripe
* Gestion des dons et génération de reçus fiscaux PDF
* Génération et lecture de QR Codes pour le contrôle des adhésions
* Gestion des inscriptions et du contrôle d'accès aux activités
* Gestion comptable et export des données
* Envoi d'e-mails transactionnels
* Interface d'administration
* Affichage temps réel des informations liées aux événements
* Intégration avec Mixxx pour la gestion musicale des soirées

## Architecture

L'application repose sur Symfony et suit une organisation séparant notamment :

* Controllers
* Services métier
* DTO
* Entities
* Repositories
* Forms
* Security
* Enums

La logique métier est principalement encapsulée dans des services dédiés afin de limiter les responsabilités des contrôleurs et faciliter la maintenance de l'application.

## Technologies

* PHP 8.2+
* Symfony 7.2
* Doctrine ORM / DBAL
* MariaDB
* Symfony Security
* Symfony Mailer
* Symfony HttpClient
* Stripe
* WebSocket
* Twig
* Endroid QR Code
* FPDF / FPDI
* Git

## Contexte

Projet développé et maintenu par Cédric Josso pour répondre aux besoins opérationnels réels de l'association Stras4Water.

Le projet constitue également un terrain d'expérimentation autour de l'architecture backend Symfony, des intégrations de services externes, du temps réel et de l'automatisation de processus métier.
