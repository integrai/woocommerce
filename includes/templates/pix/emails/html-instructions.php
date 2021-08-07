<?php if (!defined('ABSPATH')) { exit; }?>

<h2><?php esc_html_e( 'Pagamento', 'integrai' ); ?></h2>

<p class="order_details">
  <?php esc_html_e( 'Utilize o QR Code abaixo para fazer o pagamento via Pix:', 'integrai' ); ?>

  <br />

  <div style="margin-top: 1rem">
    <img height="150" style="height: 150px" src="data:image/jpeg;base64,<?php echo $qr_code_base64; ?>"/>
    <div>
        <strong>Código QR: </strong>
    </div>
    <input style="width: 80%;padding: .5rem;" readonly value="<?php echo $qr_code; ?>">
  </div>

  <br />

  <?php esc_html_e( 'O seu pedido será processado assim que recebermos a confirmação do pagamento do pix.', 'integrai' ); ?>
</p>
