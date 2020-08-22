<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Integrai_Model_Config extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_shipping');
  }

  public function quote() {
    /***
     * 1. Verificar se a Integrai está ativa
     * 2. Verificar se os metodos de entrega estão ativos
     * 3. Pegar os dados do produto e o CEP para fazer a cotação
     * 4. Preparar os parametros para enviar para a API /shipping/quote
     * 5. Transformar o retorno da API para exibir no resultado da cotação
     * 6. Tratar erro
    */


  }
}