# Upgrade — astral-shop 1.x → 2.0

## Prérequis

- PHP **8.3+**
- `"astral-php/astral-core": "^2.1"`

## Breaking

| 1.x | 2.0 |
|-----|-----|
| PHP ^8.1 | PHP ^8.3 |
| core ^1.2 | core ^2.1 |
| `Role::ADMIN` | `Role::Admin` (enum) |
| `Role::USER` | `Role::User` |
| `Role::GUEST` | `Role::Guest` |
| payment ^0.1.3 | ^0.1.4 |
| paypal ^0.1 | ^0.1.1 |
| shipping ^0.5.3 | ^0.5.4 |
| worker ^0.1 | ^0.1.1 |

Les valeurs string (`admin`, `user`, `guest`) restent valides en base.

```bash
composer require astral-php/astral-core:^2.1
composer update
```

Voir aussi : [astral-core UPGRADE-2.0](https://github.com/astral-php/astral-core/blob/main/UPGRADE-2.0.md) · [UPGRADE-2.1](https://github.com/astral-php/astral-core/blob/main/UPGRADE-2.1.md).
