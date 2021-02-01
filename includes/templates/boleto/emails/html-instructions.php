<?php if (!defined('ABSPATH')) { exit; }?>

<h2><?php esc_html_e( 'Payment', 'integrai' ); ?></h2>

<p class="order_details">
  <?php esc_html_e( 'Please use the link below to view your banking ticket, you can print and pay in your internet banking or in a lottery retailer:', 'integrai' ); ?>

  <br />
  <a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank">
    <?php esc_html_e( 'Pay the banking ticket', 'integrai' ); ?>
  </a>
  <br />

  <?php esc_html_e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'integrai' ); ?>
</p>
