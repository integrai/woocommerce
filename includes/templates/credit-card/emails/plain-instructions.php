<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

esc_html_e( 'Pagamento', 'integrai' );

echo "\n\n";

printf(
  esc_html__( 'Pagamento com cartão de crédito %1$s em %2$s realizado com sucesso.', 'integrai' ),
  esc_html( $card_brand ),
  intval( $installments ) . 'x'
);

echo "\n\n****************************************************\n\n";
