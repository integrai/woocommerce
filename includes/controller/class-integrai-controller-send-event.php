<?php

class Integrai_Send_Event_Controller extends WP_REST_Controller {

    protected $namespace = 'integrai';
    protected $path = 'event/send';

    private function get_helper() {
        return new Integrai_Payment_Method_Helper( $this->id );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->path, [
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'get_items' ),
                'permission_callback' => '__return_true'
            ),
        ]);
    }

    public function get_items( $request ) {
        try {
            $data = json_decode($request->get_body());
            $payload = $data->payload;

            Integrai_Helper::log($data->event, 'Executando evento: ');

            $processEvent = new Integrai_Process_Event();
            $response = $processEvent->process($payload);

            $response = new WP_REST_Response($response);
            $response->header( 'Content-type', 'application/json' );
            $response->set_status( 200 );

            return $response;
        } catch (Exception $e) {
            Integrai_Helper::log($e->getMessage(), 'Error ao enviar evento');

            $response = new WP_REST_Response( array(
                "ok" => false,
                "error" => $e->getMessage()
            ) );
            $response->header( 'Content-type', 'application/json' );
            $response->set_status( 500 );

            return $response;
        }
    }
}