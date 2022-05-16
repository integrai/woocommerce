<?php

class Integrai_Cron_Process_Events {
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
      $timeout = $this->get_config_helper()->get_global_config('processEventsTimeoutHours', 1);
      $isRunning = $this->get_config_helper()->get_by_name('PROCESS_EVENTS_RUNNING', false);
      $lastRunning = $this->get_config_helper()->get_by_name('LAST_PROCESS_EVENTS_RUN', false);
      $now = date('Y-m-d H:i:s');
      $dateDiff = date_diff(date_create($now), date_create($lastRunning));
      $interval = $dateDiff->format('%h');

      if ($isRunning === 'RUNNING' && $lastRunning && $interval < $timeout) {
        $this->log('Já existe um processo rodando');
      } else {
        $this->get_config_helper()->update_config('PROCESS_EVENTS_RUNNING', 'RUNNING');
        $this->get_config_helper()->update_config('LAST_PROCESS_EVENTS_RUN', $now);

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

            $processEvent = new Integrai_Process_Event();
            $processEvent->process($payload);

            array_push($success, $eventId);
          } catch (Throwable $e) {
              array_push($errors, $this->error_handling($e, $event, $eventId));
          } catch (Exception $e) {
              array_push($errors, $this->error_handling($e, $event, $eventId));
          }
        }

        // Delete events
        if (count($success) > 0 || count($errors) > 0) {
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

  private function error_handling($e, $event, $eventId) {
      $this->log($e->getMessage(), 'Erro');
      $this->log($event, 'Erro ao processar o evento: ');

      if ($eventId) {
          return array(
              "eventId" => $eventId,
              "error" => $e->getMessage()
          );
      }
  }
}