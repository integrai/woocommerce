<?php if (!defined('ABSPATH')) { exit; }?>

<ul class="order_details">
  <li>
    <div id="integrai-payment-success"></div>
  </li>
</ul>

<?php

function sanitizeFields($fields) {
    $newFields = array();

    foreach ($fields as $key => $value) {
      $newFields[$key] = !empty($value)
        ? esc_html( sanitize_text_field( $value ) )
        : '';
    }

    return $newFields;
}

function sanitizeScripts($scripts) {
    $newFields = array();

    foreach ($scripts as $item) {
      array_push($newFields, esc_url( sanitize_text_field($item) ));
    }

    return $newFields;
}

function sanitizePageOptions($options) {
  if (isset($options)) {
    $result = [];

    $scripts = $options['scripts'];
    $pageOptions = $options['pageOptions'];

    $boleto = $pageOptions['boleto'];
    $creditcard = $pageOptions['creditcard'];

    $result['scripts'] = isset($scripts) ? sanitizeScripts($scripts) : array();
    $result['boleto'] = isset($boleto) ? sanitizeFields($boleto) : array();
    $result['creditcard'] = isset($creditcard) ? sanitizeFields($creditcard) : array();

    try {
      return json_encode($result);
    } catch (Throwable $e) {
      Integrai_Helper::log( $e->getMessage() );
    } catch (Exception $e) {
      Integrai_Helper::log( $e->getMessage() );
    }
  }
}

function sanitizeOrder($order) {
  try {
    return json_encode( sanitizeFields($order) );
  } catch (Throwable $e) {
    Integrai_Helper::log( $e->getMessage() );
  } catch (Exception $e) {
    Integrai_Helper::log( $e->getMessage() );
  }
}

?>

<script>
    function getJson(json) {
        return JSON.parse(JSON.stringify( json ));
    }

    const integraiSuccessData = getJson( <?php echo sanitizePageOptions( $options ) ?> );
    const order = getJson(<?php echo sanitizeOrder( $order ) ?>);

    window.IntegraiSuccess = Object.assign({}, integraiSuccessData.pageOptions, {
        order: order,
    });

    integraiSuccessData.scripts.forEach(function (script) {
        let scriptElm = document.createElement('script');
        scriptElm.src = script;

        document.body.appendChild(scriptElm);
    });
</script>