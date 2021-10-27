<?php

class Integrai_Payment_Method  {
  public function admin_order_meta( $orderId ) {
    $marketplace_data = array();
    $payments_data = array();
    $marketplace = (array) get_post_meta( $orderId, 'marketplace', true );
    $payments = (array) get_post_meta( $orderId, 'payments', true );

    if (isset($marketplace)) {
      $name = isset($marketplace['name']) ? sanitize_text_field($marketplace['name']) : '';
      $order_id = isset($marketplace['order_id']) ? sanitize_text_field($marketplace['order_id']) : '';
      $created_at = isset($marketplace['created_at']) ? date_format(date_create($marketplace['created_at']), 'd/m/Y H:i:s') : '';
      $updated_at = isset($marketplace['updated_at']) ? date_format(date_create($marketplace['updated_at']), 'd/m/Y H:i:s') : '';

      $marketplace_data = array(
          __('Criado por', 'integrai' ) => $name,
          __('Nº Pedido Marketplace', 'integrai' ) => $order_id,
          __('Data criação do pedido no marketplace', 'integrai' ) => $created_at,
          __('Data atualização do pedido no marketplace', 'integrai' ) => $updated_at
      );
    }

    if (isset($payments) && count($payments) > 0) {
      foreach ($payments as $payment) {
        $method = isset($payment['method']) ? sanitize_text_field($payment['method']) : '';
        $module_name = isset($payment['module_name']) ? sanitize_text_field($payment['module_name']) : '';
        $value = isset($payment['value']) ? 'R$' . number_format($payment['value'],2,",",".") : '';
        $transaction_id = isset($payment['transaction_id']) ? sanitize_text_field($payment['transaction_id']) : '';
        $date_approved = isset($payment['date_approved']) ? date_format(date_create($payment['date_approved']), 'd/m/Y H:i:s') : '';
        $installments = isset($payment['installments']) ? sanitize_text_field($payment['installments']) . 'x' : '';
        $boleto = isset($payment['boleto']) ? (array) $payment['boleto']: '';
        $card = isset($payment['card']) ? (array) $payment['card']: '';
        $pix = isset($payment['pix']) ? (array) $payment['pix']: '';

        $card_data = '';
        if (isset($card) && is_array($card)) {
          $card_number = isset($card['last_four_digits']) ? $card['last_four_digits'] : '';
          $card_brand = isset($card['brand']) ? $card['brand'] : '';
          $card_holder = isset($card['holder']) ? $card['holder'] : '';
          $expiration_month = isset($card['expiration_month']) ? $card['expiration_month'] : '';
          $expiration_year = isset($card['expiration_year']) ? $card['expiration_year'] : '';
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
