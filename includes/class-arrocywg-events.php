<?php
class ArrocyWG_Events {
    public function __construct() {
        if (class_exists('WooCommerce')) {
            $event_hooks = [
                'woocommerce_new_order'               => 'woocommerce_order_created',
                'woocommerce_order_status_completed'  => 'woocommerce_order_completed',
                'woocommerce_order_status_cancelled'  => 'woocommerce_order_cancelled',
                'woocommerce_order_status_refunded'   => 'woocommerce_order_refunded',
                'woocommerce_subscription_status_updated' => 'woocommerce_subscription_updated',
            ];

            $this->process_woocommerce($event_hooks);
        }

        if (class_exists('Easy_Digital_Downloads')) {
            $event_hooks = [
                'edd_complete_purchase' => 'edd_purchase_completed',
            ];
            $this->process_easydigitaldownloads($event_hooks);
        }

        if (class_exists('WPForms')) {
            $event_hooks = [
                'wpforms_process_complete' => 'wpforms_submitted',
            ];
            $this->process_wpforms($event_hooks);
        }

        if (class_exists('LearnDash')) {
            $event_hooks = [
                'learndash_course_completed' => 'learndash_course_completed',
            ];
            $this->process_learndash($event_hooks);
        }

        if (class_exists('MemberPress')) {
            $event_hooks = [
                'mepr-event-subscription-created' => 'memberpress_subscription_created',
            ];
            $this->process_memberpress($event_hooks);
        }

        if (class_exists('GFForms')) {
            $event_hooks = [
                'gform_after_submission' => 'gravityforms_submitted',
            ];
            $this->process_gravityforms($event_hooks);
        }
    }

    public function process_woocommerce($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            if ($hook === 'woocommerce_subscription_status_updated') {
                add_action($hook, function($subscription, $new_status, $old_status) {
                    // $order_id = $subscription->get_last_order(); // or use $subscription->get_id() if you want the subscription ID
                    $subscription_id = $subscription->get_id();
                    $phone = $subscription->get_billing_phone();
                    $message = "Subscription #{$subscription_id} status changed from {$old_status} to {$new_status}";
                    ArrocyWG_API::notify_admins($message);
                    ArrocyWG_API::send($phone, $message);
                }, 10, 3);
            } else {
                add_action($hook, function($order_id) use ($event_key) {
                    ArrocyWG_API::send_woocommerce_event_message($order_id, $event_key);
                }, 10, 1);
            }
        }
    }

    public function process_easydigitaldownloads($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            add_action($hook, function($payment_id) use ($event_key) {
                ArrocyWG_API::send_easydigitaldownloads_event_message($payment_id, $event_key);
            }, 10, 1);
        }
    }

    public function process_wpforms($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            add_action($hook, function($fields, $entry, $form_data, $entry_id) use ($event_key) {
                ArrocyWG_API::send_wpforms_event_message($fields, $entry, $form_data, $entry_id, $event_key);
            }, 10, 4);
        }
    }

    public function process_learndash($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            add_action($hook, function($data, $user_id) use ($event_key) {
                ArrocyWG_API::send_learndash_event_message($data, $user_id, $event_key);
            }, 10, 2);
        }
    }

    public function process_memberpress($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            add_action($hook, function($subscription) use ($event_key) {
                ArrocyWG_API::send_memberpress_event_message($subscription->ID, $event_key);
            }, 10, 1);
        }
    }

    public function process_gravityforms($event_hooks) {
        foreach ($event_hooks as $hook => $event_key) {
            add_action($hook, function($entry, $form) use ($event_key) {
                ArrocyWG_API::send_gravityforms_event_message($entry['id'], $event_key);
            }, 10, 2);
        }
    }
}
