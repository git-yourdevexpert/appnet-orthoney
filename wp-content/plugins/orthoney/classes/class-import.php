<?php

if (!defined('ABSPATH')) {
exit;
}
function import_affiliate($user_data) {
    global $wpdb;

    // Check if the affiliate already exists (by user_id or token)
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM wp_yith_wcaf_affiliates WHERE user_id = %d OR token = %s",
            $user_data->user_id,
            $user_data->orgCode
        )
    );

    // Only insert if it doesn't exist
    if ( $exists == 0 ) {
        $wpdb->insert('wp_yith_wcaf_affiliates', [
            'token'         => $user_data->orgCode,
            'user_id'       => $user_data->user_id,
            'click'         => 0,
            'conversion'    => 0,
            'enabled'       => $user_data->status == 'INACTIVE' ? -1 : 1,
        ]);
    }


    // Update user meta fields
    update_user_meta( $user_data->user_id, 'associated_affiliate_id', $user_data->user_id );
    update_user_meta( $user_data->user_id, 'associated_affiliate_id', $user_data->phone ); // This line will overwrite the one above

    update_user_meta( $user_data->user_id, '_yith_wcaf_name_of_your_organization', $user_data->orgName );
    update_user_meta( $user_data->user_id, '_yith_wcaf_your_organizations_website', $user_data->website );
    update_user_meta( $user_data->user_id, '_yith_wcaf_first_name', $user_data->orgName);
    update_user_meta( $user_data->user_id, '_affiliate_org_code', $user_data->orgCode );
    update_user_meta( $user_data->user_id, '_yith_wcaf_reject_message', $user_data->NOTES );
    update_user_meta( $user_data->user_id, '_yith_wcaf_phone_number', $user_data->phone );
    update_user_meta( $user_data->user_id, '_yith_wcaf_address', trim($user_data->street_1 . ' ' . $user_data->street_2) );
    update_user_meta( $user_data->user_id, '_yith_wcaf_city', $user_data->city );
    update_user_meta( $user_data->user_id, '_yith_wcaf_state', $user_data->state );
    update_user_meta( $user_data->user_id, '_yith_wcaf_zipcode', $user_data->zip );
}



function custom_add_user_roles() {

// Add Distributor role
    add_role(
        'distributor',
        'Distributor',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );

    // Add Sales Manager role
    add_role(
        'sales_manager',
        'Sales Manager',
        array(
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'edit_others_posts' => true,
        )
    );

    // Add Subscriber role (already exists by default in WordPress, but you can ensure it's created)
    add_role(
        'subscriber',
        'Subscriber',
        array(
            'read' => true,
        )
    );
}
add_action('init', 'custom_add_user_roles');


add_action('admin_menu', 'custom_import_data_admin_page');

function custom_import_data_admin_page() {
    add_menu_page(
        'Import Data',              // Page title
        'Import Data',              // Menu title
        'manage_options',           // Capability
        'import-data',              // Menu slug
        'import_data_page_content', // Callback function
        'dashicons-upload',         // Icon
        26                          // Position in menu
    );
}

