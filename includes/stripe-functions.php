<?php

/**
 * Display the Stripe Form in a Thickbox Pop-up
 *
 * @param $atts array Undefined, have not found any use yet
 * @return string Form Pop-up Link (wrapped in <a></a>)
 *
 * @since 1.3
 *
 */

function wp_stripe_shortcode( $atts ){

    $options = get_option('wp_stripe_options');

    if ( $options['stripe_address_switch'] == 'Yes' ) {
      $settings = '?keepThis=true&TB_iframe=true&height=460&width=800';
    } else {
      $settings = '?keepThis=true&TB_iframe=true&height=580&width=400';
    }
    $path = WP_STRIPE_PATH . '/includes/stripe-iframe.php'. $settings;
    $count = 1;

    if ( $options['stripe_modal_ssl'] == 'Yes' ) {
        $path = str_replace("http", "https", $path, $count);
    }

    extract(shortcode_atts(array(
        'cards' => 'true'
    ), $atts));

    if ( $cards == 'true' )  {
        $payments = '<div id="wp-stripe-types"></div>';
    }

    return '<a class="thickbox" id="wp-stripe-modal-button" title="' . $options['stripe_header'] . '" href="' . $path . '">' . $options['stripe_header'] . '</a>' . $payments;

}
add_shortcode( 'wp-stripe', 'wp_stripe_shortcode' );

/**
 * Display Legacy Stripe form in-line
 *
 * @param $atts array Undefined, have not found any use yet
 * @return string Form / DOM Content
 *
 * @since 1.3
 *
 */

function wp_stripe_shortcode_legacy( $atts ){

    return wp_stripe_form();
}
add_shortcode( 'wp-legacy-stripe', 'wp_stripe_shortcode_legacy' );

/**
 * Create Charge using Stripe PHP Library
 *
 * @param $amount int transaction amount in cents (i.e. $1 = '100')
 * @param $card string
 * @param $description string
 * @return array
 *
 * @since 1.0
 *
 */

function wp_stripe_charge($amount, $card, $name, $description) {

    /*
     * Currency - All amounts must be denominated in USD when creating charges with Stripe — the currency conversion happens automatically
     */

    $currency = 'usd';

    /*
     * Card - Token from stripe.js is provided (not individual card elements)
     */

    $charge = array(
        'card' => $card,
        'amount' => $amount,
        'currency' => $currency,
    );

    if ( $description ) {
        $charge['description'] = $description;
    }

    $response = Stripe_Charge::create($charge);

    return $response;

}

/**
 * Find existing plan or create a new one from parameters using Stripe PHP Library
 *
 * @param $amount int transaction amount in cents (i.e. $1 = '100')
 * @param $interval string one of two options, 'month' or 'year'
 * @return array
 *
 * @since 1.4.5
 *
 */

function wp_stripe_find_or_create_plan($amount, $interval) {
    
    /*
     * Currency - All amounts must be denominated in USD when creating charges with Stripe — the currency conversion happens automatically
     */
    $currency = 'usd';

    $plan = null;

    // Construct desired plan
    $requested_plan = array(
        'amount' => $amount,
        'interval' => $interval,
        'name' => '$' . $amount/100 . '/' . $interval,
        'currency' => $currency,
        'id' => $amount . $interval[0] . '_wp'
    );

    $existing_plans = Stripe_Plan::all();

    // Loop through plans, looking for one that matches our desired plan
    foreach( $existing_plans->data as $existing_plan ) {
        if ( $existing_plan->amount == $requested_plan['amount'] && $existing_plan->interval == $requested_plan['interval'] ) {
            $plan = $existing_plan;
            break;
        }
    }

    // Create a new plan if we didn't find one that matched
    if ( !$plan ) {
        $plan = Stripe_Plan::create($requested_plan);
    }
    
    return $plan;
}

/**
 * Create customer by susbscribing them to a plan using Stripe PHP Library
 *
 * @param $email string
 * @param $card string
 * @param $plan_id int 
 * @param $description string
 * @return array
 *
 * @since 1.4.5
 *
 */

function wp_stripe_subscribe_customer_to_plan($email, $card, $plan_id, $description) {

    $customer = array(
        'email' => $email,
        'card' => $card,
        'plan' => $plan_id
    );

    if ( $description ) {
        $customer['description'] = $description;
    }

    $response = Stripe_Customer::create($customer);

    return $response;
}

/**
 * Find the charge for a customer's last invoice on a given plan using Stripe PHP Library
 *
 * @param $customer_id int
 * @param $plan_id int 
 * @return array
 *
 * @since 1.4.5
 *
 */

