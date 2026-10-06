# Stras4Water

Application web de gestion développée et maintenue pour l'association Stras4Water.

L'application est utilisée en production pour centraliser une partie des opérations de l'association : adhérents, activités, abonnements, paiements, dons, inscriptions, contrôle d'accès et gestion administrative.

Le projet est également un projet personnel permettant de mettre en pratique et d'approfondir le développement backend avec PHP/Symfony, la conception d'une architecture métier, l'intégration de services externes et la mise en place de fonctionnalités temps réel.

> **Application web utilisée en production dans un contexte associatif réel**
>
> **Backend :** PHP 8.3 / Symfony 7.2  
> **Base de données :** MariaDB / Doctrine ORM  
> **Paiements :** Stripe  
> **Temps réel :** SSE  
> **Intégration :** C# / VirtualDJ

---

## 🎯 Objectifs

L'objectif initial était de remplacer progressivement différents outils et processus manuels utilisés par l'association par une application centralisée.

L'application doit notamment permettre de :

- gérer les adhérents et leurs adhésions ;
- gérer les activités et leurs abonnements ;
- gérer les cartes de séances ;
- gérer les paiements en ligne ;
- gérer les dons ;
- automatiser l'envoi de documents et d'e-mails ;
- contrôler rapidement l'accès aux activités ;
- suivre les opérations financières ;
- fournir des outils adaptés aux bénévoles lors des cours et événements.

Une attention particulière est portée à la simplicité d'utilisation, notamment pour les opérations réalisées depuis un téléphone pendant les cours et les événements.

---

## 🏗️ Architecture

L'application est construite avec Symfony et suit une architecture orientée métier.

```text
src/
├── Controller/
├── Entity/
├── Repository/
├── Service/
├── DTO/
├── Form/
├── Enum/
└── Security/
```

Les contrôleurs restent principalement responsables de la gestion HTTP et délèguent la logique métier à des services dédiés.

Exemples de services métier :

- gestion des inscriptions ;
- gestion du panier ;
- contrôle d'accès ;
- génération des cartes de membre ;
- génération des reçus fiscaux ;
- envoi d'e-mails ;
- export des données.

Cette organisation permet notamment d'éviter de concentrer les règles métier dans les contrôleurs ou les templates Twig.

---

## ⚙️ Fonctionnalités principales

### Gestion des adhérents

- création et modification des adhérents ;
- gestion des adhésions ;
- génération automatique des cartes de membre PDF ;
- envoi de la carte par e-mail ;
- gestion des rôles administrateurs et accueil.

### Activités et abonnements

- gestion des disciplines ;
- abonnements annuels et semestriels ;
- cartes de séances ;
- tarifs réduits ;
- gestion des saisons ;
- gestion des statuts des souscriptions.

Les règles métier prennent notamment en compte :

- le statut de la souscription ;
- la saison ;
- la discipline ;
- le tarif réduit ;
- la validation du justificatif ;
- le nombre de séances restantes.

### Paiements

Paiements en ligne intégrés avec Stripe.

Le paiement est relié au processus métier de l'application afin de créer ou activer les éléments concernés après confirmation du paiement.

Les paiements réalisés directement par l'association peuvent également être enregistrés avec différents moyens de paiement.

### Dons

- gestion des dons ;
- paiement en ligne ;
- génération de reçus fiscaux PDF ;
- envoi automatique des documents par e-mail.

### Contrôle d'accès

Un système de QR Code permet d'identifier rapidement un adhérent lors des cours et événements.

Le contrôle d'accès tient compte des abonnements et cartes actifs et peut appliquer des règles différentes selon le groupe d'activité.

Le système permet notamment de distinguer :

- accès autorisé ;
- justificatif de tarif réduit à vérifier ;
- abonnement ou carte non valide ;
- carte de séances épuisée.

### Administration

Interface d'administration permettant notamment :

- gestion des utilisateurs ;
- inscriptions ;
- gestion des abonnements ;
- gestion des cartes ;
- contrôle des adhésions ;
- exports CSV ;
- gestion des événements ;
- gestion des documents.

---

## 🔄 Fonctionnalités temps réel

Le projet comporte également une partie temps réel utilisée pendant les soirées de l'association.

Une interface d'affichage permet de présenter les informations liées à la musique et aux événements.

Un bridge développé en C# assure notamment la communication entre VirtualDJ et l'application Symfony.

