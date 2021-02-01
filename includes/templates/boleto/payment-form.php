<?php if (!defined('ABSPATH')) { exit; }?>

<div class="form-list" id="payment_form_integrai-boleto">
    <div id="integrai-payment-boleto"></div>
</div>

<script>
    window.integraiBoletoData = JSON.parse('<?php echo json_encode( $options ) ?>');

    window.IntegraiBoleto = Object.assign({}, integraiBoletoData.formOptions, {
        boletoModel: JSON.parse('<?php echo json_encode( $customer ) ?>'),
    });

    console.log(window.IntegraiBoleto)

    integraiBoletoData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>