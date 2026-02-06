# Restaurant GameHub

Plugin WordPress Multisite pour restaurants : QR → jeu → gain/consolation → code de retrait → stats → leads.

## Arborescence
```
restaurant-gamehub/
├── admin/
│   ├── css/admin.css
│   └── js/admin.js
├── assets/
│   ├── css/gamehub.css
│   └── js/gamehub.js
├── docs/
│   ├── DB_SCHEMA.md
│   ├── INSTALLATION.md
│   └── SECURITY_PERF.md
├── includes/
│   ├── class-activator.php
│   ├── class-admin.php
│   ├── class-db.php
│   ├── class-plugin.php
│   ├── class-rest.php
│   ├── class-shortcodes.php
│   └── class-utils.php
└── restaurant-gamehub.php
```

## Points clés
- Parcours SaaS : Super Admin crée des entreprises, attribue des plans (free/pro/elite) et pilote tous les espaces.
- Création d'entreprise : génération de l'espace (site), page jeu, token public, QR et configuration initiale.
- Dashboard entreprise : configuration du jeu, lots, validation caisse, statistiques et upgrade de plan.
- Endpoints REST pour play, leads, validation.

Consultez `docs/INSTALLATION.md` pour l'installation complète.

## Portail Front-only v4
- Pages front auto: `/loyaltyplay-signup/`, `/loyaltyplay-login/`, `/loyaltyplay-portal/`.
- Entreprises/staff redirigés hors wp-admin vers le portail front.
- Support des types de jeu: roulette, scratch, quiz, pick-a-box, memory, stop-timer.
