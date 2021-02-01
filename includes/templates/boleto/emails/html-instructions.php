<?php if (!defined('ABSPATH')) { exit; }?>

<h2><?php esc_html_e( 'Pagamento', 'integrai' ); ?></h2>

<p class="order_details">
  <?php esc_html_e( 'Utilize o link abaixo para visualizar o boleto:', 'integrai' ); ?>

  <br />
  <a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank">
    <?php esc_html_e( 'Imprimir boleto', 'integrai' ); ?>
  </a>
  <br />

  <?php esc_html_e( 'O seu pedido será processado assim que recebermos a confirmação do pagamento do boleto.', 'integrai' ); ?>
</p>
