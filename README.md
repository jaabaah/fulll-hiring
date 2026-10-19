# Tests techniques Fulll : Architecte PHP

Réalisation des exercices **Algo** et **Backend** du dépôt [fulll/hiring](https://github.com/fulll/hiring), en PHP 8.4.

| Exercice | Sujet | Détails |
|---|---|---|
| [`algo/`](algo/) | FizzBuzz | [README](algo/README.md) |
| [`backend/`](backend/) | Gestion du stationnement d'une flotte de véhicules (DDD, CQRS, architecture hexagonale, CLI, PostgreSQL) | [README](backend/README.md) |

## En bref

- **Tests d'abord** : PHPUnit pour les tests unitaires et d'intégration, Behat pour les scénarios de l'énoncé, repris mot pour mot.
- **Énoncé respecté à la lettre** : aucun framework ni dépendance de production à l'étape 1 du backend.
  Seul le composant `symfony/console` est utilisé à l'étape 2, et il est cantonné à l'infrastructure.
- **Qualité vérifiée automatiquement** :
  - PHPStan au niveau max ;
  - PHP-CS-Fixer (PER-CS 3.0) ;
  - Deptrac, qui fait respecter l'architecture hexagonale ;
  - `composer audit`.
- **Intégration continue** avec GitHub Actions (`.github/workflows/ci.yml`) : un job par exercice, et un service PostgreSQL pour le backend.

## Lancer les vérifications

Prérequis : PHP ≥ 8.4 (avec `pdo_pgsql` pour le backend), Composer et Docker.

```bash
cd algo && composer install && composer check
```

```bash
cd backend && composer install && docker compose up -d && composer check
```

`composer check` lance, dans chaque projet, exactement les contrôles exécutés par la CI.
