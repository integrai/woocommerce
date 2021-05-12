<?php

class Integrai_Cron_Process_Events {
  private $_models = array();

  private function log($data, $message = '') {
    Integrai_Helper::log($data, $message);
  }

  private function get_api_helper() {
    return new Integrai_API();
  }

  private function get_config_helper() {
    return new Integrai_Model_Config();
  }

  public function execute() {
    Integrai_Helper::log($this->get_config_helper()->is_enabled(), '==> Integrai_Cron_Process_Events :: isEnabled?');
    if ($this->get_config_helper()->is_enabled()) {
      $this->log('Iniciando processamento dos eventos...');

      $limit = $this->get_config_helper()->get_global_config('processEventsLimit') || 50;
      $isRunning = $this->get_config_helper()->get_by_name('PROCESS_EVENTS_RUNNING') || 'NOT_RUNNING';

      if ($isRunning === 'RUNNING') {
        $this->log('Já existe um processo rodando');
      } else {
        global $woocommerce;

        $this->get_config_helper()->update_config('PROCESS_EVENTS_RUNNING', 'RUNNING');

        $ProcessEventsModel = new Integrai_Model_Process_Events();
        $events = $ProcessEventsModel->get("LIMIT 0, $limit", true);

        $this->log(count($events), 'Total de eventos a processar: ');

        $success = [];
        $errors = [];
        $eventIds = [];

        foreach ($events as $event) {
          $eventIds[] = $event->id;

          $eventId = $event->event_id;
          $payload = json_decode($event->payload);

          try {
            if (!isset($payload) || !isset($payload->models)) {
              throw new Exception('Evento sem payload');
            }

            array_push($success, $eventId);

            foreach ($payload->models as $modelItem) {
              $modelName = $modelItem->name;
              $modelRun = (bool) $modelItem->run;

              if ($modelRun) {
                $this->log($modelItem->className, '$modelItem->className: ');
                $this->log($modelItem->modelArgs, '$modelItem->modelArgs: ');
                $this->log($this->transform_args($modelItem->modelArgs), '$this->transform_args($modelItem->modelArgs): ');

                $model = new $modelItem->className(...$this->transform_args($modelItem->modelArgs));
                $this->log($model, '$model: ');
                $methods = $modelItem->methods;
                $this->log($methods, '$methods: ');

                if ( isset($methods->method) && isset($methods->args) ) {
                  call_user_func_array(array($model, $methods->method), $this->transform_args($methods->args));
                }

                $this->_models[$modelName] = $model;
              }
            }

            array_push($success, $eventId);
            $this->log($success, '$success: ');

          } catch (Exception $e) {
            $this->log($event, 'Erro ao processar o evento');
            $this->log($e->getMessage(), 'Erro');

            if ($eventId) {
              array_push($errors, array(
                "eventId" => $eventId,
                "error" => $e->getMessage()
              ));
            }
          }

          // Delete events
//          if (count($success) > 0 || count($errors) > 0) {
//            $this->get_api_helper()->send_event('DELETE', array(
//              'eventIds' => $success,
//              'errors' => $errors
//            ));
//
//            $eventIdsRemove = implode(', ', $eventIds);
//            $ProcessEventsModel->delete("id in ($eventIdsRemove)");
//
//            $this->log('Eventos processados: ', array(
//              'success' => $success,
//              'errors' => $errors
//            ));
//          }

          $this->get_config_helper()->update_config('PROCESS_EVENTS_RUNNING', 'NOT_RUNNING');
        }
      }
    }
  }

  private function get_other_model($modelName) {
    return $this->_models[$modelName];
  }

  private function transform_args($args = array()) {
    $newArgs = array();

    foreach($args as $arg) {
      if(is_array($arg) && $arg["otherModelName"]){
        array_push($newArgs, $this->get_other_model($arg["otherModelName"]));
      } else {
        array_push($newArgs, $arg);
      }
    }

    return $newArgs;
  }
}