function wp_stripe_find_customer_subscription_charge($customer_id, $plan_id) {

    $customer_invoices = Stripe_Invoice::all(array('customer' => $customer_id));

    $matching_invoice = null; 

    // Loop through invoices looking for a matching subscription
    foreach( $customer_invoices->data as $invoice) {
        if ( $invoice->lines->subscriptions ) {
            foreach( $invoice->lines->subscriptions as $subscription ) {
                if ( $subscription->plan->id == $plan_id ) {
                    $matching_invoice = $invoice;  
                    break 2;
                }
            }
        }
    }

    // Get the charge if we found a matching invoice
    $charge = $matching_invoice ? Stripe_Charge::retrieve($matching_invoice->charge) : null;

    return $charge;
}

/**
 * 3-step function to Process & Save Transaction
 *
 * 1) Capture POST
 * 2) Create Charge using wp_stripe_charge()
 * 3) Store Transaction in Custom Post Type
 *
 * @since 1.0
 *
 */

add_action('wp_ajax_wp_stripe_charge_initiate', 'wp_stripe_charge_initiate');
add_action('wp_ajax_nopriv_wp_stripe_charge_initiate', 'wp_stripe_charge_initiate');

function wp_stripe_charge_initiate() {

        // Security Check

        if ( ! wp_verify_nonce( $_POST['nonce'], 'wp-stripe-nonce' ) ) {
            die ( 'Nonce verification failed');
        }

        // Define/Extract Variables

        $public = $_POST['wp_stripe_public'];
        $name = $_POST['wp_stripe_name'];
        $email = $_POST['wp_stripe_email'];
        $amount = str_replace('$', '', $_POST['wp_stripe_amount']) * 100;
        $card = $_POST['stripeToken'];
        $type = $_POST['wp_stripe_type'];
        $details = 'Email: ' . $_POST['wp_stripe_email'];

        if ( $_POST['wp_stripe_address'] ) {
            $address = implode(', ', array($_POST['wp_stripe_address'], $_POST['wp_stripe_city'], $_POST['wp_stripe_state'], $_POST['wp_stripe_zip']));
            $phone = $_POST['wp_stripe_phone'];
            $details .= ' // Address: ' . $address . ' // Phone: ' . $phone;
        }

        if ( $_POST['wp_stripe_comment'] ) {
            $details .= ' // Comment: ' . $_POST['wp_stripe_comment'];
        }

        // Create Charge

        try {

            // Recurring donation
            if ( $type && $type == 'recurring' ) {

                $interval = $_POST['wp_stripe_interval'];

                // Make sure we have the plan we want
                $plan = wp_stripe_find_or_create_plan($amount, $interval);
                
                // Subscribe the customer to that plan
                $customer = wp_stripe_subscribe_customer_to_plan ($email, $card, $plan->id, $details);

                // Get the charge that we just created
                $response = wp_stripe_find_customer_subscription_charge($customer->id, $plan->id);
              
            // One Time donation
            } else {

                $response = wp_stripe_charge($amount, $card, $name, $details);

            }

            $id = $response->id;
            $amount = ($response->amount)/100;
            $currency = $response->currency;
            $created = $response->created;
            $live = $response->livemode;
            $paid = $response->paid;
            $fee = $response->fee;
            $type = $plan ? 'Recurring' : 'One-Time';

            $result =  '<div class="wp-stripe-notification wp-stripe-success"> ' . __('Thank you! Your payment of ', 'wp-stripe') . '<span class="wp-stripe-currency">' . $currency . '</span> ' . $amount . ' was successful.<div>';

            // Save Charge

            if ( $paid == true ) {

                $new_post = array(
                    'ID' => '',
                    'post_type' => 'wp-stripe-trx',
                    'post_author' => 1,
                    'post_content' => $details,
                    'post_title' => $id,
                    'post_status' => 'publish',
                );

                $post_id = wp_insert_post( $new_post );

                // Define Livemode

                if ( $live ) {
                    $live = 'LIVE';
                } else {
                    $live = 'TEST';
                }

                // Define Public (for Widget)

                if ( $public == 'public' ) {
                    $public = 'YES';
                } else {
                    $public = 'NO';
                }

                // Update Meta

                update_post_meta( $post_id, 'wp-stripe-public', $public);
                update_post_meta( $post_id, 'wp-stripe-name', $name);
                update_post_meta( $post_id, 'wp-stripe-email', $email);

                update_post_meta( $post_id, 'wp-stripe-live', $live);
                update_post_meta( $post_id, 'wp-stripe-date', $created);
                update_post_meta( $post_id, 'wp-stripe-amount', $amount);
                update_post_meta( $post_id, 'wp-stripe-currency', strtoupper($currency));
                update_post_meta( $post_id, 'wp-stripe-fee', $fee);
                update_post_meta( $post_id, 'wp-stripe-type', $type);

                // Update Project

                // wp_stripe_update_project_transactions( 'add', $project_id , $post_id );

            }

        // Error

        } catch (Exception $e) {
            $result = '<div class="wp-stripe-notification wp-stripe-failure">' . __('Oops, something went wrong', 'wp-stripe' ) . ' (' . $e->getMessage() . ')</div>';
        }

        // Return Results to JS

        header( "Content-Type: application/json" );
        echo json_encode($result);
        exit;

}

?>
