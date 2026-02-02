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
        add_shortcode('gamehub_play', [self::class, 'render_public']);
    }

    public static function render_public(): string
    {
        $settings = get_option('gamehub_settings', []);
        $type = $settings['active_game'] ?? 'roulette';
        $token = sanitize_text_field($_GET['gh'] ?? '');
        if (!$token || !Utils::is_public_token_valid($token)) {
            return '<div class="gamehub"><p>QR indisponible. Veuillez contacter le restaurant.</p></div>';
        }
        return self::render_game([
            'type' => $type,
            'qr_id' => Utils::get_qr_id(),
            'token' => $token,
            'test' => sanitize_text_field($_GET['test'] ?? ''),
            'sig' => sanitize_text_field($_GET['sig'] ?? ''),
        ]);
    }

    public static function render_game(array $atts = []): string
    {
        $atts = shortcode_atts([
            'type' => 'roulette',
            'qr_id' => '',
            'token' => '',
        ], $atts);

        wp_enqueue_style('gamehub-frontend');
        wp_enqueue_script('gamehub-frontend');

        $allowed = ['roulette', 'scratch', 'quiz'];
        $type = sanitize_text_field($atts['type']);
        if (!in_array($type, $allowed, true)) {
            $type = 'roulette';
        }

        $qr_id = sanitize_text_field($atts['qr_id']);
        if (!$qr_id) {
            $qr_id = Utils::get_qr_id();
        }
        $token = sanitize_text_field($atts['token']);
        if (!$token) {
            $token = Utils::get_public_token();
        }

        $data = [
            'type' => $type,
            'qr_id' => $qr_id,
            'rest_url' => esc_url_raw(rest_url('gamehub/v1/play')),
            'lead_url' => esc_url_raw(rest_url('gamehub/v1/lead')),
            'nonce' => wp_create_nonce('wp_rest'),
            'token' => $token,
            'test' => sanitize_text_field($atts['test'] ?? ''),
            'sig' => sanitize_text_field($atts['sig'] ?? ''),
        ];

        $json = wp_json_encode($data);

        return sprintf(
            '<section class="gamehub" data-gamehub="%s">
                <div class="gamehub__screen">
                    <h3 class="gamehub__title">Choisissez votre jeu</h3>
                    <div class="gamehub__grid">
                        <button class="gamehub__card" data-select-game="roulette">Roulette</button>
                        <button class="gamehub__card" data-select-game="scratch">Scratch</button>
                        <button class="gamehub__card" data-select-game="quiz">Quiz</button>
                    </div>
                    <div class="gamehub__stage" data-stage></div>
                    <button class="gamehub__play">Jouer</button>
                    <div class="gamehub__result" hidden>
                        <p class="gamehub__result-label"></p>
                        <p class="gamehub__claim"></p>
                        <button class="gamehub__lead">Obtenir mon code</button>
                    </div>
                </div>
                <script type="application/json" class="gamehub-data">%s</script>
            </section>',
            esc_attr($data['type']),
            esc_html($json)
        );
    }
}
