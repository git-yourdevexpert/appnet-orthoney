<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

class OAM_ADMINISTRATOR_CUSTOM_ORDER_STATUS_MAIL {

    public function __construct() {
        add_filter( 'woocommerce_email_classes', array( $this, 'orthoney_register_custom_order_emails' ) );
        add_action( 'woocommerce_order_status_partial-shipped', array( $this, 'orthoney_send_partial_shipped_email' ) );
        add_action( 'woocommerce_order_status_shipped', array( $this, 'orthoney_send_shipped_email' ) );
        
        add_action( 'init', array( $this, 'init_send_mail_customer_callback' ) );
        add_action( 'send_mail_customer', array( $this, 'send_mail_customer_callback' ) );

    }
    
    public function send_mail_customer_callback($offset = 0) {
        $chunk_size = 50; // Make sure this matches the send_mail function
        $results = OAM_ADMINISTRATOR_HELPER::update_wc_order_status_send_mail_callback(1, $offset);

        // Step 3: Schedule next batch only if this batch was full
        if (!empty($results)) {
           $next_offset = $results;
           if (!as_has_scheduled_action('update_wc_order_status')) {
                as_schedule_single_action(
                    time() + 60,
                    'send_mail_customer',
                    [ $next_offset ],
                    'tracking-order-group'
                );
            }
        }
    }

    public function init_send_mail_customer_callback(  ) {
        if(isset($_GET['send_mail_customer']) && $_GET['send_mail_customer'] == 'yes' ){
            
            $action_id = as_schedule_single_action(
                time() + 30,
                'send_mail_customer',
                [],
                'tracking-order-group'
            );
        }
    }

    public function orthoney_register_custom_order_emails( $emails ) {
        require_once OH_PLUGIN_DIR_PATH . 'classes/administrator/emails/class-wc-email-partial-shipped.php';
        require_once OH_PLUGIN_DIR_PATH . 'classes/administrator/emails/class-wc-email-shipped.php';

        $emails['WC_Email_Partial_Shipped'] = new WC_Email_Partial_Shipped();
        $emails['WC_Email_Shipped']        = new WC_Email_Shipped();

        return $emails;
    }

    public function orthoney_send_partial_shipped_email( $order_id ) {
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();

        if ( ! empty( $emails['WC_Email_Partial_Shipped'] ) ) {
            $emails['WC_Email_Partial_Shipped']->trigger( $order_id );
        }
    }

    public function orthoney_send_shipped_email( $order_id ) {
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();

        if ( ! empty( $emails['WC_Email_Shipped'] ) ) {
            $emails['WC_Email_Shipped']->trigger( $order_id );
        }
    }
}

new OAM_ADMINISTRATOR_CUSTOM_ORDER_STATUS_MAIL();
