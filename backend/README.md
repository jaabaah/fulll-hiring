# [Backend] Gestion du stationnement d'une flotte de véhicules

Exercice DDD / CQRS de Fulll ([énoncé](https://github.com/fulll/hiring/blob/master/Backend/ddd-and-cqrs-intermediate-senior.md)),
réalisé en PHP 8.4 avec une **architecture hexagonale**.

- **Étape 1** : domaine et cas d'usage en PHP pur, sans framework ni dépendance de production, testés avec Behat sur un stockage en mémoire.
- **Étape 2** : CLI `./fleet` et persistance PostgreSQL, ajoutées comme de simples adaptateurs, sans modifier le domaine ni l'application.
- **Étape 3** : outils qualité et CI, décrits [plus bas](#étape-3--qualité-et-cicd).

## Démarrage rapide

Prérequis : PHP ≥ 8.4 avec l'extension `pdo_pgsql`, Composer et Docker.

```bash
composer install
docker compose up -d   # PostgreSQL 18 sur le port 5442 : bases "fleet" et "fleet_test"
export DATABASE_URL=postgresql://fleet:fleet@127.0.0.1:5442/fleet

FLEET_ID=$(./fleet create john)
./fleet register-vehicle "$FLEET_ID" AB-123-CD
./fleet localize-vehicle "$FLEET_ID" AB-123-CD 43.5283 5.4497 173   # lat lng [alt]
```

| Commande | Effet |
|---|---|
| `./fleet create <userId>` | Crée une flotte et affiche **uniquement** son id sur la sortie standard (capturable avec `$(...)`). |
| `./fleet register-vehicle <fleetId> <vehiclePlateNumber>` | Enregistre un véhicule dans la flotte. |
| `./fleet localize-vehicle <fleetId> <vehiclePlateNumber> lat lng [alt]` | Indique où le véhicule est garé (degrés décimaux, altitude en mètres). |

Une règle métier refusée (véhicule déjà enregistré, déjà garé à cet endroit…), une saisie invalide
(latitude hors bornes, valeur non numérique, plaque trop longue…) ou une modification concurrente de la flotte
affiche un message clair sur la sortie d'erreur, avec le code de sortie `1`.

La connexion se configure avec `DATABASE_URL`, **obligatoire** : sans elle, seules l'aide et `./fleet list` fonctionnent.
Ses paramètres de requête sont transmis à libpq, par exemple `?sslmode=require&connect_timeout=5`.

## Tests et qualité

```bash
composer check   # tout ce que lance la CI : composer validate et audit, style, PHPStan, Deptrac, PHPUnit, Behat
```

La base de test est celle de `docker compose`. Les tests lisent une variable **dédiée**, `TEST_DATABASE_URL`,
et jamais `DATABASE_URL` : comme ils vident leur base, ils ne peuvent pas effacer celle de l'application par erreur.

| Niveau | Outil | Ce qui est testé | Infrastructure |
|---|---|---|---|
| Unitaire | PHPUnit (`composer test:unit`) | Value objects, agrégat `Fleet`, repository en mémoire, CLI complète | Aucune |
| Intégration | PHPUnit (`composer test:integration`) | `PostgresFleetRepository`, `PdoFactory`, l'exécutable `./fleet` lancé dans un processus | PostgreSQL |
| Acceptation | Behat, suite `in_memory` | Les 5 scénarios de l'énoncé | Aucune |
| Acceptation | Behat, suite `postgres` | Les scénarios `@critical` uniquement | PostgreSQL |

- **Les deux suites Behat partagent le même contexte**, qui reçoit en option une URL de base de données :
  on change l'adaptateur, pas le test. Pour travailler sans Docker : `vendor/bin/behat --suite=in_memory`.
- **Un contrat de test commun** (`tests/Contract/FleetRepositoryContractTestCase.php`) s'exécute sur les
  deux repositories. Ils sont donc vérifiés comme interchangeables (substitution de Liskov), et l'adaptateur
  en mémoire utilisé par Behat ne peut pas diverger du vrai comportement.

## Architecture

```
src/
├── Domain/   Modèle métier en PHP pur : agrégat, entité, value objects, exceptions, port
├── App/      Cas d'usage CQRS : commandes, requêtes et leurs handlers
└── Infra/    Adaptateurs : CLI (Symfony Console), PostgreSQL (PDO), en mémoire
```

```
            adaptateur « driving »                         adaptateurs « driven »
   ./fleet ── Cli\*Command ──▶ App\*Handler ──▶ Domain\Fleet
                                    │
                                    └──▶ Domain\FleetRepository (port)
                                                 ▲              ▲
                                InMemoryFleetRepository   PostgresFleetRepository
```

**Les dépendances pointent toujours vers le domaine**, et Deptrac le vérifie à chaque exécution de la CI (`deptrac.yaml`) :

| Couche | Peut dépendre de |
|---|---|
| `Domain` | rien |
| `App` | `Domain` |
| `Infra` | `App`, `Domain`, librairies (Symfony), extension PDO |

Toute autre librairie n'appartient à aucune couche : Deptrac tourne avec `--fail-on-uncovered`, donc l'utiliser fait aussi échouer la CI.

### Le modèle

- **`Fleet`** est l'**agrégat** : la seule porte d'entrée vers ses véhicules. Il garantit les deux règles métier de l'énoncé :
  pas deux fois le même véhicule dans une flotte, et pas deux fois de suite la même position.
- **`Vehicle`** est une entité interne à l'agrégat, identifiée par sa plaque. Elle est **immuable** : se garer renvoie un nouvel état, que la flotte conserve.
- **Les value objects** se valident à la construction et ne peuvent donc pas exister dans un état invalide :
  - `FleetId` : un UUID v4 généré en PHP natif ;
  - `UserId` : au plus 255 caractères imprimables ;
  - `PlateNumber` : normalisée, pour que `ab-123-cd` et `AB-123-CD` soient le même véhicule, et d'au plus 32 caractères imprimables ;
  - `Location` : latitude et longitude bornées, altitude optionnelle, toutes finies (`NAN` et `INF` sont refusés).
  - Les tailles des colonnes SQL découlent de ces limites, et non l'inverse : le test de contrat vérifie que la base accepte les plus longues valeurs du domaine.
- **Les exceptions** implémentent toutes `FleetException`, le seul type que la CLI transforme en message d'erreur. Elles se répartissent en trois familles :
  - `InvalidArgumentException` (`InvalidValue`) pour une donnée invalide ;
  - `DomainException` pour une règle métier refusée ;
  - `RuntimeException` (`FleetModifiedConcurrently`) pour une modification concurrente, qu'il suffit de relancer.
  - Toute autre exception est un bug et garde l'affichage par défaut de Symfony Console. C'est le cas d'un état stocké qui viole une règle du domaine :
    le repository le signale comme une donnée corrompue (`UnexpectedValueException`), pas comme une erreur de l'utilisateur.

### CQRS

| Commandes (écriture) | Requêtes (lecture) |
|---|---|
| `CreateFleet`, `RegisterVehicle`, `ParkVehicle` | `IsVehicleRegistered`, `GetVehicleLocation` |

- Chaque handler suit le même schéma : **charger l'agrégat, lui demander d'agir, sauvegarder**. Aucune règle métier dans les handlers.
- Les commandes et requêtes sont des DTO en types primitifs. N'importe quel point d'entrée (Behat, CLI, une API demain) les utilise sans connaître le domaine.

## Choix de conception et alternatives écartées

- **Un seul agrégat, `Fleet`, plutôt que deux (`Fleet` et `Vehicle`).** Un agrégat se dessine autour des
  **invariants**, pas des objets du monde réel. Les deux règles de l'énoncé portent sur « mon véhicule *dans* ma flotte ».
  - **Conséquence :** un même véhicule enregistré dans deux flottes y a deux positions connues indépendantes. L'application
    ne connaît pas la position *réelle*, mais ce que chaque utilisateur a *déclaré*.
  - **Avec un agrégat `Vehicle` partagé**, la déclaration d'un utilisateur pourrait bloquer celle d'un autre
    (« already parked at this location »), et la position d'une flotte serait visible par l'autre.
  - **Ce choix serait à revoir** si l'application devenait un vrai suivi GPS.
- **`CreateFleetHandler` retourne l'id de la flotte.** C'est une entorse assumée au CQRS strict, où une commande
  ne renvoie rien : `fleet create` doit afficher cet id, et l'appelant n'a pas d'autre moyen de le connaître.
- **Pas de bus de commandes.** Sans framework à l'étape 1, un bus maison n'apporterait rien : les handlers sont appelés directement.
- **Pas d'événements de domaine.** Aucun besoin ne les justifie ici. Des événements sans abonné seraient de la sur-ingénierie.
- **PDO natif plutôt que Doctrine.** C'est zéro dépendance pour 2 tables. L'ORM imposerait du mapping, et DBAL une abstraction
  inutilisée. Le repository :
  - reconstruit la flotte avec `Fleet::reconstitute()`, qui **repasse par les règles du domaine** ;
  - vérifie le type de chaque valeur lue en base ;
  - sauvegarde l'agrégat entier dans **une transaction**, avec des requêtes préparées.
- **Concurrence : verrouillage optimiste.** Deux commandes simultanées sur la même flotte (par exemple `localize-vehicle` et
  `register-vehicle`) chargent le même état. Sans protection, la seconde sauvegarde réécrirait tous les véhicules avec cet état périmé et
  effacerait la première. Une colonne `fleets.version` est donc vérifiée et incrémentée à chaque sauvegarde : la seconde commande échoue
  avec `FleetModifiedConcurrently`, sans rien écrire, et peut être relancée.
  - La version est mémorisée par le repository (un `WeakMap` par flotte chargée), pas par l'agrégat : le domaine ignore la persistance.
  - Les deux repositories appliquent la même règle, vérifiée par le test de contrat.
- **Le composant `symfony/console`, pas le framework Symfony.** C'est autorisé par l'énoncé à l'étape 2, et il est
  limité à la couche `Infra`, ce que Deptrac vérifie. L'assemblage se fait par injection de dépendances manuelle :
  `FleetApplication::create()` branche les handlers sur un repository, et l'exécutable `fleet` lui fournit le repository PostgreSQL.
  Un conteneur serait superflu pour 3 commandes.
  - Ce repository est un *lazy proxy* natif de PHP 8.4 : la base n'est contactée qu'à la première requête. `./fleet list` fonctionne
    donc sans base, et une configuration absente ou une base injoignable s'affiche comme une erreur de la commande (code `1`).
- **Les coordonnées négatives.** Par défaut, Symfony Console lit `-33.8688` comme l'option `-3`.
  `CoordinatesArgvInput` traite tout nombre comme un argument (la CLI n'a aucune option numérique), et un test le couvre.

## Étape 3 : qualité et CI/CD

### Outils qualité

| Outil | Pourquoi |
|---|---|
| **PHPStan**, niveau max | Analyse statique : erreurs de type, `null` non géré, code mort. Il trouve des bugs sans exécuter le code. |
| **PHP-CS-Fixer**, PER-CS 3.0 | Un style uniforme et automatique : les revues de code portent sur le fond, pas sur la forme. |
| **Deptrac** | Fait respecter l'architecture hexagonale : la CI échoue si une couche dépend de ce qu'elle n'a pas le droit de connaître. |
| **`composer audit`** | Détecte les failles de sécurité connues dans les dépendances. |
| PHPUnit et Behat | Tests unitaires, d'intégration et d'acceptation (voir plus haut). |

Pour aller plus loin, on pourrait ajouter **Infection** (mutation testing), qui mesure si les tests détectent
vraiment les régressions, et **Rector**, qui automatise les montées de version de PHP.

### Intégration continue (en place)

Le workflow `.github/workflows/ci.yml` s'exécute à chaque push et à chaque pull request :

1. **Deux jobs en parallèle**, `algo` et `backend` ;
2. **Dans le job backend** : PHP 8.4 avec `pdo_pgsql`, le cache Composer et un service PostgreSQL 18 ;
3. **Les contrôles, du plus rapide au plus lent** : `composer validate`, `composer audit`, style, PHPStan, Deptrac,
   tests unitaires, puis création du schéma, tests d'intégration et Behat (les deux suites).

Une étape par contrôle, pour voir immédiatement lequel a échoué. Les droits sont minimaux (`contents: read`).
Les actions sont épinglées par SHA de commit, et Dependabot (`.github/dependabot.yml`) propose leurs mises à jour.

### Déploiement continu (décrit, non mis en place faute d'environnement cible)

1. **À chaque tag de version** : construire une image Docker de la CLI et la publier dans un registre.
2. **Appliquer les migrations de schéma** (avec un vrai outil, comme Doctrine Migrations) avant de déployer le code, et les garder rétrocompatibles.
3. **Déployer en staging**, puis lancer les scénarios `@critical` sur cet environnement.
4. **Passer en production** après une validation manuelle, en gardant la possibilité de revenir à l'image précédente.

## Limites connues et évolutions possibles

- **Migrations.** Le schéma est appliqué par `database/schema.sql` au premier démarrage du conteneur.
  Il a déjà évolué une fois (colonne `version`, contrainte sur l'altitude) : une base créée avant se recrée avec
  `docker compose down -v && docker compose up -d`, ce qui efface ses données. La prochaine évolution justifierait un outil de migrations.
- **Événements de domaine** (`VehicleParked`…). Ils seraient à introduire si un besoin réel apparaît : notification, historique des positions, projection de lecture.
- **Modèle de lecture dédié.** Les requêtes relisent aujourd'hui l'agrégat complet, ce qui suffit à cette échelle.
  Avec beaucoup de véhicules, on lirait directement en SQL.
