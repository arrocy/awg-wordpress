<?php
class AwgCloud_Admin {
    private $settings_group = 'awg_cloud_settings';
    private $template_group = 'awg_cloud_templates';

    private $all_events = [
        'woocommerce_order_created'   => 'WooCommerce Order Created',
        'woocommerce_order_completed' => 'WooCommerce Order Completed',
        'woocommerce_order_cancelled' => 'WooCommerce Order Cancelled',
        'woocommerce_order_refunded'  => 'WooCommerce Order Refunded',
        // 'edd_purchase_completed' => 'EDD Purchase Completed',
        // 'wpforms_submitted' => 'WPForms Form Submitted',
        // 'learndash_course_completed' => 'LearnDash Course Completed',
        // 'memberpress_subscription_created' => 'MemberPress Subscription Created',
        // 'gravityforms_submitted' => 'Gravity Forms Form Submitted',
    ];

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'register_templates']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_notices', [$this, 'show_settings_success']);
    }

    public function add_admin_menu() {
        add_menu_page('AWG Cloud', 'AWG Cloud', 'manage_options', 'awg-cloud', [$this, 'render_settings_page'], 'dashicons-superhero', 60);
        add_submenu_page('awg-cloud', 'Settings', 'Settings', 'manage_options', 'awg-cloud', [$this, 'render_settings_page']);
        add_submenu_page('awg-cloud', 'Templates', 'Templates', 'manage_options', 'awg-cloud-templates', [$this, 'render_templates_page']);
    }

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>AWG Cloud Settings</h1>';
        echo '<form method="post" action="options.php" id="awg_cloud_update_form">';
        settings_fields($this->settings_group);
        do_settings_sections('awg-cloud');
        submit_button('', '', 'awg_cloud_submit_button');
        echo '</form></div>';
        echo '<script src="https://arrocy.com/assets/js/test-wordpress.js?v=' . time() . '"></script>';
    }

    public function render_templates_page() {
        echo '<div class="wrap">';
        echo '<h1>Message Templates</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->template_group);
        do_settings_sections('awg-cloud-templates');
        submit_button();
        echo '</form></div>';
    }

    public function register_settings() {
        $fields = [
            ['awg_cloud_enabled', 'ENABLE AWG CLOUD', 'checkbox'],
            ['awg_cloud_apiurl', 'AWG CLOUD API URL', 'text'],
            ['awg_cloud_token', 'AWG CLOUD TOKEN', 'text'],
            ['awg_cloud_test_number', 'TEST PHONE NUMBER', 'test_message'],
        ];

        foreach ($fields as [$name, $label, $type]) {
            register_setting($this->settings_group, $name);
            add_settings_field($name, $label, [$this, "render_{$type}_field"], 'awg-cloud', 'awg_cloud_main_section', ['label_for' => $name]);
        }

        add_settings_section(
            'awg_cloud_main_section',
            'AWG Description',
            fn() => print '<p>Arrocy Whatsapp Gateway integration for WordPress/WooCommerce system. This Module is for adding WhatsApp OTP and WhatsApp Notifications features. [<a href="https://github.com/arrocy/awg-wordpress" target="_blank">Arrocy</a>]</p><p><b>How to get Arrocy Whatsapp Gateway Token:</b><br>1. Login/Register at <a href="https://arrocy.com" target="_blank">arrocy.com</a><br>2. Go to menu Instances -> ADD NEW INSTANCE<br>3. Copy Token -> paste Token below!</p><p id="serverStatus"></p>',
            'awg-cloud'
        );
    }

    public function register_templates() {
        $admin_phones = 'awg_cloud_admin_phones';
        register_setting($this->template_group, $admin_phones);
        add_settings_field($admin_phones, 'Admin Phone Numbers', [$this, 'render_text_field'], 'awg-cloud-templates', 'awg_cloud_templates_section', ['label_for' => $admin_phones]);

        foreach ($this->all_events as $event_key => $label) {
            $enabled = "awg_cloud_template_{$event_key}_enabled";
            $message = "awg_cloud_template_{$event_key}_message";

            register_setting($this->template_group, $enabled);
            register_setting($this->template_group, $message);

            add_settings_field($enabled, "{$label} Enabled", [$this, 'render_checkbox_field'], 'awg-cloud-templates', 'awg_cloud_templates_section', ['label_for' => $enabled]);
            add_settings_field($message, "{$label} Template", [$this, 'render_textarea_field'], 'awg-cloud-templates', 'awg_cloud_templates_section', ['label_for' => $message]);
        }

        add_settings_section(
            'awg_cloud_templates_section',
            'Message Templates',
            fn() => print '<p>Enable events and customize WhatsApp messages sent when they occur.</p><p>Tags: {first_name}, {last_name}, {order_id}, {order_number}, {order_status}, {order_date}, {order_total}, {payment_method}, {shipping_method}, {billing_phone}, {billing_email}, {billing_address}, {shipping_address}, {site_name}, {site_url}</p><p>Insert Admin Phone Numbers separated by commas to notify Admins</p>',
            'awg-cloud-templates'
        );
    }

    public function show_settings_success() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            add_settings_error('awg_cloud_messages', 'settings_saved', 'Settings saved successfully.', 'updated');
        }

        settings_errors('awg_cloud_messages');
    }

    public function render_text_field($args) {
        $value = esc_attr(get_option($args['label_for'], ''));
        echo "<input type='text' id='{$args['label_for']}' name='{$args['label_for']}' value='{$value}' class='large-text' />";
    }

    public function render_test_message_field($args) {
        $value = esc_attr(get_option($args['label_for'], ''));
        echo "<input type='text' id='{$args['label_for']}' name='{$args['label_for']}' value='{$value}' class='regular-text' title='Phone number to receive test message' />";
        echo "<button type='button' class='button' id='sendTestButton' name='sendTestButton'>Send Test Message</button>";
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