```text
VirtualDJ
    │
    ▼
C# Bridge
    │
    ├── état du lecteur
    ├── informations musicales
    └── événements
    │
    ▼
Symfony
    │
    ▼
Interface temps réel
```

La communication avec l'interface utilise notamment des événements Server-Sent Events (SSE).

Cette partie du projet permet d'expérimenter une architecture multi-technologies PHP/C#/JavaScript avec communication temps réel.

---

## 🧩 Intégrations externes

Le projet utilise plusieurs services et bibliothèques externes :

- Stripe pour les paiements ;
- Symfony Mailer pour les e-mails ;
- Symfony HttpClient pour les communications HTTP ;
- Endroid QR Code pour les QR Codes ;
- FPDF / FPDI pour la génération et manipulation de PDF ;
- VirtualDJ pour la partie musicale ;
- MariaDB pour la persistance des données.

---

## 🔐 Sécurité

L'application utilise notamment :

- Symfony Security ;
- gestion des rôles ;
- protection CSRF sur les actions administratives ;
- contrôle des accès aux fonctionnalités ;
- validation des formulaires ;
- gestion des états des souscriptions ;
- validation des données avant traitement métier.

---

## 🛠️ Technologies

| Technologie | Utilisation |
|---|---|
| PHP 8.3 | Backend |
| Symfony 7.2 | Framework |
| Doctrine ORM / DBAL | Persistance |
| MariaDB | Base de données |
| Twig | Interface |
| Bootstrap | UI |
| Symfony Security | Authentification / autorisation |
| Symfony Mailer | E-mails |
| Symfony HttpClient | HTTP / APIs |
| Stripe | Paiements |
| Endroid QR Code | QR Codes |
| FPDF / FPDI | Documents PDF |
| SSE | Communication temps réel |
| C# | Bridge VirtualDJ |
| Git | Gestion du code |

---

## 💡 Problématiques techniques

Le projet permet notamment de travailler sur des problématiques qui dépassent un simple CRUD.

### Gestion d'états métier

Les abonnements et cartes possèdent plusieurs états :

```text
CREATED
PENDING
ACTIVE
EXPIRED
CANCELLED
```

Le comportement de l'application dépend notamment de l'état de chaque souscription.

### Règles de contrôle d'accès

Le contrôle d'accès ne dépend pas uniquement de l'existence d'une souscription.

Il doit prendre en compte plusieurs paramètres :

- activité concernée ;
- abonnement ou carte ;
- statut ;
- saison ;
- justificatif de tarif réduit ;
- séances restantes.

Ces règles sont encapsulées dans des services métier afin de rester indépendantes de l'interface.

### Paiement et cohérence métier

Le paiement en ligne doit être correctement synchronisé avec la création ou l'activation des éléments métier correspondants.

Cela implique notamment de gérer différents états du processus de paiement et d'éviter de considérer une commande comme finalisée avant confirmation effective du paiement.

### Documents automatisés

Plusieurs documents sont générés automatiquement à partir des données de l'application, notamment :

- cartes de membre ;
- reçus fiscaux ;
- exports administratifs.

### Utilisation en conditions réelles

L'application est utilisée dans le cadre des activités de l'association, avec des contraintes concrètes :

- utilisation depuis un smartphone ;
- contrôles rapides avant les séances ;
- gestion par plusieurs bénévoles ;
- paiements et inscriptions lors des événements ;
- fonctionnement en production.

---

## 🚀 Installation

### Prérequis

- PHP 8.3+
- Composer
- MariaDB
- Symfony CLI (recommandé)

### Installation

```bash
git clone https://github.com/icekni/stras4water.git
cd stras4water

composer install

cp .env .env.local
```

Configurer ensuite la connexion à la base de données et les services externes dans `.env.local`.

Puis :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

symfony server:start
```

---

## 👨‍💻 À propos du projet

Projet développé et maintenu par Cédric Josso pour répondre aux besoins opérationnels réels de l'association Stras4Water.

Le projet constitue également un environnement de développement permettant d'approfondir :

- PHP / Symfony ;
- architecture backend ;
- conception orientée métier ;
- intégration de services externes ;
- paiements en ligne ;
- génération de documents ;
- sécurité applicative ;
- communication temps réel ;
- développement multi-technologies PHP / C#.

Le projet évolue progressivement en fonction des besoins réels rencontrés par l'association.
