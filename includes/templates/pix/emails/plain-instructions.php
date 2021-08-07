<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

esc_html_e( 'Pagamento', 'integrai' );

echo "\n\n";

esc_html_e( 'Utilize o QR Code abaixo para fazer o pagamento via Pix:', 'integrai' );

echo "\n";

echo esc_html_e("<img height=\"150\" style=\"height: 150px\" src=\"data:image/jpeg;base64,<?php echo $qrCodeBase64; ?>\"/>");

echo "\n";

esc_html_e( 'O seu pedido será processado assim que recebermos a confirmação do pagamento do pix.', 'integrai' );

echo "\n\n****************************************************\n\n";
