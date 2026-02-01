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
- 6 jeux via shortcode `[gamehub type="..."]`.
- Endpoints REST pour play, leads, validation.
- Tables custom par sous-site.
- Exports CSV et webhooks.

Consultez `docs/INSTALLATION.md` pour l'installation complète.
