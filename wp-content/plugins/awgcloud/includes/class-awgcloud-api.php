<?php
class AwgCloud_API {
    public static function send($phone, $message) {
        $api_url = get_option('awg_cloud_apiurl');
        $token   = get_option('awg_cloud_token');

        if (!$api_url || !$token || !$phone) return;

        wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'receiver'  => $phone,
                'token'     => $token,
                'options'   => ['delay' => 2500, 'presence' => 'composing'],
                'msgtype'   => 'textMessage',
                'content'   => ['text' => $message],
            ]),
        ]);
    }

    public static function notify_admins($message) {
        $admin_phones = get_option('awg_cloud_admin_phones');
        if ($admin_phones) {
            $admins = array_map('trim', explode(',', $admin_phones));
            foreach ($admins as $admin_phone) {
                self::send($admin_phone, "Admin Notification: {$message}");
                sleep(2);
            }
        }
    }

    private static function parse_woocommerce_template($template, $order) {
        $billing = $order->get_address('billing');
        $shipping = $order->get_address('shipping');

        $replacements = [
            '{first_name}'        => sanitize_text_field($billing['first_name']),
            '{last_name}'         => sanitize_text_field($billing['last_name']),
            '{order_id}'          => $order->get_id(),
            '{order_number}'      => $order->get_order_number(),
            '{order_status}'      => ucfirst($order->get_status()),
            '{order_date}'        => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i') : '',
            '{order_total}'       => wc_price($order->get_total()),
            '{payment_method}'    => $order->get_payment_method_title(),
            '{shipping_method}'   => $order->get_shipping_method(),
            '{billing_phone}'     => sanitize_text_field($billing['phone']),
            '{billing_email}'     => sanitize_email($billing['email']),
            '{billing_address}'   => wc_format_address($billing),
            '{shipping_address}'  => wc_format_address($shipping),
            '{site_name}'         => get_bloginfo('name'),
            '{site_url}'          => home_url(),
        ];

        return strtr($template, $replacements);

    // Hi {first_name}, your order #{order_number} of {order_total} is now {order_status}. We'll ship via {shipping_method}. Thanks for shopping at {site_name}!
    }

    public static function send_woocommerce_event_message($order_id, $event_key) {
        if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $order = wc_get_order($order_id);
        $phone = $order->get_billing_phone();
        $message = get_option("awg_cloud_template_{$event_key}_message", "Your order update: {$event_key}");
        $message = self::parse_woocommerce_template($message, $order);

        self::notify_admins("Event '{$event_key}' triggered for Order #{$order->get_order_number()}");

        self::send($phone, $message);
    }

    private static function parse_easydigitaldownloads_template($template, $payment) {
        $customer = new EDD_Customer($payment->customer_id);
        $replacements = [
            '{first_name}'      => sanitize_text_field($customer->first_name),
            '{last_name}'       => sanitize_text_field($customer->last_name),
            '{payment_id}'      => $payment->ID,
            '{payment_number}'  => $payment->number,
            '{payment_status}'  => ucfirst($payment->status),
            '{payment_date}'    => $payment->date,
            '{payment_total}'   => edd_currency_filter(edd_format_amount($payment->total)),
            '{billing_phone}'   => sanitize_text_field(edd_get_payment_meta_phone($payment->ID)),
            '{billing_email}'   => sanitize_email($customer->email),
            '{site_name}'       => get_bloginfo('name'),
            '{site_url}'        => home_url(),
        ];

        return strtr($template, $replacements);
    }

    public static function send_easydigitaldownloads_event_message($payment_id, $event_key) {
        // if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $payment = edd_get_payment($payment_id);
        if (!$payment) return;

        $phone = edd_get_payment_meta_phone($payment_id);
        $message = get_option("awg_cloud_template_{$event_key}_message", "Your payment update: {$event_key}");
        $message = self::parse_easydigitaldownloads_template($message, $payment);

        self::notify_admins("Event '{$event_key}' triggered for Payment #{$payment->number}");

        self::send($phone, $message);
    }

    private static function parse_wpforms_template($template, $fields) {
        $replacements = [];
        foreach ($fields as $field) {
            $tag = '{' . strtolower(str_replace(' ', '_', $field['name'])) . '}';
            $replacements[$tag] = sanitize_text_field($field['value']);
        }

        return strtr($template, $replacements);
    }

    public static function send_wpforms_event_message($fields, $entry, $form_data, $entry_id, $event_key) {
        // if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $phone = '';
        foreach ($fields as $field) {
            if (strpos(strtolower($field['name']), 'phone') !== false) {
                $phone = sanitize_text_field($field['value']);
                break;
            }
        }

        $message = get_option("awg_cloud_template_{$event_key}_message", "Your form submission update: {$event_key}");
        $message = self::parse_wpforms_template($message, $fields);

        self::notify_admins("Event '{$event_key}' triggered for WPForms Entry #{$entry_id}");

        self::send($phone, $message);
    }

    public static function send_learndash_event_message($data, $user_id, $event_key) {
        // if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $user = get_userdata($user_id);
        if (!$user) return;

        $phone = get_user_meta($user_id, 'phone', true);
        $message = get_option("awg_cloud_template_{$event_key}_message", "Your LearnDash update: {$event_key}");
        $message = strtr($message, [
            '{course_id}'       => isset($data['course_id']) ? intval($data['course_id']) : '',
            '{course_title}'    => isset($data['course_title']) ? sanitize_text_field($data['course_title']) : '',
            '{user_id}'         => $user_id,
            '{completion_date}' => isset($data['completion_date']) ? sanitize_text_field($data['completion_date']) : '',
            '{site_name}'       => get_bloginfo('name'),
            '{site_url}'        => home_url(),
        ]);

        self::notify_admins("Event '{$event_key}' triggered for LearnDash User #{$user_id}");

        self::send($phone, $message);
    }

    public static function send_memberpress_event_message($subscription_id, $event_key) {
        // if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $subscription = new MeprSubscription($subscription_id);
        if (!$subscription) return;

        $user_id = $subscription->user_id;
        $user = get_userdata($user_id);
        if (!$user) return;

        $phone = get_user_meta($user_id, 'phone', true);
        $message = get_option("awg_cloud_template_{$event_key}_message", "Your MemberPress update: {$event_key}");
        $message = strtr($message, [
            '{subscription_id}' => $subscription_id,
            '{user_id}'         => $user_id,
            '{site_name}'       => get_bloginfo('name'),
            '{site_url}'        => home_url(),
        ]);

        self::notify_admins("Event '{$event_key}' triggered for MemberPress Subscription #{$subscription_id}");

        self::send($phone, $message);
    }

    public static function send_gravityforms_event_message($entry, $form, $event_key) {
        // if (!get_option("awg_cloud_template_{$event_key}_enabled")) return;

        $phone = rgar($entry, 'phone');
        $message = get_option("awg_cloud_template_{$event_key}_message", "Your Gravity Forms submission update: {$event_key}");
        $message = strtr($message, [
            '{entry_id}'    => rgar($entry, 'id'),
            '{form_id}'     => rgar($form, 'id'),
            '{site_name}'   => get_bloginfo('name'),
            '{site_url}'    => home_url(),
        ]);

        self::notify_admins("Event '{$event_key}' triggered for Gravity Forms Entry #".rgar($entry, 'id'));

        self::send($phone, $message);
    }
}
