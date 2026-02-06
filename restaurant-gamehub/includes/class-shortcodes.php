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
        add_shortcode('loyaltyplay_register', [self::class, 'render_register']);
        add_shortcode('loyaltyplay_login', [self::class, 'render_login']);
        add_shortcode('loyaltyplay_portal', [self::class, 'render_portal']);
    }

    public static function render_register(): string
    {
        if (is_user_logged_in()) {
            return '<div class="gamehub"><p>Vous êtes déjà connecté. <a href="' . esc_url(Utils::get_portal_url()) . '">Accéder au portail</a></p></div>';
        }

        if (!empty($_POST['lp_register_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lp_register_nonce'])), 'lp_register')) {
            $name = sanitize_text_field($_POST['company_name'] ?? '');
            $email = sanitize_email($_POST['email'] ?? '');
            if ($name && $email) {
                $company_id = DB::create_company($name, $email, 0);
                if ($company_id > 0) {
                    return '<div class="gamehub"><p>Inscription créée. <a href="' . esc_url(Utils::get_login_url()) . '">Se connecter</a></p></div>';
                }
            }
            return '<div class="gamehub"><p>Impossible de créer le compte pour le moment.</p></div>';
        }

        ob_start();
        ?>
        <section class="gamehub">
            <h3>Créer votre espace entreprise</h3>
            <form method="post" class="gamehub-form">
                <?php wp_nonce_field('lp_register', 'lp_register_nonce'); ?>
                <label>Nom entreprise <input type="text" name="company_name" required></label>
                <label>Email propriétaire <input type="email" name="email" required></label>
                <button class="button button-primary">Créer mon espace</button>
            </form>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_login(): string
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(Utils::get_portal_url());
            exit;
        }
        return wp_login_form([
            'echo' => false,
            'redirect' => Utils::get_portal_url(),
            'label_log_in' => 'Connexion au portail',
        ]);
    }

    public static function render_portal(): string
    {
        if (!is_user_logged_in()) {
            return '<div class="gamehub"><p>Veuillez vous connecter. <a href="' . esc_url(Utils::get_login_url()) . '">Connexion</a></p></div>';
        }

        $company = DB::get_company_by_site(get_current_blog_id());
        if (!$company) {
            $company = DB::get_company_by_user(get_current_user_id());
        }
        if ($company && in_array($company->status, ['suspended', 'unpaid'], true)) {
            return '<div class="gamehub"><p>Votre portail est temporairement bloqué. Contactez le support.</p></div>';
        }

        $tab = sanitize_key($_GET['lp_tab'] ?? 'dashboard');
        $tabs = [
            'dashboard' => 'Dashboard',
            'games' => 'Games',
            'prizes' => 'Prizes',
            'qr' => 'QR',
            'automations' => 'Automations',
            'leads' => 'Leads',
            'validate' => 'Validate',
            'stats' => 'Stats',
            'billing' => 'Billing',
        ];
        if (!isset($tabs[$tab])) {
            $tab = 'dashboard';
        }

        $stats = DB::get_stats();
        $subscription = $company ? DB::get_company_subscription((int) $company->id) : null;
        $plans = DB::get_all_plans();
        $public_url = Utils::get_public_game_url();

        ob_start();
        ?>
        <section class="gamehub">
            <h3>Portail LoyaltyPlay</h3>
            <nav class="gamehub__grid">
                <?php foreach ($tabs as $key => $label) : ?>
                    <a class="gamehub__card" href="<?php echo esc_url(add_query_arg('lp_tab', $key)); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="gamehub__screen">
                <?php if ($tab === 'dashboard' || $tab === 'stats') : ?>
                    <div class="gamehub__grid">
                        <div class="gamehub__card">Today: <?php echo esc_html($stats['today']); ?></div>
                        <div class="gamehub__card">Total: <?php echo esc_html($stats['total']); ?></div>
                        <div class="gamehub__card">Leads: <?php echo esc_html($stats['leads']); ?></div>
                        <div class="gamehub__card">Claims used: <?php echo esc_html($stats['claimed']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'games' || $tab === 'prizes' || $tab === 'qr') : ?>
                    <p>Page jeu publique: <a href="<?php echo esc_url($public_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($public_url); ?></a></p>
                    <?php echo do_shortcode('[gamehub_play]'); ?>
                <?php endif; ?>

                <?php if ($tab === 'validate') : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                        <?php wp_nonce_field('gamehub_validate_check'); ?>
                        <input type="hidden" name="action" value="gamehub_validate_check">
                        <label>Claim code <input type="text" name="claim_code" required></label>
                        <button class="button">Valider</button>
                    </form>
                <?php endif; ?>

                <?php if ($tab === 'billing') : ?>
                    <p>Plan actuel: <?php echo esc_html($subscription->plan_name ?? 'Aucun'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                        <?php wp_nonce_field('gamehub_upgrade_plan'); ?>
                        <input type="hidden" name="action" value="gamehub_upgrade_plan">
                        <select name="plan_id">
                            <?php foreach ($plans as $plan) : ?>
                                <option value="<?php echo esc_attr($plan->id); ?>"><?php echo esc_html($plan->name . ' - ' . $plan->price_monthly . '€'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button">Mettre à niveau</button>
                    </form>
                <?php endif; ?>

                <?php if ($tab === 'automations' || $tab === 'leads') : ?>
                    <p>Export/Import et webhooks à configurer depuis ce portail (base prête v4.0).</p>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
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

        $allowed = ['roulette', 'scratch', 'quiz', 'pick-a-box', 'memory', 'stop-timer'];
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
                        <button class="gamehub__card" data-select-game="pick-a-box">Pick-a-box</button>
                        <button class="gamehub__card" data-select-game="memory">Memory</button>
                        <button class="gamehub__card" data-select-game="stop-timer">Stop Timer</button>
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