function import_data_page_content() {
    ?>
    <div class="wrap">
        <h1>Import Data</h1>
        <p>Upload your CSV or Excel file here to import data.</p>
        
        <?php 
        global $wpdb;

        $sql = "SELECT 
        o.ID AS ID,
        0 AS pid,
        o.user_id AS user_id,
        0 AS created_by,
        o.OrderID AS order_id,
        0 AS recipient_id,
        CONCAT(o.OrderID,'-',o.ID) AS recipient_order_id,
        o.Code AS affiliate_token,
        o.RecipName AS full_name,
        o.RecipComp AS company_name,
        o.RecipAddr1 AS address_1,
        o.RecipAddr2 AS address_2,
        o.RecipCity AS city,
        o.RecipState AS state,
        o.RecipZip AS zipcode,
        'US' AS country,
        o.RecipQty AS quantity,
        CONCAT(o.GreetingWords, o.Greeting) AS greeting,
        o.DataDump AS created_date,
        o.DataDump AS updated_date,
        wcom.order_id AS wc_order,
        1 AS address_verified,
        '' AS order_type
    FROM orders o
    JOIN wp_wc_orders_meta wcom ON wcom.meta_value = o.OrderID AND wcom.meta_key = '_orthoney_OrderID' LIMIT 10";
    
    $results = $wpdb->get_results($wpdb->prepare($sql));

    echo "<pre>";
    print_r( $results );
    echo "</pre>";

            

        if (isset($_GET['mapping-affiliate-order']) && $_GET['mapping-affiliate-order'] == 'done') {

            //1. Get Affilite ID

            //2. Get Order ID

            // Order Meta Entry (wc_orders_meta) Add Token with Order ID
            //_yith_wcaf_referral
           // _yith_wcaf_referral_history


        //    2. woocommerce_order_items Table  
        // Get Product name with 
        // Get order_item_id
        //    - order_item_name
        //    - order_item_type
        //    - order_id

        //3. add commistion 

        // yith_wcaf_commissions

        global $wpdb;

        $sql = "
            INSERT INTO {$wpdb->prefix}yith_wcaf_commissions (
                order_id,
                line_item_id,
                product_id,
                product_name,
                affiliate_id,
                rate,
                line_total,
                amount,
                status
            )
           SELECT
                wm.order_id AS order_id,
                oi.order_item_id AS line_item_id,
                %d AS product_id,
                %s AS product_name,
                a.ID AS affiliate_id,
                %d AS rate,
                wco.total_amount AS line_total,
                (wco.total_amount / %d) AS amount,
                %s AS refunds
            FROM {$wpdb->prefix}yith_wcaf_affiliates a
            INNER JOIN orders o ON o.Code = a.token
            INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id
            INNER JOIN {$wpdb->prefix}wc_orders wco ON wco.id = wm.order_id  WHERE a.token != ''
        ";
        // $sql = "
            
        //     SELECT
        //         wm.order_id AS order_id,
        //         oi.order_item_id AS line_item_id,
        //         %d AS product_id,
        //         %s AS product_name,
        //         a.ID AS affiliate_id,
        //         %d AS rate,
        //         wco.total_amount AS line_total,
        //         (wco.total_amount / %d) AS amount,
        //         %s AS refunds
        //     FROM {$wpdb->prefix}yith_wcaf_affiliates a
        //     INNER JOIN orders o ON o.Code = a.token
        //     INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
        //     INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id
        //     INNER JOIN {$wpdb->prefix}wc_orders wco ON wco.id = wm.order_id  WHERE a.token != ''
        // ";
        
        // // Prepare and run safely
        echo $wpdb->prepare(
            $sql,
            1708,                      // product_id
            '8oz Honey (#1708)',       // product_name
            10,                        // rate
            10,                        // divisor for amount
            'pending'                  // refunds
        );
        
        // $results = $wpdb->get_results($wpdb->prepare(
        //     $sql,
        //     1708,                      // product_id
        //     '8oz Honey (#1708)',       // product_name
        //     10,                        // rate
        //     10,                        // divisor for amount
        //     'pending'                  // refunds
        // ));

        // echo "<pre>";
        // print_r($results);
        // echo "</pre>";










// Insert _yith_wcaf_referral
// $wpdb->query("
//     INSERT INTO {$wpdb->prefix}wc_orders_meta (order_id, meta_key, meta_value)
//     SELECT 
//         wm.order_id,
//         '_yith_wcaf_referral',
//         o.Code
//     FROM orders o
//     INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates a ON a.token = o.Code
//     INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
//     INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id
// ");

//INSERT INTO wp_wc_orders_meta (order_id, meta_key, meta_value) SELECT wm.order_id, '_yith_wcaf_referral', o.Code FROM orders o INNER JOIN wp_yith_wcaf_affiliates a ON a.token = o.Code INNER JOIN wp_wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID INNER JOIN wp_woocommerce_order_items oi ON oi.order_id = wm.order_id


// // Insert _yith_wcaf_referral_history (serialized)
// $wpdb->query("
    // INSERT INTO {$wpdb->prefix}wc_orders_meta (order_id, meta_key, meta_value)
    // SELECT 
    //     wm.order_id,
    //     '_yith_wcaf_referral_history',
    //     CONCAT('a:1:{i:0;s:', LENGTH(o.Code), ':\"', o.Code, '\";}')
    // FROM orders o
    // INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates a ON a.token = o.Code
    // INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
    // INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id
// ");

// INSERT INTO {$wpdb->prefix}wc_orders_meta (order_id, meta_key, meta_value)
//     SELECT 
//         wm.order_id,
//         '_yith_wcaf_referral_history',
//         CONCAT('a:1:{i:0;s:', LENGTH(o.Code), ':\"', o.Code, '\";}')
//     FROM orders o
//     INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates a ON a.token = o.Code
//     INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
//     INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id

//         $sql = "
//         SELECT 
//             o.Code AS token,
//             o.OrderID AS _orthoney_OrderID,
//             wm.order_id AS OrderID,
//             oi.order_item_id,
//             oi.order_item_name,
//             a.user_id AS affiliate_user_id
//         FROM orders o
//         INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates a
//             ON a.token = o.Code
//         INNER JOIN {$wpdb->prefix}wc_orders_meta wm
//             ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
//         INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
//             ON oi.order_id = wm.order_id
//     ";
    
//     $results = $wpdb->get_results($sql);
    

//     $data = [$row->token];
//     foreach ($results as $row) {
//     $wpdb->insert(
//         $wpdb->prefix . 'wc_orders_meta',
//         [
//             'order_id'   => $row->wc_order_id,
//             'meta_key'   => '_yith_wcaf_referral',
//             'meta_value' => $row->token,
//         ]
//     );
    
//     $wpdb->insert(
//         $wpdb->prefix . 'wc_orders_meta',
//         [
//             'order_id'   => $row->wc_order_id,
//             'meta_key'   => '_yith_wcaf_referral_history',
//             'meta_value' => maybe_serialize($data),
//         ]
//     );
// }


    // global $wpdb;
    // $tokens = $wpdb->get_col("SELECT DISTINCT Code FROM orders WHERE Code= 'AAC' ");
    // foreach ($tokens as $token) {


        // Prepare query to get unique customer–affiliate pairs
        
//         $sql = "
//         SELECT  DISTINCT 
//         wco.customer_id AS customer_id,
//         a.user_id AS affiliate_id
// FROM orders o
//     INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates a ON a.token = o.Code
//     INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
//     INNER JOIN {$wpdb->prefix}woocommerce_order_items oi  ON oi.order_id = wm.order_id
//     INNER JOIN {$wpdb->prefix}wc_orders wco ON wco.id = wm.order_id
//         ";

//         echo $wpdb->prepare($sql);

//         // Execute query
//         $results = $wpdb->get_results($wpdb->prepare($sql, $token));

        // echo "<pre>";
        // print_r($results );
        // echo "</pre>";

        // Insert each row into your custom table
        // if (!empty($results)) {
        //     foreach ($results as $row) {
        //     //Generate token
        //     $random_string = OAM_AFFILIATE_Helper::getRandomChars('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 10);
        //     $token = md5($random_string . time());
        //         $wpdb->query(
        //             $wpdb->prepare(
        //                 "
        //                 INSERT INTO {$wpdb->prefix}oh_affiliate_customer_linker 
        //                     (customer_id, affiliate_id, status, token)
        //                 VALUES (%d, %d, %d, %s)
        //                 ",
        //                 $row->customer_id,
        //                 $row->affiliate_id,
        //                 1,
        //                 $token
        //             )
        //         );
        //     }
        // }
    // }


        

        // // Step 1: Get all distinct tokens from orders table
        // $tokens = $wpdb->get_col("SELECT DISTINCT Code FROM orders WHERE Code= 'AAC'");

        // // // Step 2: Loop through each token and run a single JOIN query for each
        // foreach ($tokens as $token) {
            // One optimized SQL query with JOINs for each token

            /**
             * 
             * Used Only  Commition
             */
        //     $sql = "
        //     SELECT 
        //         a.token,
        //         a.user_id AS affiliate_id,
        //         o.OrderID AS _orthoney_OrderID,
        //         wm.order_id AS wc_order_id,
        //         wco.customer_id AS customer_id,
        //         oi.order_item_id,
        //         oi.order_item_name
        //     FROM {$wpdb->prefix}yith_wcaf_affiliates a
        //     INNER JOIN orders o ON o.Code = a.token
        //     INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.meta_key = '_orthoney_OrderID' AND wm.meta_value = o.OrderID
        //     INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = wm.order_id
        //     INNER JOIN {$wpdb->prefix}wc_orders wco ON wco.id = wm.order_id
        //     WHERE a.token = %s
        // ";
    

            // echo $wpdb->prepare($sql, $token);
            // $results = $wpdb->get_results($wpdb->prepare($sql, $token));

            // $data = [$row->token];
            
            // $wpdb->insert(
            //     $wpdb->prefix . 'wc_orders_meta',
            //     [
            //         'order_id'   => $row->wc_order_id,
            //         'meta_key'   => '_yith_wcaf_referral',
            //         'meta_value' => $row->token,
            //     ]
            // );
            
            // $wpdb->insert(
            //     $wpdb->prefix . 'wc_orders_meta',
            //     [
            //         'order_id'   => $row->wc_order_id,
            //         'meta_key'   => '_yith_wcaf_referral_history',
            //         'meta_value' => maybe_serialize($data),
            //     ]
            // );
            // Display results
            // if (!empty($results)) {
            //     echo "<pre>";
            //     foreach ($results as $row) {
            //         // echo "token = {$row->token}\n";
            //         echo "customer_id = {$row->customer_id}\n";
            //         echo "affiliate_id = {$row->affiliate_id}\n";
            //         echo "_orthoney_OrderID = {$row->_orthoney_OrderID}\n";
            //         echo "OrderID = {$row->wc_order_id}\n";
            //         echo "order_item_name = " . ($row->order_item_name ?? 'N/A') . "\n";
            //         echo "order_item_id = " . ($row->order_item_id ?? 'N/A') . "\n";
            //         echo "-----------------------------\n";
            //     }
            //     echo "</pre>";
            // }
        // }



        

            // echo '<p>mapping affiliate order.</p>';

            // $order_id = 47280;
            // $data = [$row->token];
            
            // $wpdb->insert(
            //     $wpdb->prefix . 'wc_orders_meta',
            //     [
            //         'order_id'   => $row->wc_order_id,
            //         'meta_key'   => '_yith_wcaf_referral',
            //         'meta_value' => $row->token,
            //     ]
            // );
            
            // $wpdb->insert(
            //     $wpdb->prefix . 'wc_orders_meta',
            //     [
            //         'order_id'   => $row->wc_order_id,
            //         'meta_key'   => '_yith_wcaf_referral_history',
            //         'meta_value' => maybe_serialize($data),
            //     ]
            // );

        }

        if (isset($_GET['user-role-team-affiliate']) && $_GET['user-role-team-affiliate'] == 'done') {

            $teams = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_value
                    FROM {$wpdb->usermeta}
                    WHERE user_id = %d AND meta_key = %s",
                    447,
                    '_userinfo'
                )
            );
            
            echo "<pre>";
            print_r($teams);
            
            // Access meta_value from object
            if (!empty($teams)) {
                $meta_value = $teams[0]->meta_value;
                echo "1";
                print_r($meta_value);
                echo "2";
                print_r(unserialize($meta_value)['Distributor']);
            }
            echo "</pre>";

            // if (email_exists($email)) {
            //     wp_send_json(['success' => false, 'message' => esc_html__('Organization Users is Already Exist', 'text-domain')]);
            // }
            

        }
        if (isset($_GET['user-role-change-affiliate']) && $_GET['user-role-change-affiliate'] == 'done') {
            $args = array(
                'role'    => 'distributor',
                'fields'  => 'ID',
                'number'  => 500,
            );
        
            $user_query = new WP_User_Query( $args );
        
            if ( ! empty( $user_query->get_results() ) ) {
                foreach ( $user_query->get_results() as $user_id ) {
                    echo $user_id . '<br>'; // for debugging
                    $user = new WP_User( $user_id );
                    $user->remove_role( 'distributor' );
                    $user->add_role( 'yith_affiliate' );
                    $user->add_role( 'customer' );
                    update_user_meta( $user_id, 'associated_affiliate_id', $user_id);
                }
            } else {
                echo 'No distributor found.';
            }
        }

        if (isset($_GET['user-role-affiliate']) && $_GET['user-role-affiliate'] == 'done') {
            echo "affiliate group";

                        
            

            //             UPDATE distributors d
            // JOIN wp_users u ON d.user_id = u.ID
            // SET d.email = u.user_email
            // WHERE d.email = '';

            // SELECT d.*
            // FROM distributors d
            // JOIN (
            //     SELECT email
            //     FROM distributors
            //     WHERE email != ''
            //     GROUP BY email
            //     HAVING COUNT(*) > 1
            // ) dup ON d.email = dup.email
            // ORDER BY d.email;

            // SELECT first_name, last_name, orgName, email
            // FROM distributors
            // WHERE email IN (
            //   'AlisonAdler@nykolami.org',
            //   'Sybarra@bethshalomnb.org',
            //   'Srwag8@comcast.net',
            //   'Lwertheimer@mac.com',
            //   'Cbalpern@yahoo.com',
            //   'Boopedo1@gmail.com',
            //   'Eileenbloom@gmail.com',
            //   'Francesbloom12@gmail.com',
            //   'Mikelauren1212@gmail.com',
            //   'Batyamsanibel@gmail.com',
            //   'Templeoffice@templeisraelnh.org',
            //   'sewflash@gmail.com',
            //   'Homeoffice777@aol.com',
            //   'Sisterhood@vbsds.org',
            //   'Mandalaybeachgirl@gmail.com',
            //   'Catshpresident@gmail.com',
            //   'elliot.shulman@avodah.org',
            //   'Pauladreyfuss131@gmail.com'
            // )
            // ORDER BY email;

            // DELETE FROM distributors
            // WHERE did IN (
            //   1193, 921, 1267, 1089, 1322, 1160, 1072, 813, 806,
            //   770, 1096, 746, 1143, 811, 1172, 776, 1320, 756
            // );


            $distributors = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT user_id FROM `distributors`"
                )
            );
         

            foreach ($distributors as $data) {
                $user = get_userdata($data->user_id);
            
                if ($user && !empty($user->roles)) {
                    foreach ($user->roles as $role) {
                        if (!isset($distributor_roles[$role])) {
                            $distributor_roles[$role] = [];
                        }
                        $distributor_roles[$role][] = $data->user_id;
                    }
                }
            }
            echo "<pre>";
            
            print_r($distributor_roles);
            count($distributor_roles);
            echo "</pre>";
           

            // foreach ($distributors as $key => $data) {
            //     $user = get_userdata($data->user_id);

            //         if ($user) {
            //             $email = $user->user_email;
            //             $wpdb->update(
            //                 'distributors',
            //                 ['email' => $email],                  // Set this value
            //                 ['user_id' => $data->user_id],        // Where condition
            //                 ['%s'],                               // Value format
            //                 ['%d']                                // Where format
            //             );
            //         } 
            // }
        }

        if (isset($_GET['user-role-change-subscriber']) && $_GET['user-role-change-subscriber'] == 'done') {
            $args = array(
                'role'    => 'subscriber',
                'fields'  => 'ID',
                'number'  => 500,
            );
        
            $user_query = new WP_User_Query( $args );
        
            if ( ! empty( $user_query->get_results() ) ) {
                foreach ( $user_query->get_results() as $user_id ) {
                    echo $user_id . '<br>'; // for debugging
                    $user = new WP_User( $user_id );
                    $user->remove_role( 'subscriber' );
                    $user->add_role( 'customer' );
                }
            } else {
                echo 'No subscribers found.';
            }
            
        }
        if (isset($_GET['import-group']) && $_GET['import-group'] == 'done') {
            echo "import-group";

            // INSERT INTO wp_oh_group (user_id, pid, order_id, visibility, name)
            // SELECT 
            //     user_id,
            //     0 AS pid,
            //     OrderID AS order_id,
            //     1 AS visibility,
            //     OrderID AS name
            // FROM orders
            // WHERE user_id != 0
            // GROUP BY user_id, OrderID;
            $group_table             = OAM_Helper::$group_table;

            $order_unique = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT user_id,
                    GROUP_CONCAT(DISTINCT OrderID) AS order_ids,
                    COUNT(DISTINCT OrderID) AS unique_order_count
                    FROM orders
                    WHERE user_id != 0
                    GROUP BY user_id"
                )
            );
            foreach ($order_unique as $key => $data) {
            $order_ids = explode(',', $data->order_ids);
                foreach ($order_ids as $key => $OrderID) {   
                    $wpdb->insert($group_table, [
                        'user_id'    => $data->user_id,
                        'pid'        => 0,
                        'order_id'   => $OrderID,
                        'visibility' => 1,
                        'name'       => $OrderID,
                    ]);
                }
            }
        }


        if (isset($_GET['import-group-recipient']) && $_GET['import-group-recipient'] == 'done') {
            echo 'import-group-recipient';

            // INSERT INTO wp_oh_group_recipient (
            //     user_id,
            //     recipient_id,
            //     group_id,
            //     order_id,
            //     full_name,
            //     company_name,
            //     address_1,
            //     address_2,
            //     city,
            //     state,
            //     zipcode,
            //     quantity,
            //     verified,
            //     address_verified,
            //     visibility,
            //     new,
            //     greeting,
            //     reasons
            // )
            // SELECT 
            //     o.user_id,
            //     0 AS recipient_id,
            //     g.id AS group_id,
            //     g.order_id,
            //     o.RecipName AS full_name,
            //     o.RecipComp AS company_name,
            //     o.RecipAddr1 AS address_1,
            //     o.RecipAddr2 AS address_2,
            //     o.RecipCity AS city,
            //     o.RecipState AS state,
            //     o.RecipZip AS zipcode,
            //     o.RecipQty AS quantity,
            //     wcom.order_id AS wc_order,
            //     1 AS verified,
            //     1 AS address_verified,
            //     1 AS visibility,
            //     0 AS new,
            //     CONCAT(o.GreetingWords, o.Greeting) AS greeting,
            //     '' AS reasons
            // FROM wp_oh_group g
            // JOIN orders o ON o.OrderID = g.order_id;
            // JOIN wp_wc_orders_meta wcom ON wcom.meta_value = g.order_id AND wcom.meta_key = '_orthoney_OrderID' LIMIT 100;

            $group_table = 'wp_oh_group';
            $groups = $wpdb->get_results(
                "SELECT * FROM $group_table"
            );
            foreach ($groups as $key => $data) {
                $order_id = $data->order_id;
                $id = $data->id;
                $order_group = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT *
                        FROM orders
                        WHERE OrderID = $order_id
                        GROUP BY OrderID"
                    )
                );
               foreach ($order_group as $key => $data) {
                $wpdb->insert('wp_oh_group_recipient', [
                    'user_id'     => $data->user_id,
                    'recipient_id' => 0,
                    'group_id' => $id,
                    'order_id'   => $order_id,
                    'full_name'  => $data->RecipName,
                    'company_name'  => $data->RecipComp,
                    'address_1'  => $data->RecipAddr1,
                    'address_2'  => $data->RecipAddr2,
                    'city'  => $data->RecipCity,
                    'state'  => $data->RecipState,
                    'zipcode'  => $data->RecipZip,
                    'quantity'  => $data->RecipQty,
                    'verified'  => 1,
                    'address_verified'  => 1,
                    'visibility'  => 1,
                    'new'  => 0,
                    'greeting'  => $data->GreetingWords .''. $data->Greeting ,
                    'reasons'  => '',
                ]);
               }
            }


        }
        
        ?>
    </div>
    <?php
}

