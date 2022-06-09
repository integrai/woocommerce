<?php

class Integrai_Process_Event {
    private $_models = array();

    public function process($payload) {
        $model = null;

        foreach ($payload->models as $modelItem) {
            $modelName = $modelItem->name;
            $modelRun = (bool) $modelItem->run;

            if ($modelRun) {
                $modelArgs = $this->transform_args($modelItem);
                $modelMethods = $modelItem->methods;
                $model = $modelItem->functionName ? call_user_func_array($modelItem->functionName, $modelArgs) : new $modelItem->className(...$modelArgs);
                $model = $this->run_methods($model, $modelMethods);

                $this->_models[$modelName] = $model;
            }
        }

        return $model;
    }

    private function run_methods($model, $modelMethods) {
        foreach ($modelMethods as $methodValue) {
            $methodName = $methodValue->name;
            $methodRun = (bool)$methodValue->run;
            $keepModel = (bool)$methodValue->keepModel;
            $methodCheckReturnType = isset($methodValue->checkReturnType) ? $methodValue->checkReturnType : null;

            if ($methodRun && $model) {
                $methodArgs = $this->transform_args($methodValue);
                $methodResponse = call_user_func_array(array($model, $methodName), $methodArgs);
                if ($methodCheckReturnType) {
                    $types = (array) $methodCheckReturnType->types;
                    $errorMessage = $methodCheckReturnType->errorMessage;
                    if (!in_array(gettype($methodResponse), $types)) {
                        throw new Exception($errorMessage);
                    }
                }

                if (!next($modelMethods) && !$keepModel) {
                    $model = $methodResponse;
                }
            }
        }

        return $model;
    }

    private function get_other_model($modelName) {
        return $this->_models[$modelName];
    }

    private function transform_args($itemValue) {
        $newArgs = array();

        $args = isset($itemValue->args) ? json_decode(json_encode($itemValue->args), true) : null;

        if (is_array($args)) {
            $argsFormatted = array_values($args);

            foreach($argsFormatted as $arg) {
                if (is_array($arg) && $arg['otherModelName']) {
                    $model = $this->get_other_model($arg['otherModelName']);
                    if (isset($arg['otherModelMethods'])) {
                        array_push($newArgs, $this->run_methods($model, json_decode(json_encode($arg['otherModelMethods']))));
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
}