<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes
{
    public static function register(): void
    {
        add_shortcode('gamehub', [self::class, 'render_game']);
        add_shortcode('gamehub_rules', [self::class, 'render_rules']);
    }

    public static function render_game(array $atts = []): string
    {
        $atts = shortcode_atts([
            'type' => 'roulette',
            'qr_id' => '',
        ], $atts);

        wp_enqueue_style('gamehub-frontend');
        wp_enqueue_script('gamehub-frontend');

        $allowed = ['hub', 'roulette', 'scratch', 'quiz', 'pickbox', 'memory', 'stoptimer'];
        $type = sanitize_text_field($atts['type']);
        if (!in_array($type, $allowed, true)) {
            $type = 'roulette';
        }

        $settings = get_option('gamehub_settings', []);
        $theme = [
            'primary' => $settings['primary_color'] ?? '#ff6a3d',
            'secondary' => $settings['secondary_color'] ?? '#101828',
            'accent' => $settings['accent_color'] ?? '#7f56d9',
            'dark_mode' => !empty($settings['dark_mode']),
            'font' => $settings['font_family'] ?? 'System',
        ];
        $actions = $settings['actions'] ?? [];
        $logo = function_exists('get_custom_logo') ? get_custom_logo() : '';
        $site_name = get_bloginfo('name');
        $language = $settings['language'] ?? 'fr';
        $i18n = self::get_strings($language);

        $data = [
            'type' => $type,
            'qr_id' => sanitize_text_field($atts['qr_id']),
            'rest_url' => esc_url_raw(rest_url('gamehub/v1/play')),
            'lead_url' => esc_url_raw(rest_url('gamehub/v1/lead')),
            'nonce' => wp_create_nonce('wp_rest'),
            'theme' => $theme,
            'actions' => $actions,
            'site_name' => $site_name,
            'language' => $language,
            'i18n' => $i18n,
            'consent_text' => $settings['consent_text'] ?? '',
            'marketing_email_text' => $settings['marketing_email_text'] ?? '',
            'marketing_sms_text' => $settings['marketing_sms_text'] ?? '',
            'consent_version' => $settings['consent_version'] ?? 'v1',
            'qr_service_url' => $settings['qr_service_url'] ?? '',
        ];

        $json = wp_json_encode($data);

        return sprintf(
            '<section class="gamehub" data-gamehub="%s" style="--gh-primary:%s;--gh-secondary:%s;--gh-accent:%s" data-dark="%s">
                <header class="gamehub__header">
                    <div class="gamehub__brand">
                        <div class="gamehub__logo">%s</div>
                        <div>
                            <p class="gamehub__kicker">Jeu officiel</p>
                            <h2 class="gamehub__restaurant">%s</h2>
                        </div>
                    </div>
                    <div class="gamehub__mode">
                        <button class="gamehub__toggle" type="button" aria-label="Basculer le mode sombre">🌓</button>
                    </div>
                </header>
                <div class="gamehub__hub" data-hub>
                    <h3 class="gamehub__title">Choisissez votre jeu</h3>
                    <div class="gamehub__grid">
                        <button class="gamehub__card" data-select-game="roulette" aria-label="Roulette">Roulette</button>
                        <button class="gamehub__card" data-select-game="scratch" aria-label="Scratch">Scratch</button>
                        <button class="gamehub__card" data-select-game="quiz" aria-label="Quiz">Quiz</button>
                        <button class="gamehub__card" data-select-game="pickbox" aria-label="Pick-a-box">Pick-a-box</button>
                        <button class="gamehub__card" data-select-game="memory" aria-label="Memory">Memory</button>
                        <button class="gamehub__card" data-select-game="stoptimer" aria-label="Stop timer">Stop timer</button>
                    </div>
                    <button class="gamehub__cta" data-start-hub type="button">Jouer</button>
                </div>
                <div class="gamehub__screen" data-screen hidden>
                    <div class="gamehub__status" data-status>Prêt ?</div>
                    <div class="gamehub__stage" data-stage></div>
                    <div class="gamehub__actions">
                        <button class="gamehub__play" type="button">Lancer</button>
                        <button class="gamehub__reset" type="button" hidden>Rejouer</button>
                    </div>
                    <div class="gamehub__result" hidden>
                        <div class="gamehub__result-card">
                            <p class="gamehub__result-label" aria-live="polite"></p>
                            <p class="gamehub__claim"></p>
                            <div class="gamehub__claim-box" hidden>
                                <span class="gamehub__claim-code" data-claim-code></span>
                                <img class="gamehub__claim-qr" alt="QR de retrait" />
                                <button class="gamehub__wallet" type="button">Ajouter au wallet</button>
                            </div>
                            <button class="gamehub__lead">Obtenir mon code</button>
                            <button class="gamehub__bonus" type="button">Actions bonus</button>
                        </div>
                    </div>
                </div>
                <div class="gamehub__modal" role="dialog" aria-modal="true" aria-hidden="true">
                    <div class="gamehub__modal-content" role="document">
                        <button class="gamehub__modal-close" type="button" aria-label="Fermer">✕</button>
                        <h3>Actions bonus</h3>
                        <ul class="gamehub__bonus-list" data-bonus-list></ul>
                        <button class="gamehub__skip" type="button">Passer</button>
                    </div>
                </div>
                <script type="application/json" class="gamehub-data">%s</script>
            </section>',
            esc_attr($data['type']),
            esc_attr($theme['primary']),
            esc_attr($theme['secondary']),
            esc_attr($theme['accent']),
            esc_attr($theme['dark_mode'] ? 'true' : 'false'),
            $logo ? $logo : '<span class="gamehub__logo-fallback">🍽️</span>',
            esc_html($site_name),
            esc_html($json)
        );
    }

    public static function render_rules(): string
    {
        global $wpdb;
        $campaigns = $wpdb->get_results('SELECT * FROM ' . DB::table('campaigns') . ' ORDER BY start_date DESC');
        $prizes = $wpdb->get_results('SELECT * FROM ' . DB::table('prizes') . ' ORDER BY created_at DESC');
        ob_start();
        ?>
        <section class="gamehub-rules">
            <h2>Règlement du jeu</h2>
            <h3>Campagnes</h3>
            <ul>
                <?php foreach ($campaigns as $campaign) : ?>
                    <li><?php echo esc_html($campaign->name . ' (' . $campaign->start_date . ' → ' . ($campaign->end_date ?: 'open') . ')'); ?></li>
                <?php endforeach; ?>
            </ul>
            <h3>Lots</h3>
            <ul>
                <?php foreach ($prizes as $prize) : ?>
                    <li><?php echo esc_html($prize->title . ' - ' . $prize->type); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Le gain est déterminé côté serveur, une participation par 24h et par participant identifié.</p>
            <p>Aucune récompense n’est conditionnée par un avis Google.</p>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function get_strings(string $language): array
    {
        $strings = [
            'fr' => [
                'choose_game' => 'Choisissez votre jeu',
                'play' => 'Jouer',
                'launch' => 'Lancer',
                'get_code' => 'Obtenir mon code',
                'bonus' => 'Actions bonus',
                'result_default' => 'Merci pour votre participation',
                'ready' => 'Prêt ?',
                'error' => 'Erreur réseau, réessayez.',
            ],
            'en' => [
                'choose_game' => 'Choose your game',
                'play' => 'Play',
                'launch' => 'Start',
                'get_code' => 'Get my code',
                'bonus' => 'Bonus actions',
                'result_default' => 'Thanks for playing',
                'ready' => 'Ready?',
                'error' => 'Network error, try again.',
            ],
        ];
        return $strings[$language] ?? $strings['fr'];
    }
}
