<?php if (!defined('ABSPATH')) { exit; }?>

<?php
    $jsonOptions  = json_encode( isset($options) && !empty($options) ? $options : array() );
    $jsonCustomer = json_encode( isset($customer) && !empty($customer) ? $customer : array() );
?>

<div class="form-list" id="payment_form_integrai-pix">
    <div id="integrai-payment-pix"></div>
</div>

<script>

    window.integraiPixData = JSON.parse('<?php echo $jsonOptions ?>');

    window.IntegraiPix = Object.assign({}, integraiPixData.formOptions, {
        pixModel: JSON.parse('<?php echo $jsonCustomer ?>'),
    });

    integraiPixData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>