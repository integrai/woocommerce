<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

esc_html_e( 'Pagamento', 'integrai' );

echo "\n\n";

esc_html_e( 'Utilize o link abaixo para visualizar o boleto:', 'integrai' );

echo "\n";

echo esc_url( $url );

echo "\n";

esc_html_e( 'O seu pedido será processado assim que recebermos a confirmação do pagamento do boleto.', 'integrai' );

echo "\n\n****************************************************\n\n";
