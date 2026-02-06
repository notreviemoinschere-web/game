<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        if (is_network_admin()) {
            add_menu_page(
                'GameHub SaaS',
                'GameHub SaaS',
                'manage_network_options',
                'gamehub-saas',
                [self::class, 'render_super_admin_dashboard'],
                'dashicons-admin-multisite',
                58
            );
            return;
        }

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

    public static function render_super_admin_dashboard(): void
    {
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }

        $companies = DB::get_all_companies();
        $plans = DB::get_all_plans();
        $stats = DB::get_network_stats();
        ?>
        <div class="wrap gamehub-dashboard">
            <h1>GameHub SaaS — Super Admin</h1>

            <section class="gamehub-card">
                <h2>Vue globale</h2>
                <div class="gamehub-stats">
                    <div><strong><?php echo esc_html($stats['companies']); ?></strong><span>Entreprises</span></div>
                    <div><strong><?php echo esc_html($stats['active_subscriptions']); ?></strong><span>Abonnements actifs</span></div>
                    <div><strong><?php echo esc_html($stats['plays_total']); ?></strong><span>Participations réseau</span></div>
                </div>
            </section>

            <section class="gamehub-card">
                <h2>Créer une entreprise</h2>
                <form method="post" action="<?php echo esc_url(network_admin_url('admin-post.php')); ?>" class="gamehub-form">
                    <?php wp_nonce_field('gamehub_create_company'); ?>
                    <input type="hidden" name="action" value="gamehub_create_company">
                    <label>Nom entreprise
                        <input type="text" name="company_name" required>
                    </label>
                    <label>Email propriétaire
                        <input type="email" name="owner_email" required>
                    </label>
                    <label>Plan initial
                        <select name="plan_id">
                            <?php foreach ($plans as $plan) : ?>
                                <option value="<?php echo esc_attr($plan->id); ?>"><?php echo esc_html($plan->name . ' (' . $plan->price_monthly . '€/mois)'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button button-primary">Créer l'entreprise</button>
                </form>
            </section>

            <section class="gamehub-card">
                <h2>Plans</h2>
                <form method="post" action="<?php echo esc_url(network_admin_url('admin-post.php')); ?>" class="gamehub-form">
                    <?php wp_nonce_field('gamehub_save_plan'); ?>
                    <input type="hidden" name="action" value="gamehub_save_plan">
                    <label>Code plan
                        <input type="text" name="code" placeholder="starter" required>
                    </label>
                    <label>Nom
                        <input type="text" name="name" required>
                    </label>
                    <label>Prix mensuel (€)
                        <input type="number" step="0.01" min="0" name="price_monthly" required>
                    </label>
                    <label>Limites JSON
                        <textarea name="limits_json" rows="4" placeholder='{"plays_per_month":1000,"games":2}'></textarea>
                    </label>
                    <button class="button">Ajouter plan</button>
                </form>
                <ul>
                    <?php foreach ($plans as $plan) : ?>
                        <li><strong><?php echo esc_html($plan->name); ?></strong> — <?php echo esc_html($plan->price_monthly); ?>€/mois (<?php echo esc_html($plan->code); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="gamehub-card">
                <h2>Entreprises</h2>
                <table class="widefat striped">
                    <thead><tr><th>#</th><th>Entreprise</th><th>Plan</th><th>Statut</th><th>Espace</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($companies as $company) : ?>
                        <tr>
                            <td><?php echo esc_html($company->id); ?></td>
                            <td><?php echo esc_html($company->name); ?></td>
                            <td><?php echo esc_html($company->plan_name ?: '-'); ?></td>
                            <td><?php echo esc_html($company->status); ?></td>
                            <td>
                                <?php if (!empty($company->site_id) && is_multisite()) : ?>
                                    <a href="<?php echo esc_url(get_admin_url((int) $company->site_id)); ?>" target="_blank">Ouvrir</a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?php echo esc_url(network_admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:6px;">
                                    <?php wp_nonce_field('gamehub_assign_plan'); ?>
                                    <input type="hidden" name="action" value="gamehub_assign_plan">
                                    <input type="hidden" name="company_id" value="<?php echo esc_attr($company->id); ?>">
                                    <select name="plan_id">
                                        <?php foreach ($plans as $plan) : ?>
                                            <option value="<?php echo esc_attr($plan->id); ?>" <?php selected($company->plan_code, $plan->code); ?>><?php echo esc_html($plan->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button">Changer</button>
                                </form>
                                <form method="post" action="<?php echo esc_url(network_admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                    <?php wp_nonce_field('gamehub_delete_company'); ?>
                                    <input type="hidden" name="action" value="gamehub_delete_company">
                                    <input type="hidden" name="company_id" value="<?php echo esc_attr($company->id); ?>">
                                    <button class="button button-link-delete" onclick="return confirm('Supprimer cette entreprise ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
        <?php
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
        $company = DB::get_company_by_site(get_current_blog_id());
        $subscription = $company ? DB::get_company_subscription((int) $company->id) : null;
        $plans = DB::get_all_plans();
        ?>
        <div class="wrap gamehub-dashboard">
            <h1>GameHub — Dashboard Entreprise</h1>

            <?php if ($subscription) : ?>
                <section class="gamehub-card">
                    <h2>Mon abonnement</h2>
                    <p><strong>Plan actuel :</strong> <?php echo esc_html($subscription->plan_name); ?> (<?php echo esc_html($subscription->price_monthly); ?>€/mois)</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                        <?php wp_nonce_field('gamehub_upgrade_plan'); ?>
                        <input type="hidden" name="action" value="gamehub_upgrade_plan">
                        <label>Changer de plan
                            <select name="plan_id">
                                <?php foreach ($plans as $plan) : ?>
                                    <option value="<?php echo esc_attr($plan->id); ?>" <?php selected($subscription->plan_id, $plan->id); ?>><?php echo esc_html($plan->name . ' - ' . $plan->price_monthly . '€/mois'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button">Mettre à niveau</button>
                    </form>
                </section>
            <?php endif; ?>

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
                    <a class="button" href="<?php echo esc_url(add_query_arg(['test' => 1, 'sig' => Utils::get_test_signature(Utils::get_public_token())], $public_url)); ?>" target="_blank">Tester le jeu</a>
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

    public static function handle_create_company(): void
    {
        check_admin_referer('gamehub_create_company');
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }
        $name = sanitize_text_field($_POST['company_name'] ?? '');
        $email = sanitize_email($_POST['owner_email'] ?? '');
        $plan_id = (int) ($_POST['plan_id'] ?? 0);
        if ($name && $email) {
            DB::create_company($name, $email, $plan_id);
        }
        wp_safe_redirect(network_admin_url('admin.php?page=gamehub-saas&created=1'));
        exit;
    }

    public static function handle_delete_company(): void
    {
        check_admin_referer('gamehub_delete_company');
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }
        $company_id = (int) ($_POST['company_id'] ?? 0);
        if ($company_id) {
            DB::delete_company($company_id);
        }
        wp_safe_redirect(network_admin_url('admin.php?page=gamehub-saas&deleted=1'));
        exit;
    }

    public static function handle_assign_plan(): void
    {
        check_admin_referer('gamehub_assign_plan');
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }
        $company_id = (int) ($_POST['company_id'] ?? 0);
        $plan_id = (int) ($_POST['plan_id'] ?? 0);
        if ($company_id && $plan_id) {
            DB::assign_plan($company_id, $plan_id);
        }
        wp_safe_redirect(network_admin_url('admin.php?page=gamehub-saas&plan=1'));
        exit;
    }

    public static function handle_save_plan(): void
    {
        check_admin_referer('gamehub_save_plan');
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $code = sanitize_key($_POST['code'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $price = (float) ($_POST['price_monthly'] ?? 0);
        $limits = wp_unslash($_POST['limits_json'] ?? '');
        if ($code && $name) {
            $decoded = json_decode($limits, true);
            $wpdb->insert(DB::saas_table('plans'), [
                'code' => $code,
                'name' => $name,
                'price_monthly' => $price,
                'limits_json' => is_array($decoded) ? wp_json_encode($decoded) : wp_json_encode([]),
                'active' => 1,
                'created_at' => Utils::now_mysql(),
            ]);
        }
        wp_safe_redirect(network_admin_url('admin.php?page=gamehub-saas&plan_created=1'));
        exit;
    }

    public static function handle_upgrade_plan(): void
    {
        check_admin_referer('gamehub_upgrade_plan');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $plan_id = (int) ($_POST['plan_id'] ?? 0);
        $company = DB::get_company_by_site(get_current_blog_id());
        if ($company && $plan_id) {
            DB::assign_plan((int) $company->id, $plan_id);
        }
        wp_safe_redirect(admin_url('admin.php?page=gamehub-dashboard&upgraded=1'));
        exit;
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
        $saved_id = DB::save_simple_prize($prize_id, $data);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $prize = DB::get_prize_by_id($saved_id);
            wp_send_json_success([
                'prize' => $prize,
                'row' => self::render_prize_row($prize),
            ]);
        }
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
            echo self::render_prize_row($prize);
        }
        echo '</tbody></table>';
    }

    private static function render_prize_row(object $prize): string
    {
        ob_start();
        ?>
        <tr data-prize-row="<?php echo esc_attr($prize->id); ?>">
            <td><?php echo esc_html($prize->title); ?></td>
            <td><?php echo esc_html($prize->weight); ?></td>
            <td><?php echo esc_html($prize->stock ?? '-'); ?></td>
            <td><?php echo esc_html($prize->expiry_days ? $prize->expiry_days . ' j' : '-'); ?></td>
            <td>
                <button class="button" data-open-prize-modal data-prize-id="<?php echo esc_attr($prize->id); ?>" data-prize-title="<?php echo esc_attr($prize->title); ?>" data-prize-type="<?php echo esc_attr($prize->type); ?>" data-prize-weight="<?php echo esc_attr($prize->weight); ?>" data-prize-stock="<?php echo esc_attr($prize->stock); ?>" data-prize-expiry="<?php echo esc_attr($prize->expiry_days); ?>">Modifier</button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('gamehub_prize_toggle'); ?>
                    <input type="hidden" name="action" value="gamehub_prize_toggle">
                    <input type="hidden" name="prize_id" value="<?php echo esc_attr($prize->id); ?>">
                    <button class="button"><?php echo esc_html($prize->active ? 'Désactiver' : 'Activer'); ?></button>
                </form>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
}
