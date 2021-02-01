<?php if (!defined('ABSPATH')) { exit; }?>

<div class="form-list" id="payment_form_integrai">
    <div id="integrai-payment-creditcard"></div>
</div>

<script>
    if (!window.integraiCCData) {
        window.integraiCCData = JSON.parse('<?php echo json_encode( $options ) ?>');
    }

    window.IntegraiCreditCard = Object.assign({}, integraiCCData.formOptions, {
        amount: <?php echo $total ?>
    });

    integraiCCData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>