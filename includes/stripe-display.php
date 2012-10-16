<?php

/**
 * Display Stripe Form
 *
 * @return string Stripe Form (DOM)
 *
 * @since 1.0
 *
 */

function wp_stripe_form() {

    ob_start();

    $options = get_option('wp_stripe_options');

    ?>

    <!-- Start WP-Stripe -->

    <div id="wp-stripe-wrap"<?php if ( $options['stripe_address_switch'] == 'Yes' ): echo 'class="two-column"'; endif; ?>>

    <form id="wp-stripe-payment-form">

    <input type="hidden" name="action" value="wp_stripe_charge_initiate" />
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'wp-stripe-nonce' ); ?>" />

    <div class="wp-stripe-notification wp-stripe-failure payment-errors" style="display:none"></div>

    <div class="wp-stripe-details">

        <div class="stripe-row">
                <input type="text" name="wp_stripe_name" class="wp-stripe-name" placeholder="<?php _e('Name', 'wp-stripe'); ?> *" autofocus required />
        </div>

        <div class="stripe-row">
                <input type="email" name="wp_stripe_email" class="wp-stripe-email" placeholder="<?php _e('E-mail', 'wp-stripe'); ?> *" required />
        </div>

        <?php if ( $options['stripe_address_switch'] == 'Yes' ): ?>
        <div class="stripe-row">
                <input type="text" name="wp_stripe_address" class="wp-stripe-address no-icon" placeholder="<?php _e('Address', 'wp-stripe'); ?> *" required />
        </div>

        <div class="stripe-row">
                <input type="text" name="wp_stripe_city" class="wp-stripe-city no-icon" placeholder="<?php _e('City', 'wp-stripe'); ?> *" required />
        </div>

        <div class="stripe-row">
          <div class="stripe-row-left">
              <span class="stripe-expiry">STATE</span>
              <select name="wp_stripe_state" class="wp-stripe-city">
                  <option value="" selected="selected">Please select</option>
                  <option value="AK">AK</option>
                  <option value="AL">AL</option>
                  <option value="AR">AR</option>
                  <option value="AZ">AZ</option>
                  <option value="CA">CA</option>
                  <option value="CO">CO</option>
                  <option value="CT">CT</option>
                  <option value="DC">DC</option>
                  <option value="DE">DE</option>
                  <option value="FL">FL</option>
                  <option value="GA">GA</option>
                  <option value="HI">HI</option>
                  <option value="IA">IA</option>
                  <option value="ID">ID</option>
                  <option value="IL">IL</option>
                  <option value="IN">IN</option>
                  <option value="KS">KS</option>
                  <option value="KY">KY</option>
                  <option value="LA">LA</option>
                  <option value="MA">MA</option>
                  <option value="MD">MD</option>
                  <option value="ME">ME</option>
                  <option value="MI">MI</option>
                  <option value="MN">MN</option>
                  <option value="MO">MO</option>
                  <option value="MS">MS</option>
                  <option value="MT">MT</option>
                  <option value="NC">NC</option>
                  <option value="ND">ND</option>
                  <option value="NE">NE</option>
                  <option value="NH">NH</option>
                  <option value="NJ">NJ</option>
                  <option value="NM">NM</option>
                  <option value="NV">NV</option>
                  <option value="NY">NY</option>
                  <option value="OH">OH</option>
                  <option value="OK">OK</option>
                  <option value="OR">OR</option>
                  <option value="PA">PA</option>
                  <option value="RI">RI</option>
                  <option value="SC">SC</option>
                  <option value="SD">SD</option>
                  <option value="TN">TN</option>
                  <option value="TX">TX</option>
                  <option value="UT">UT</option>
                  <option value="VA">VA</option>
                  <option value="VT">VT</option>
                  <option value="WA">WA</option>
                  <option value="WI">WI</option>
                  <option value="WV">WV</option>
                  <option value="WY">WY</option>
              </select>
          </div>
          <div class="stripe-row-right">
              <input type="text" name="wp_stripe_zip" class="wp-stripe-zip no-icon" placeholder="<?php _e('Zip', 'wp-stripe'); ?> *" required />
          </div>
        </div>

        <div class="stripe-row">
                <input type="text" name="wp_stripe_phone" class="wp-stripe-phone no-icon" placeholder="<?php _e('Phone Number', 'wp-stripe'); ?> *" required />
        </div>
        <?php endif; ?>

    </div>

    <div class="wp-stripe-card">
         <div class="stripe-row">
            <input type="text" name="wp_stripe_amount" autocomplete="off" class="wp-stripe-card-amount" id="wp-stripe-card-amount" placeholder="<?php _e('Amount (USD)', 'wp-stripe'); ?> *" required />
        </div>

        <?php if ( $options['stripe_recurring_switch'] == 'Yes' ): ?>
        <div class="stripe-row">
            <label><?php _e('Payment Type', 'wp-stripe'); ?></label>
            <input type="radio" name="wp_stripe_type" class="wp-stripe-type" value="once" checked /> <span class="wp-stripe-radio-text">One-Time</span>
            <input type="radio" name="wp_stripe_type" class="wp-stripe-type" value="recurring" /> <span class="wp-stripe-radio-text">Recurring</span>
        </div>
        <div class="stripe-row" id="frequency" style="display: none;">
            <label><?php _e('Frequency', 'wp-stripe'); ?></label>
            <select name="wp_stripe_interval" class="wp-stripe-interval">
                <option value="month">Monthly</option>
                <option value="year">Yearly</option>
            </select>

        </div>
        <?php endif; ?>

       <div class="stripe-row">
                <textarea name="wp_stripe_comment" class="wp-stripe-comment" placeholder="<?php _e('Comment', 'wp-stripe'); ?>"></textarea>
        </div>

        <div class="stripe-row">
            <input type="text" autocomplete="off" class="card-number" placeholder="<?php _e('Card Number', 'wp-stripe'); ?> *" required />
        </div>

        <div class="stripe-row">
            <div class="stripe-row-left">
                <input type="text" autocomplete="off" class="card-cvc" placeholder="<?php _e('CVC Number', 'wp-stripe'); ?> *" maxlength="4" required />
            </div>
            <div class="stripe-row-right">
                <span class="stripe-expiry">EXPIRY</span>
                <select class="card-expiry-month">
                    <option value="1">01</option>
                    <option value="2">02</option>
                    <option value="3">03</option>
                    <option value="4">04</option>
                    <option value="5">05</option>
                    <option value="6">06</option>
                    <option value="7">07</option>
                    <option value="8">08</option>
                    <option value="9">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
                <span></span>
                <select class="card-expiry-year">
                <?php
                    $year = date(Y,time());
                    $num = 1;

                    while ( $num <= 7 ) {
                        echo '<option value="' . $year .'">' . $year . '</option>';
                        $year++;
                        $num++;
                    }
                ?>
                </select>
            </div>

        </div>

        </div>

        <?php if ( $options['stripe_recent_switch'] == 'Yes' ) { ?>

        <div class="stripe-row">

            <input type="checkbox" name="wp_stripe_public" value="public" checked="checked" /> <label><?php _e('Display on Website?', 'wp-stripe'); ?></label>

            <p class="stripe-display-comment"><?php _e('If you check this box, the name as you enter it (including the avatar from your e-mail) and comment will be shown in recent donations. Your e-mail address and donation amount will not be shown.', 'wp-stripe'); ?></p>

        </div>

        <?php }; ?>

        <div style="clear:both"></div>

        <div class="wp-stripe-actions">
          <input type="hidden" name="wp_stripe_form" value="1"/>

          <button type="submit" class="stripe-submit-button"><?php _e('Submit Payment', 'wp-stripe'); ?></button>
          <div class="stripe-spinner"></div>

          <div class="wp-stripe-poweredby">Payments powered by <a href="http://wordpress.org/extend/plugins/wp-stripe" target="_blank">WP-Stripe</a>. No card information is stored on this server.</div>
        </div>


    </form>

    </div>


    <!-- End WP-Stripe -->

    <?php

    $output = ob_get_contents();
    ob_end_clean();

    return $output;

}

?>
