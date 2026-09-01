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

## Accessibilité — ce qui dépend de nous, et ce qui n'en dépend pas

**Rien de l'interface visible ne vient de ce module.** Notre surface front se
limite à deux balises `<link>` (dns-prefetch et preconnect) et à l'enregistrement
du loader et du code de services. Le bandeau, le panneau de réglages et tous
leurs contrôles sont produits par la bibliothèque tarteaucitron.io.

Un audit RGAA de ce module ne trouve donc aucun manquement dans notre code — mais
cela ne signifie pas qu'une boutique qui l'installe est conforme.

### Le point à traiter côté tarteaucitron.io

Le bandeau est inséré **en fin de DOM**. Une personne naviguant au lecteur
d'écran doit donc parcourir toute la page avant de l'atteindre, alors que ce
bandeau conditionne l'accès au site. C'est le reproche récurrent fait à l'outil.

La configuration de tarteaucitron.io permet de **placer le bandeau en tête de
page**. Ce réglage vit dans le tableau de bord tarteaucitron.io, pas dans ce
module : nous utilisons le service hébergé (`load.js` avec identifiant de
compte), et les options d'initialisation y sont gérées côté compte.

**Recommandation** : positionner le bandeau en haut de page depuis le tableau de
bord tarteaucitron.io. C'est le seul levier d'accessibilité réel sur ce
composant, et il ne coûte rien.

### Conséquence pour un audit

Les défauts d'accessibilité éventuels du bandeau relèvent d'une bibliothèque
tierce. Dans un rapport d'audit RGAA, ils se déclarent au titre des contenus
issus d'applications tierces — comme le font les sites publics qui emploient cet
outil. Ce n'est pas une exemption : c'est une attribution de responsabilité.
