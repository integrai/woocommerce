<?php

class Integrai_Payment_Method  {
  public function admin_order_meta( $orderId ) {
    $marketplace_data = array();
    $payments_data = array();
    $marketplace = array_filter((array) get_post_meta( $orderId, 'marketplace', true ));
    $payments = array_filter((array) get_post_meta( $orderId, 'payments', true ));

    if (isset($marketplace) && count($marketplace) > 0) {
      $name = !empty($marketplace['name']) ? sanitize_text_field($marketplace['name']) : '';
      $order_id = !empty($marketplace['orderId']) ? sanitize_text_field($marketplace['orderId']) : '';
      $created_at = !empty($marketplace['createdAt']) ? date_format(date_create($marketplace['createdAt']), 'd/m/Y H:i:s') : '';
      $updated_at = !empty($marketplace['updatedAt']) ? date_format(date_create($marketplace['updatedAt']), 'd/m/Y H:i:s') : '';

      $marketplace_data = array(
          __('Criado por', 'integrai' ) => $name,
          __('Nº Pedido Marketplace', 'integrai' ) => $order_id,
          __('Data criação do pedido no marketplace', 'integrai' ) => $created_at,
          __('Data atualização do pedido no marketplace', 'integrai' ) => $updated_at
      );
    }

    if (isset($payments) && count($payments) > 0) {
      foreach ($payments as $payment) {
        $method = !empty($payment['method']) ? sanitize_text_field($payment['method']) : '';
        $module_name = !empty($payment['moduleName']) ? sanitize_text_field($payment['moduleName']) : '';
        $value = !empty($payment['value']) ? 'R$' . number_format($payment['value'],2,",",".") : '';
        $transaction_id = !empty($payment['transactionId']) ? sanitize_text_field($payment['transactionId']) : '';
        $date_approved = !empty($payment['dateApproved']) ? date_format(date_create($payment['dateApproved']), 'd/m/Y H:i:s') : '';
        $installments = !empty($payment['installments']) ? sanitize_text_field($payment['installments']) . 'x' : '';
        $boleto = !empty($payment['boleto']) ? (array) $payment['boleto']: '';
        $card = !empty($payment['card']) ? (array) $payment['card']: '';
        $pix = !empty($payment['pix']) ? (array) $payment['pix']: '';

        $card_data = '';
        if (isset($card) && is_array($card)) {
          $card_number = !empty($card['lastFourDigits']) ? $card['lastFourDigits'] : '';
          $card_brand = !empty($card['brand']) ? $card['brand'] : '';
          $card_holder = !empty($card['holder']) ? $card['holder'] : '';
          $expiration_month = !empty($card['expirationMonth']) ? $card['expirationMonth'] : '';
          $expiration_year = !empty($card['expirationYear']) ? $card['expirationYear'] : '';
          $expiration = implode('/', array_filter(array($expiration_month, $expiration_year)));

          $card_data = array(
              __( 'Número do cartão', 'integrai' ) => sanitize_text_field( "**** **** **** $card_number" ),
              __( 'Nome do titular', 'integrai' ) => sanitize_text_field( $card_holder ),
              __( 'Expiração', 'integrai' ) => $expiration,
              __( 'Bandeira', 'integrai' ) => sanitize_text_field( strtoupper( $card_brand ) ),
          );
        }

        $payments_data[] = array(
            __('Método', 'integrai') => $method,
            __('Processado por', 'integrai' ) => $module_name,
            __('Identificação da transação', 'integrai' ) => $transaction_id,
            __('Data de pagamento', 'integrai' ) => $date_approved,
            __('Nº de Parcelas', 'integrai' ) => $installments,
            __('Valor cobrado', 'integrai' ) => $value,
            'boleto' => $boleto,
            'card' => $card_data,
            'pix' => $pix,
        );
      }
    }

    return array(
        'marketplace_data' => $marketplace_data,
        'payments' => $payments_data,
    );
  }
}
?>
