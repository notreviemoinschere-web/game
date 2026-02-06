# Schéma DB (tables par sous-site)

| Table | Description |
| --- | --- |
| `{prefix}gamehub_plays` | Historique des parties et résultats. |
| `{prefix}gamehub_audit` | Logs d’audit admin (qui a modifié quoi). |
| `{prefix}gamehub_campaigns` | Campagnes avec dates et statut. |
| `{prefix}gamehub_prizes` | Lots gagnants / consolations avec poids et stock. |
| `{prefix}gamehub_claims` | Codes de retrait et statut. |
| `{prefix}gamehub_leads` | Leads collectés. |
| `{prefix}gamehub_consents` | Logs de consentements. |
| `{prefix}gamehub_qr_codes` | QR codes par label et type de jeu. |

Les tables sont créées via `dbDelta()` lors de l'activation réseau ou locale.
