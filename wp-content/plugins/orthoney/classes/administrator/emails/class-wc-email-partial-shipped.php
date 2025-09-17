<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Email_Partial_Shipped extends WC_Email {

    public function __construct() {
        $this->id             = 'partial_shipped';
        $this->title          = 'Partial Shipped';
        $this->description    = 'This email is sent when an order is marked Partial Shipped.';
        $this->heading        = 'Your Order #{order_number} is Partially Shipped';
        $this->subject        = 'Order #{order_number} is Partially Shipped';

        $this->template_html  = 'emails/partial-shipped.php';
        $this->template_plain = 'emails/plain/partial-shipped.php';

        $this->customer_email = true;

        // ✅ correct hook name (no "wc-" prefix)
        add_action( 'woocommerce_order_status_partial-shipped_notification', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();
    }

    public function trigger( $order_id, $order = false ) {
        if ( $order_id ) {
            $this->object     = wc_get_order( $order_id );
            $this->recipient  = $this->object->get_billing_email();

            // Placeholders for subject/heading
            $this->placeholders = array(
                '{order_number}' => OAM_COMMON_Custom::get_order_meta($this->object->get_order_number(), '_orthoney_OrderID'),
                '{order_date}'   => wc_format_datetime( $this->object->get_date_created() ),
                '{site_title}'   => $this->get_blogname(),
            );
        }

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ) );
    }

    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ) );
    }
}
