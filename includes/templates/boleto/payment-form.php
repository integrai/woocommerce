<?php if (!defined('ABSPATH')) { exit; }?>

<?php
    $jsonOptions  = json_encode( isset($options) && !empty($options) ? $options : array() );
    $jsonCustomer = json_encode( isset($customer) && !empty($customer) ? $customer : array() );
?>

<div class="form-list" id="payment_form_integrai-boleto">
    <div id="integrai-payment-boleto"></div>
</div>

<script>

    window.integraiBoletoData = JSON.parse('<?php echo $jsonOptions ?>');

    window.IntegraiBoleto = Object.assign({}, integraiBoletoData.formOptions, {
        boletoModel: JSON.parse('<?php echo $jsonCustomer ?>'),
    });

    integraiBoletoData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>