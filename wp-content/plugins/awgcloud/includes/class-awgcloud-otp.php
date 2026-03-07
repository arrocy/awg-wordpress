<?php
class AwgCloud_OTP {
    public function __construct() {
        add_action('woocommerce_register_form', [$this, 'add_otp_fields']);
        add_action('woocommerce_register_post', [$this, 'validate_otp'], 10, 3);
        add_action('woocommerce_created_customer', [$this, 'clear_otp']);
        add_action('woocommerce_login_form', [$this, 'add_login_otp_fields']);
        // add_action('wp_authenticate_user', [$this, 'validate_login_otp'], 10, 2);
        // add_action('lostpassword_form', [$this, 'add_reset_otp_fields']);
        // add_filter('allow_password_reset', [$this, 'validate_reset_otp'], 10, 2);
        add_action('wp_ajax_awgcloud_send_otp', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_nopriv_awgcloud_send_otp', [$this, 'ajax_send_otp']);
    }

    public function add_otp_fields() {
        ?>
        <p class="form-row form-row-wide">
            <label for="billing_phone">Phone Number <span class="required">*</span></label>
            <input type="tel" name="billing_phone" id="billing_phone" value="<?php echo esc_attr($_POST['billing_phone'] ?? ''); ?>" required />
            <button type="button" id="send-otp" class="button">Send OTP</button>
        </p>
        <p class="form-row form-row-wide">
            <label for="otp_code">Enter OTP <span class="required">*</span></label>
            <input type="text" name="otp_code" id="otp_code" value="<?php echo esc_attr($_POST['otp_code'] ?? ''); ?>" required />
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#send-otp').on('click', function() {
                var phone = $('#billing_phone').val();
                if (!phone) return alert('Enter phone number first');
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'awgcloud_send_otp',
                    phone: phone
                }, function(response) {
                    alert(response.success ? 'OTP sent!' : 'Failed to send OTP');
                });
            });
        });
        </script>
        <?php
    }

    public function add_login_otp_fields() {
        ?>
        <p class="form-row form-row-wide">
            <label for="awgcloud_login_otp">OTP <span class="required">*</span></label>
            <input type="text" name="awgcloud_login_otp" id="awgcloud_login_otp" required />
        </p>
        <?php
    }

    public function add_reset_otp_fields() {
        ?>
        <p>
            <label for="awgcloud_reset_otp">OTP <span class="required">*</span></label>
            <input type="text" name="awgcloud_reset_otp" id="awgcloud_reset_otp" required />
        </p>
        <?php
    }

    public function ajax_send_otp() {
        if (get_transient("awgcloud_otp_sent_{$phone}")) {
            wp_send_json_error('Please wait before requesting another OTP.');
        }
        set_transient("awgcloud_otp_sent_{$phone}", true, 60); // 1-minute cooldown

        $phone = sanitize_text_field($_POST['phone'] ?? '');
        if (!$phone) wp_send_json_error('Missing phone');

        $otp = rand(100000, 999999);
        set_transient("awgcloud_otp_{$phone}", $otp, 10 * MINUTE_IN_SECONDS);

        AwgCloud_API::send($phone, "Your OTP is: {$otp}");
        wp_send_json_success();
    }

    public function validate_otp($username, $email, $errors) {
        $phone = sanitize_text_field($_POST['billing_phone'] ?? '');
        $otp   = sanitize_text_field($_POST['otp_code'] ?? '');
        $stored = get_transient("awgcloud_otp_{$phone}");

        if (!$stored) {
            $errors->add('otp_error', 'OTP expired. Please request a new one.');
        } elseif ($stored !== $otp) {
            $errors->add('otp_error', 'Incorrect OTP.');
        }
    }

    public function validate_login_otp($user, $password) {
        $phone = get_user_meta($user->ID, 'billing_phone', true);
        if (!$phone) return new WP_Error('otp_error', 'No phone number found for this account.');
        $otp = $_POST['awgcloud_login_otp'] ?? '';
        $stored = get_transient("awgcloud_otp_{$phone}");

        if (!$stored) {
            return new WP_Error('otp_error', 'OTP expired. Please request a new one.');
        } elseif ($stored !== $otp) {
            return new WP_Error('otp_error', 'Incorrect OTP.');
        }

        delete_transient("awgcloud_otp_{$phone}");
        return $user;
    }

    public function validate_reset_otp($allow, $user_id) {
        $phone = get_user_meta($user_id, 'billing_phone', true);
        if (!$phone) return new WP_Error('otp_error', 'No phone number found for this account.');
        $otp = $_POST['awgcloud_reset_otp'] ?? '';
        $stored = get_transient("awgcloud_otp_{$phone}");

        if (!$stored || $stored !== $otp) {
            return false;
        }

        delete_transient("awgcloud_otp_{$phone}");
        return true;
    }

    public function clear_otp($customer_id) {
        $phone = sanitize_text_field($_POST['billing_phone'] ?? '');
        delete_transient("awgcloud_otp_{$phone}");
    }
}
