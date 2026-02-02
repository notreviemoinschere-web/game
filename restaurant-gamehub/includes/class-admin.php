<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        add_menu_page(
            'GameHub',
            'GameHub',
            'gamehub_manage',
            'gamehub-dashboard',
            [self::class, 'render_dashboard'],
            'dashicons-games',
            58
        );
        add_submenu_page('gamehub-dashboard', 'Validation caisse', 'Validation caisse', 'gamehub_validate', 'gamehub-validation', [self::class, 'render_validation']);
        add_submenu_page('gamehub-dashboard', 'Réglages', 'Réglages', 'gamehub_manage', 'gamehub-settings', [self::class, 'render_settings']);
    }

    public static function enqueue_assets(string $hook): void
    {
        if (strpos($hook, 'gamehub') === false) {
            return;
        }
        wp_enqueue_style('gamehub-admin');
        wp_enqueue_script('gamehub-admin');
    }

    public static function render_dashboard(): void
    {
        $settings = get_option('gamehub_settings', []);
        $active_game = $settings['active_game'] ?? 'roulette';
        $stats = DB::get_stats();
        $public_url = Utils::get_public_game_url();
        $qr_id = Utils::get_qr_id();
        $prizes_win = DB::get_prizes_by_type('win');
        $prizes_consolation = DB::get_prizes_by_type('consolation');
        ?>
        <div class="wrap gamehub-dashboard">
            <h1>GameHub — Dashboard</h1>

            <section class="gamehub-card">
                <h2>Configuration rapide</h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                    <?php wp_nonce_field('gamehub_activate_now'); ?>
                    <input type="hidden" name="action" value="gamehub_activate_now">
                    <label>Email de notification (option)
                        <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? ''); ?>" class="regular-text">
                    </label>
                    <label>Jeu actif
                        <select name="active_game">
                            <option value="roulette" <?php selected($active_game, 'roulette'); ?>>Roulette</option>
                            <option value="scratch" <?php selected($active_game, 'scratch'); ?>>Scratch</option>
                            <option value="quiz" <?php selected($active_game, 'quiz'); ?>>Quiz</option>
                        </select>
                    </label>
                    <label>Cooldown (heures)
                        <input type="number" name="cooldown_hours" value="<?php echo esc_attr($settings['play_limit_hours'] ?? 24); ?>" min="1">
                    </label>
                    <label class="gamehub-switch">
                        <input type="checkbox" name="test_mode" value="1" <?php checked(!empty($settings['test_mode'])); ?>>
                        <span>Mode test</span>
                    </label>
                    <button class="button button-primary">Activer maintenant</button>
                    <?php if (!empty($_GET['activated'])) : ?>
                        <div class="notice notice-success inline">
                            <p>Activation terminée. <a href="<?php echo esc_url($public_url); ?>" target="_blank">Ouvrir la page du jeu</a></p>
                        </div>
                    <?php endif; ?>
                </form>
                <div class="gamehub-links">
                    <a class="button" href="<?php echo esc_url($public_url); ?>" target="_blank">Ouvrir la page du jeu</a>
                    <button class="button" type="button" data-download-qr>Tout télécharger (QR)</button>
                </div>
            </section>

            <section class="gamehub-card">
                <h2>Lots gagnants</h2>
                <?php self::render_prize_table($prizes_win); ?>
                <button class="button" data-open-prize-modal data-type="win">Ajouter un lot</button>
            </section>

            <section class="gamehub-card">
                <h2>Lots de consolation</h2>
                <?php self::render_prize_table($prizes_consolation); ?>
                <button class="button" data-open-prize-modal data-type="consolation">Ajouter un lot</button>
            </section>

            <section class="gamehub-card">
                <h2>QR Code + Lien</h2>
                <p>URL publique: <code><?php echo esc_html($public_url); ?></code></p>
                <div class="gamehub-qr" data-qr-id="<?php echo esc_attr($qr_id); ?>" data-qr-url="<?php echo esc_url($public_url); ?>"></div>
                <div class="gamehub-links">
                    <button class="button" data-copy-link>Copier le lien</button>
                    <button class="button" data-download-qr>Télécharger le QR (PNG)</button>
                    <button class="button" data-print-qr>Imprimer (A4)</button>
                </div>
            </section>

            <section class="gamehub-card">
                <h2>Mini-stats</h2>
                <div class="gamehub-stats">
                    <div><strong><?php echo esc_html($stats['today']); ?></strong><span>Participations aujourd'hui</span></div>
                    <div><strong><?php echo esc_html($stats['total']); ?></strong><span>Participations total</span></div>
                    <div><strong><?php echo esc_html($stats['wins']); ?></strong><span>Gains total</span></div>
                    <div><strong><?php echo esc_html($stats['consolation']); ?></strong><span>Consolations total</span></div>
                    <div><strong><?php echo esc_html($stats['leads']); ?></strong><span>Leads collectés</span></div>
                    <div><strong><?php echo esc_html($stats['claimed']); ?></strong><span>Codes validés</span></div>
                </div>
            </section>

            <div class="gamehub-modal" id="gamehub-prize-modal" hidden>
                <div class="gamehub-modal__content">
                    <h3>Lot</h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                        <?php wp_nonce_field('gamehub_save_prize_simple'); ?>
                        <input type="hidden" name="action" value="gamehub_save_prize_simple">
                        <input type="hidden" name="prize_id" value="">
                        <label>Titre
                            <input type="text" name="title" required>
                        </label>
                        <label>Type
                            <select name="type">
                                <option value="win">Gagnant</option>
                                <option value="consolation">Consolation</option>
                            </select>
                        </label>
                        <label>Poids (1-100)
                            <input type="number" name="weight" min="1" max="100" value="10">
                        </label>
                        <label>Stock (win seulement)
                            <input type="number" name="stock" min="0">
                        </label>
                        <label>Expiration (jours)
                            <input type="number" name="expiry_days" min="0">
                        </label>
                        <div class="gamehub-actions">
                            <button class="button button-primary">Enregistrer</button>
                            <button class="button" type="button" data-close-modal>Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_validation(): void
    {
        $status = sanitize_text_field($_GET['status'] ?? '');
        $code = sanitize_text_field($_GET['code'] ?? '');
        $claim = $code ? DB::get_claim_by_code($code) : null;
        ?>
        <div class="wrap gamehub-validation">
            <h1>Validation caisse</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                <?php wp_nonce_field('gamehub_validate_check'); ?>
                <input type="hidden" name="action" value="gamehub_validate_check">
                <label>Code client
                    <input type="text" name="claim_code" value="<?php echo esc_attr($code); ?>" required>
                </label>
                <button class="button button-primary">Vérifier</button>
            </form>

            <?php if ($status) : ?>
                <div class="notice notice-info"><p><?php echo esc_html($status); ?></p></div>
            <?php endif; ?>

            <?php if ($claim) : ?>
                <div class="gamehub-card">
                    <h2>Résultat</h2>
                    <p><strong>Lot:</strong> <?php echo esc_html(DB::get_prize_title($claim->prize_id)); ?></p>
                    <p><strong>Statut:</strong> <?php echo esc_html($claim->status); ?></p>
                    <p><strong>Expiration:</strong> <?php echo esc_html($claim->expires_at); ?></p>
                    <p><strong>Créé le:</strong> <?php echo esc_html($claim->created_at); ?></p>
                    <?php if ($claim->status === 'new') : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('gamehub_validate_use'); ?>
                            <input type="hidden" name="action" value="gamehub_validate_use">
                            <input type="hidden" name="claim_code" value="<?php echo esc_attr($claim->claim_code); ?>">
                            <button class="button button-primary">Marquer comme utilisé</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_settings(): void
    {
        $settings = get_option('gamehub_settings', []);
        ?>
        <div class="wrap">
            <h1>Réglages</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                <?php wp_nonce_field('gamehub_save_settings'); ?>
                <input type="hidden" name="action" value="gamehub_save_settings">
                <label>Email de notification
                    <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? ''); ?>" class="regular-text">
                </label>
                <label>Mode test
                    <input type="checkbox" name="test_mode" value="1" <?php checked(!empty($settings['test_mode'])); ?>>
                </label>
                <button class="button button-primary">Enregistrer</button>
            </form>
        </div>
        <?php
    }

    public static function handle_activate_now(): void
    {
        check_admin_referer('gamehub_activate_now');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        Utils::update_option('notification_email', sanitize_email($_POST['notification_email'] ?? ''));
        Utils::update_option('active_game', sanitize_text_field($_POST['active_game'] ?? 'roulette'));
        Utils::update_option('play_limit_hours', (int) ($_POST['cooldown_hours'] ?? 24));
        Utils::update_option('test_mode', !empty($_POST['test_mode']));

        Utils::ensure_game_page();
        Utils::ensure_campaign();
        Utils::ensure_default_prizes();
        Utils::get_qr_id();

        wp_safe_redirect(admin_url('admin.php?page=gamehub-dashboard&activated=1'));
        exit;
    }

    public static function handle_save_settings(): void
    {
        check_admin_referer('gamehub_save_settings');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        Utils::update_option('notification_email', sanitize_email($_POST['notification_email'] ?? ''));
        Utils::update_option('test_mode', !empty($_POST['test_mode']));
        wp_safe_redirect(admin_url('admin.php?page=gamehub-settings&saved=1'));
        exit;
    }

    public static function handle_save_prize_simple(): void
    {
        check_admin_referer('gamehub_save_prize_simple');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $prize_id = (int) ($_POST['prize_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'win');
        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'type' => $type,
            'weight' => (float) ($_POST['weight'] ?? 10),
            'stock' => $type === 'win' ? (int) ($_POST['stock'] ?? 0) : null,
            'expiry_days' => (int) ($_POST['expiry_days'] ?? 0),
        ];
        DB::save_simple_prize($prize_id, $data);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-dashboard'));
        exit;
    }

    public static function handle_prize_toggle(): void
    {
        check_admin_referer('gamehub_prize_toggle');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $prize_id = (int) ($_POST['prize_id'] ?? 0);
        if ($prize_id) {
            DB::toggle_prize($prize_id);
        }
        wp_safe_redirect(admin_url('admin.php?page=gamehub-dashboard'));
        exit;
    }

    public static function handle_validate_check(): void
    {
        check_admin_referer('gamehub_validate_check');
        if (!current_user_can('gamehub_validate') && !current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $code = sanitize_text_field($_POST['claim_code'] ?? '');
        if (!$code) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Code%20manquant'));
            exit;
        }
        $claim = DB::get_claim_by_code($code);
        if (!$claim) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Code%20inconnu&code=' . urlencode($code)));
            exit;
        }
        if ($claim->status === 'used') {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Déjà%20utilisé&code=' . urlencode($code)));
            exit;
        }
        if (strtotime($claim->expires_at) < time()) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Expiré&code=' . urlencode($code)));
            exit;
        }
        wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Valide&code=' . urlencode($code)));
        exit;
    }

    public static function handle_validate_use(): void
    {
        check_admin_referer('gamehub_validate_use');
        if (!current_user_can('gamehub_validate') && !current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $code = sanitize_text_field($_POST['claim_code'] ?? '');
        if ($code) {
            DB::mark_claim_used($code, get_current_user_id());
        }
        wp_safe_redirect(admin_url('admin.php?page=gamehub-validation&status=Utilisé&code=' . urlencode($code)));
        exit;
    }

    private static function render_prize_table(array $prizes): void
    {
        if (!$prizes) {
            echo '<p>Aucun lot. Ajoutez-en un.</p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>Titre</th><th>Poids</th><th>Stock</th><th>Expiration</th><th></th></tr></thead><tbody>';
        foreach ($prizes as $prize) {
            echo '<tr>';
            echo '<td>' . esc_html($prize->title) . '</td>';
            echo '<td>' . esc_html($prize->weight) . '</td>';
            echo '<td>' . esc_html($prize->stock ?? '-') . '</td>';
            echo '<td>' . esc_html($prize->expiry_days ? $prize->expiry_days . ' j' : '-') . '</td>';
            echo '<td>';
            echo '<button class="button" data-open-prize-modal data-prize-id="' . esc_attr($prize->id) . '" data-prize-title="' . esc_attr($prize->title) . '" data-prize-type="' . esc_attr($prize->type) . '" data-prize-weight="' . esc_attr($prize->weight) . '" data-prize-stock="' . esc_attr($prize->stock) . '" data-prize-expiry="' . esc_attr($prize->expiry_days) . '">Modifier</button> ';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('gamehub_prize_toggle');
            echo '<input type="hidden" name="action" value="gamehub_prize_toggle">';
            echo '<input type="hidden" name="prize_id" value="' . esc_attr($prize->id) . '">';
            echo '<button class="button">' . ($prize->active ? 'Désactiver' : 'Activer') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
