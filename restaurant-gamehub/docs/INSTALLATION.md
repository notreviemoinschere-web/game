# Restaurant GameHub - Installation Multisite

## Prérequis
- WordPress 6.4+
- PHP 8.1+
- Multisite activé en sous-domaines

## Installation
1. Copier le dossier `restaurant-gamehub` dans `wp-content/plugins/`.
2. Network Activate depuis l'admin réseau.
3. Pour chaque sous-site (restaurant), accéder à **GameHub > Settings** et configurer :
   - Format des codes (`RESTO-XXXXXX`)
   - Expiration des gains
   - Palette (primary/secondary/accent), typographie et mode sombre
   - Webhooks (optionnel)
   - QR service URL (optionnel, pour générer un QR côté client)
4. Créer au moins une campagne active dans **GameHub > Campaigns**.
5. Ajouter des lots gagnants et de consolation dans **GameHub > Prizes**.
6. Générer vos QR dans **GameHub > QR Codes**.
7. Ajouter les shortcodes sur vos pages.

## Shortcodes
- `[gamehub type="roulette"]`
- `[gamehub type="scratch"]`
- `[gamehub type="quiz"]`
- `[gamehub type="pickbox"]`
- `[gamehub type="memory"]`
- `[gamehub type="stoptimer"]`
- `[gamehub_rules]` (règlement auto)

## Validation staff
- Accédez à **GameHub > Staff Validation**.
- Saisissez le code (RESTO-XXXXXX) pour marquer un gain comme utilisé.

## Rôles
- `GameHub Staff` : validation des codes uniquement.
- `Administrator` : gestion complète, export et configuration.

## Notes Multisite
- Chaque sous-site possède ses propres options et tables.
- L'activation réseau crée les tables pour tous les sites existants.
- Pour un nouveau sous-site, réactivez le plugin ou utilisez l'outil de maintenance WP-CLI.
