<?php if (!defined('ABSPATH')) { exit; }?>

<h2><?php esc_html_e( 'Pagamento', 'integrai' ); ?></h2>

<p class="order_details">
  <?php printf(
      wp_kses( __( 'Pagamento com cartão de crédito %1$s em %2$s realizado com sucesso.', 'integrai' ), array( 'strong' => array() ) ),
      '<strong>' . esc_html( $card_brand ) . '</strong>',
      '<strong>' . intval( $installments ) . '</strong>'
  ); ?>
</p>
