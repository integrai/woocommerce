<?php if (!defined('ABSPATH')) { exit; }?>

<div class="clear"></div>
<div class="integrai_payment">
    <?php if(count($marketplace_data) > 0): ?>
        <h4><?php echo __( 'Dados marketplace', 'integrai' ) ?></h4>
        <p>
            <?php
            foreach ($marketplace_data as $key => $value) {
                if (!empty($value)) {
                    echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br />';
                }
            }
            ?>
        </p>
        <hr />
    <?php endif; ?>

    <?php if(count($payments) > 0): ?>
        <h4><?php echo __( 'Método de Pagamento', 'integrai' ) ?></h4>
        <p>
            <?php foreach ($payments as $index => $payment_data):?>
                <?php
                    if(count($payments) > 1) {
                        echo '<strong>' . ($index + 1) . 'º forma de pagamento</strong>';
                    }
                ?>

                <p>
                    <?php foreach ($payment_data as $key => $value): ?>
                        <?php if(!empty($value) && !is_object($value) && !is_array($value)): ?>
                            <strong><?php echo esc_html($key) ?></strong> <?php echo esc_html($value); ?> <br />
                        <?php endif; ?>

                        <?php if($key == 'boleto' && is_array($value) && !empty($value['url'])): ?>
                            <a
                                id="integrai_printBoleto"
                                target="_blank"
                                title="<?php __( 'Acessar boleto' ) ?>"
                                href="<?php echo esc_url($value['url']) ?>">
                                <?php echo __( ' Imprimir boleto ' ) ?>
                            </a>
                        <?php endif; ?>

                        <?php if($key == 'pix' && is_array($value)): ?>
                            <?php if(!empty($value['qr_code_base64']) || !empty($value['qr_code_image'])): ?>
                            <p>
                                <img
                                    height="150"
                                    style="height: 150px"
                                    src="<?php echo isset($value['qr_code_base64']) ? 'data:image/jpeg;base64,' . $value['qr_code_base64'] : $value['qr_code_image']; ?>"/>
                            </p>
                            <?php endif; ?>
                            <?php if(!empty($value['qr_code'])): ?>
                            <p>
                                <strong><?php echo __( 'Código QR', 'integrai' ) ?></strong> <br/>
                                <input style="width: 80%;padding: .5rem;" readonly value="<?php echo $value['qr_code']; ?>">
                            </p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php
                            if ($key == 'card' && is_array($value)) {
                                foreach ($value as $cardKey => $cardValue) {
                                    if (!empty($cardValue)) {
                                        echo '<strong>' . esc_html($cardKey) . ':</strong> ' . esc_html($cardValue) . '<br />';
                                    }
                                }
                            }
                        ?>
                    <?php endforeach; ?>
                </p>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>
</div>