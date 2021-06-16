<?php if (!defined('ABSPATH')) { exit; }?>

<ul class="order_details">
  <li>
    <div id="integrai-payment-success"></div>
  </li>
</ul>

<?php

function printData($data) {
  is_array($data) ? print_r($data) : print($data);
  echo '<br/>';
}

function loopMap($key, $value) {
  $htmlFields = array( 'message', 'afterMessage', 'beforeMessage' );

  return in_array($key, $htmlFields)
    ? esc_html($value)
    : sanitize_text_field($value);

}

function sanitizeFields($fields) {
    $keys = array_keys($fields);

    return array_map('loopMap', $fields, $keys);
}

function sanitizeScripts($scripts) {
    return json_encode(
      array_map('sanitize_text_field', $scripts)
    );
}

function sanitizePageOptions($options) {
    $scripts = $options['scripts'];

    $pageOptions = $options['pageOptions'];

    $boleto = $pageOptions['boleto'];
    $creditcard = $pageOptions['creditcard'];

    return json_encode(
      array(
        'scripts' => sanitizeScripts($scripts),
        'boleto' => sanitizeFields($boleto),
        'creditcard' => sanitizeFields($creditcard),
      )
    );
}

?>

<script>
    const integraiSuccessData = JSON.parse('<?php echo sanitizePageOptions( $options ) ?>');

    window.IntegraiSuccess = Object.assign({}, integraiSuccessData.pageOptions, {
        order: JSON.parse('<?php echo sanitizeFields( $order ) ?>'),
    });

    integraiSuccessData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>