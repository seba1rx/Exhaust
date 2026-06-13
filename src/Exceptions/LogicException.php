<?php

namespace Exhaust\Exceptions;

use \Exception;

final class LogicException extends Exception
{
    /**
     * Handles exceptions thrown in logic
     *
     * @param \Exhaust\Exceptions\LogicException $e
     * @param object $conf
     * @return void
     */
    public function __invoke(LogicException $e, object $conf): void
    {
        http_response_code(response_code: 500);

        $response = [];
        $exception = [
            'msg' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        $trace = $e->getTrace();
        if($conf->exception->show_trace){
            $exception['trace'] = $trace;
        }

        $msg = "An error occured during the execution of the application";

        $response['log'] = [
            "error" => $msg,
        ];
        $response['dialog'] = [
            "error" => [
                "title" => $msg,
                "text" => "Contact the system manager or try again",
            ],
        ];

        if($conf->debug->backend){
            $response['log']['debug'] = $exception;
        }

        if($conf->exception->show_detail){
            $response['dialog']['error']['text'] = $e->getMessage();
        }

        error_log($e->getFile() ." # ". $e->getLine() . " " .  $e->getMessage());

        $trace = $e->getTrace();
        if($conf->exception->show_trace){
            foreach($trace as $key => $frame){
                $trace_item = "";
                if(isset($frame['file'])) $trace_item .= $frame['file'] . " ";
                if(isset($frame['line'])) $trace_item .= $frame['line'] . " ";
                if(isset($frame['function'])) $trace_item .= $frame['function'] . " ";
                if(isset($frame['class'])) $trace_item .= $frame['class'] . " ";
                if(isset($frame['type'])) $trace_item .= $frame['type'] . " ";
                error_log($trace_item);
            }
            error_log("####################");
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_INVALID_UTF8_IGNORE, JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
