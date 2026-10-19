# [Algo] FizzBuzz

Affiche les nombres de 1 à N : `Fizz` pour les multiples de 3, `Buzz` pour les multiples de 5,
`FizzBuzz` pour les multiples de 3 et 5, sinon le nombre.

## Prérequis

- PHP >= 8.4
- Composer

## Installation & utilisation

```bash
composer install
bin/fizzbuzz 100
```

## Tests et qualité

```bash
composer test    # tests unitaires PHPUnit
composer check   # tout ce que lance la CI : composer validate, style (PHP-CS-Fixer), analyse statique (PHPStan niveau max), tests
```

## Choix de conception

| Élément | Rôle |
|---|---|
| `src/Rule.php` | Value object immuable : un diviseur et un mot. Il sait seulement dire s'il s'applique à un nombre. |
| `src/FizzBuzz.php` | Convertit un nombre grâce à une liste de règles, et génère la séquence de 1 à N. |
| `bin/fizzbuzz` | Point d'entrée CLI : il valide l'argument et affiche le résultat, rien de plus. |

- **Pas de cas spécial pour 15** : les mots des règles qui s'appliquent sont concaténés dans l'ordre,
  donc `FizzBuzz` découle naturellement des règles 3 et 5.
- **Ouvert/fermé (SOLID)** : ajouter une règle (ex. `new Rule(7, 'Bazz')`) ne demande aucune
  modification de la classe `FizzBuzz` (voir le test `itSupportsNewRulesWithoutModification`).
- **Responsabilité unique** : le calcul (`FizzBuzz`) est séparé de l'affichage (`bin/fizzbuzz`),
  ce qui permet de tester le calcul sans capturer la sortie standard.
- **Scalabilité** : `upTo()` est un générateur (`yield`). Le temps est en O(N), le minimum pour afficher N lignes,
  et la mémoire en O(1) : aucun tableau de N éléments n'est construit.
  Mesuré : N = 10 000 000 avec environ 27 Mo de mémoire, la même empreinte que pour N = 15.
- **Typage strict et immuabilité** : `declare(strict_types=1)`, classes `final readonly`, constructeur variadique
  `Rule ...$rules` (le typage garantit qu'on ne passe que des règles).
- **Validation aux frontières** : `Rule` et `upTo()` rejettent les valeurs invalides, `upTo()` dès l'appel
  et non à la première itération (seule la séquence est paresseuse), et la CLI renvoie un code de sortie 1 avec un message d'usage.
- **Volontairement simple** : pas d'interface `RuleInterface` tant qu'il n'existe qu'une sorte de règle (YAGNI).
