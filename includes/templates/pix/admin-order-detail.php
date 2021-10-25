<?php if (!defined('ABSPATH')) { exit; }?>

<div class="clear"></div>
<div class="integrai_payment">
  <h4><?php echo __( 'Método de Pagamento', 'integrai' ) ?></h4>
  <p>
    <?php
      foreach ($data as $key => $value) {
        if (!empty($value)) {
            echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br />';
        }
      }
    ?>

    <strong><?php echo __( 'Pix' ) ?>: </strong>
    <div style="margin-top: 1rem">
        <img height="150" style="height: 150px" src="data:image/jpeg;base64,<?php echo $qr_code_base64; ?>"/>
        <div>
            <strong><?php echo __( 'Código QR', 'integrai' ) ?></strong>
        </div>
        <input style="width: 80%;padding: .5rem;" readonly value="<?php echo $qr_code; ?>">
    </div>
  </p>
</div>