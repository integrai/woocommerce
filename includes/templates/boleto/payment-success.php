<?php if (!defined('ABSPATH')) { exit; }?>

<ul class="order_details">
  <li>
    <div id="integrai-payment-success"></div>
  </li>
</ul>

<script>
    const integraiSuccessData = JSON.parse('<?php echo json_encode( $options ) ?>');

    window.IntegraiSuccess = Object.assign({}, integraiSuccessData.pageOptions, {
        order: JSON.parse('<?php echo json_encode( $order ) ?>'),
    });

    integraiSuccessData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>