<?php if (!defined('ABSPATH')) { exit; }?>

<script>
    const {
        pageOptions = {},
        scripts = [],
    } = JSON.parse('<?php echo $pageSuccess ?>');

    window.IntegraiSuccess = {
        ...pageOptions,
        order: JSON.parse('<?php echo $order ?>'),
    };

    scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;
        document.head.appendChild(scriptElm);
    });
</script>

<ul class="order_details">
    <li>
        <div id="integrai-payment-success"></div>
    </li>
</ul>