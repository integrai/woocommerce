<?php

class Integrai_i18n {
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'integrai',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}