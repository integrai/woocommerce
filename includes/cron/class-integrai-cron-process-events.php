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
    if ($this->get_config_helper()->is_enabled()) {
      $this->log('Iniciando processamento dos eventos...');

      $limit = $this->get_config_helper()->get_global_config('processEventsLimit', 50);
      $isRunning = $this->get_config_helper()->get_by_name('PROCESS_EVENTS_RUNNING', false);

      if ($isRunning === 'RUNNING') {
        $this->log('Já existe um processo rodando');
      } else {
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

            foreach ($payload->models as $modelItem) {
              $modelName = $modelItem->name;
              $modelRun = (bool) $modelItem->run;

              if ($modelRun) {
                  $modelArgs = $this->transform_args($modelItem);
                  $modelMethods = $modelItem->methods;

                  $model = new $modelItem->className(...$modelArgs);
                  $this->run_methods($model, $modelMethods);

                  $this->_models[$modelName] = $model;
              }
            }

            array_push($success, $eventId);
          } catch (Throwable $e) {
              $this->error_handling($e, $event, $eventId, $errors);
          } catch (Exception $e) {
              $this->error_handling($e, $event, $eventId, $errors);
          }
        }

        // Delete events
        if (count($success) > 0 || count($errors) > 0) {
            $this->get_api_helper()->request('/store/event', 'DELETE', array(
              'eventIds' => $success,
              'errors' => $errors
            ));

            $eventIdsRemove = implode(', ', $eventIds);
            $ProcessEventsModel->delete_query("id in ($eventIdsRemove)");

            $this->log(array(
                'success' => $success,
                'errors' => $errors
            ), 'Eventos processados: ');
        }

        $this->get_config_helper()->update_config('PROCESS_EVENTS_RUNNING', 'NOT_RUNNING');
      }
    }
  }

  private function error_handling($e, $event, $eventId, $errors) {
      $this->log($e->getMessage(), 'Erro');
      $this->log($event, 'Erro ao processar o evento');

      if ($eventId) {
          array_push($errors, array(
              "eventId" => $eventId,
              "error" => $e->getMessage()
          ));
      }
  }

  private function get_other_model($modelName) {
    return $this->_models[$modelName];
  }

  private function transform_args($itemValue) {
    $newArgs = array();

    $args = isset($itemValue->args) ? (array)$itemValue->args : null;

    if (is_array($args)) {
        $argsFormatted = array_values($args);

        foreach($argsFormatted as $arg) {
            if (is_array($arg) && $arg['otherModelName']) {
                $model = $this->get_other_model($arg["otherModelName"]);
                if (isset($arg['otherModelMethods'])) {
                    array_push($newArgs, $this->run_methods($model, $arg['otherModelMethods']));
                } else {
                    array_push($newArgs, $model);
                }
            } else {
                array_push($newArgs, $arg);
            }
        }
    }

    return $newArgs;
  }

  private function run_methods($model, $modelMethods) {
      $result = null;
      foreach ($modelMethods as $methodValue) {
          $methodName = $methodValue->name;
          $methodRun = (bool)$methodValue->run;
          $methodCheckReturnType = isset($methodValue->checkReturnType) ? $methodValue->checkReturnType : null;

          if ($methodRun && $model) {
              $methodArgs = $this->transform_args($methodValue);

              try {
                  $result = call_user_func_array(array($model, $methodName), $methodArgs);
              } catch (Throwable $e) {
                  $this->log($e->getMessage(), 'err');
              } catch (Exception $e) {
                  $this->log($e->getMessage(), 'err');
              }

              if ($methodCheckReturnType) {
                  $types = (array) $methodCheckReturnType->types;
                  $errorMessage = $methodCheckReturnType->errorMessage;
                  if (!in_array(gettype($model), $types)) {
                      throw new Exception($errorMessage);
                  }
              }
          }
      }

      return $result;
  }
}