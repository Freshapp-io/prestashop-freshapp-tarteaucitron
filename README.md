# FreshApp Tarteaucitron

Gestionnaire de consentement cookies (RGPD) pour PrestaShop 9, basé sur
[tarteaucitron.io](https://tarteaucitron.io/) — bibliothèque open source
développée par Amauri Champeaux.

- **Nom technique** : `freshapptarteaucitron`
- **Version** : 1.4.0
- **Auteur** : FreshApp.io
- **Compatibilité** : PrestaShop >= 9.0.0

## Fonctionnement

Le module injecte le loader tarteaucitron dans le `<head>` du front-office et
permet d'ajouter des services personnalisés (code JS) depuis le back-office.

Deux modes de chargement :

- **CDN** (défaut) — le loader est appelé directement sur tarteaucitron.io ;
- **Local** — le loader est téléchargé et mis en cache dans
  `views/js/tac-loader.cache.js`, rafraîchi selon un TTL configurable.
  Utile pour éviter une dépendance externe au rendu de page.

Le code JS de services saisi en back-office est écrit dans
`views/js/custom-services.cache.js` et enregistré comme asset statique
(même principe que `freshapphtmleverywhere`).

## Configuration (clés `Configuration`)

| Clé | Rôle |
|---|---|
| `FA_TAC_UUID` | API ID du compte tarteaucitron.io |
| `FA_TAC_DOMAIN` | Domaine déclaré côté tarteaucitron.io |
| `FA_TAC_JSCODE` | Code JS des services (mode non automatique) |
| `FA_TAC_LOCAL_ENABLED` | Active le cache local du loader |
| `FA_TAC_LOCAL_TTL` | Durée de validité du cache local, en jours |
| `FA_TAC_LOCAL_LAST_DL` | Timestamp du dernier téléchargement (interne) |
| `FA_TAC_LOCAL_UUID` | UUID associé au cache courant (interne) |
| `FA_TAC_LOCAL_ERROR` | Dernière erreur de téléchargement (interne) |

## Hooks

- `displayHeader`
- `actionFrontControllerSetMedia`

## Installation

1. Créer un compte gratuit sur https://tarteaucitron.io/
2. Récupérer l'API ID sur https://tarteaucitron.io/dashboard/#account
3. Le renseigner dans la configuration du module en back-office.

## Fichiers générés (non versionnés)

- `views/js/tac-loader.cache.js` — loader téléchargé depuis le CDN
- `views/js/custom-services.cache.js` — services JS saisis en back-office
