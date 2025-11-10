<?php
class ArrocyWG_Admin {
    private $settings_group = 'arrocy_wg_settings';
    private $template_group = 'arrocy_wg_templates';

    private $woocommerce_events = [
        'woocommerce_order_created'   => 'WooCommerce Order Created',
        'woocommerce_order_completed' => 'WooCommerce Order Completed',
        'woocommerce_order_cancelled' => 'WooCommerce Order Cancelled',
        'woocommerce_order_refunded'  => 'WooCommerce Order Refunded',
    ];

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'register_templates']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_notices', [$this, 'show_settings_success']);
    }

    public function add_admin_menu() {
        add_menu_page('Arrocy WG', 'Arrocy WG', 'manage_options', 'arrocy-wg', [$this, 'render_settings_page'], 'dashicons-superhero', 60);
        add_submenu_page('arrocy-wg', 'Settings', 'Settings', 'manage_options', 'arrocy-wg', [$this, 'render_settings_page']);
        add_submenu_page('arrocy-wg', 'Templates', 'Templates', 'manage_options', 'arrocy-wg-templates', [$this, 'render_templates_page']);
    }

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>Arrocy WG Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->settings_group);
        do_settings_sections('arrocy-wg');
        submit_button();
        echo '</form></div>';
    }

    public function render_templates_page() {
        echo '<div class="wrap">';
        echo '<h1>Message Templates</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->template_group);
        do_settings_sections('arrocy-wg-templates');
        submit_button();
        echo '</form></div>';
    }

    public function register_settings() {
        $fields = [
            ['arrocy_wg_enabled', 'Enable AWG', 'checkbox'],
            ['arrocy_wg_url', 'AWG URL', 'text'],
            ['arrocy_wg_token', 'AWG TOKEN', 'password'],
        ];

        foreach ($fields as [$name, $label, $type]) {
            register_setting($this->settings_group, $name);
            add_settings_field($name, $label, [$this, "render_{$type}_field"], 'arrocy-wg', 'arrocy_wg_main_section', ['label_for' => $name]);
        }

        add_settings_section(
            'arrocy_wg_main_section',
            'AWG Configuration',
            fn() => print '<p>Configure your Arrocy WG integration below.</p>',
            'arrocy-wg'
        );
    }

    public function register_templates() {
        $admin_phones = 'arrocy_wg_admin_phones';
        register_setting($this->template_group, $admin_phones);
        add_settings_field($admin_phones, 'Admin Phone Numbers', [$this, 'render_text_field'], 'arrocy-wg-templates', 'arrocy_wg_templates_section', ['label_for' => $admin_phones]);

        foreach ($this->woocommerce_events as $event_key => $label) {
            $enabled = "arrocy_wg_template_{$event_key}_enabled";
            $message = "arrocy_wg_template_{$event_key}_message";

            register_setting($this->template_group, $enabled);
            register_setting($this->template_group, $message);

            add_settings_field($enabled, "{$label} Enabled", [$this, 'render_checkbox_field'], 'arrocy-wg-templates', 'arrocy_wg_templates_section', ['label_for' => $enabled]);
            add_settings_field($message, "{$label} Template", [$this, 'render_textarea_field'], 'arrocy-wg-templates', 'arrocy_wg_templates_section', ['label_for' => $message]);
        }

        add_settings_section(
            'arrocy_wg_templates_section',
            'WooCommerce Message Templates',
            fn() => print '<p>Enable events and customize WhatsApp messages sent when they occur.</p><p>Tags: {first_name}, {last_name}, {order_id}, {order_number}, {order_status}, {order_date}, {order_total}, {payment_method}, {shipping_method}, {billing_phone}, {billing_email}, {billing_address}, {shipping_address}, {site_name}, {site_url}</p><p>Insert Admin Phone Numbers separated by commas to notify Admins</p>',
            'arrocy-wg-templates'
        );
    }

    public function show_settings_success() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            add_settings_error('arrocy_wg_messages', 'settings_saved', 'Settings saved successfully.', 'updated');
        }

        settings_errors('arrocy_wg_messages');
    }

    public function render_text_field($args) {
        $value = esc_attr(get_option($args['label_for'], ''));
        echo "<input type='text' id='{$args['label_for']}' name='{$args['label_for']}' value='{$value}' class='large-text' />";
    }

    public function render_textarea_field($args) {
        $value = esc_textarea(get_option($args['label_for'], ''));
        echo "<textarea id='{$args['label_for']}' name='{$args['label_for']}' rows='4' cols='50' class='large-text'>{$value}</textarea>";
    }

    public function render_checkbox_field($args) {
        $checked = get_option($args['label_for'], false) ? 'checked' : '';
        echo "<input type='checkbox' id='{$args['label_for']}' name='{$args['label_for']}' value='1' {$checked} />";
    }

    public function render_password_field($args) {
        $id = esc_attr($args['label_for']);
        $value = esc_attr(get_option($id, ''));

        echo "<input type='password' id='{$id}' name='{$id}' value='{$value}' class='regular-text' />";
        echo "<button type='button' class='button' onclick=\"togglePasswordVisibility('{$id}', this)\">Show</button>";
        echo "
        <script>
            function togglePasswordVisibility(fieldId, button) {
                var input = document.getElementById(fieldId);
                if (input.type === 'password') {
                    input.type = 'text';
                    button.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    button.textContent = 'Show';
                }
            }
        </script>";
    }
}
