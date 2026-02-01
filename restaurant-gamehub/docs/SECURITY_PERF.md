# Checklist sécurité & performance

## Sécurité
- Calcul serveur des gains via endpoint REST (`/gamehub/v1/play`).
- Validation staff protégée par la capacité `gamehub_validate`.
- Nonces REST (WP REST nonce) pour éviter les abus.
- Validation et sanitation sur toutes les entrées (`sanitize_text_field`, `sanitize_email`).
- Limite 1 play / 24h par user_key ou device hash + rate limit IP.
- HMAC pour claim_code et logs play (ip/user-agent/qr_id).
- Logs de consentements (texte, version, source, IP) + opt-in séparés.
- Aucun gain conditionné à un avis Google.

## Performance
- Tables dédiées pour éviter les surcharges des tables WP natives.
- Index sur les colonnes critiques (game_type, campaign_id, claim_code).
- Endpoints REST légers, timeouts courts pour les webhooks.
- Export CSV direct sans charger toute la page.

## Risques & mitigations
- Fraude multi-participation : user_key + device hash + limites QR/heure/jour.
- Abus IP : rate limit soft + logs d’audit.
- Accès données : rôles dédiés (`gamehub_manage`, `gamehub_export`, `gamehub_validate`).

## Hébergement & caching
- Désactiver le cache full-page sur les endpoints `/gamehub/v1/*`.
- Activer HTTPS et une politique CSP raisonnable.

## Monitoring
- Journaliser les validations staff et changements admin.
- Surveiller les erreurs REST (429/4xx) et les pics de plays.
