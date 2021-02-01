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
    </p>
</div>