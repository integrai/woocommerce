<?php if (!defined('ABSPATH')) { exit; }?>

<div class="clear"></div>
<div class="integrai_payment">
  <h4><?php echo __( 'Método de Pagamento', 'integrai' ) ?></h4>
  <p>
    <?php
      foreach ($data as $key => $value) {
        echo '<strong>' . $key . ':</strong> ' . $value . '<br />';
      }
    ?>

    <strong><?php echo __( 'Boleto' ) ?>: </strong>
    <a
      id="integrai_printBoleto"
      target="_blank"
      title="<?php __( 'Acessar boleto' ) ?>"
      href="<?php echo ($boleto_url || '') ?>">
      <?php echo __( ' Imprimir boleto ' ) ?>
    </a>
  </p>
</div>