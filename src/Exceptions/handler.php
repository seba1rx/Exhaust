<?php

/**
 * This is the global function that handles all exception caught or uncaught in a try catch code block.
 *
 * By using Throwable it is possible to handle exceptions and error in the same way
 *
 * @param Throwable $t
 */
function globalExceptionHandler(Throwable $t): void
{
    try {
        $conf = app()->conf;
    } catch (\Throwable) {
        http_response_code(500);
        error_log($t->getFile() . '# ' . $t->getLine() . ' ' . $t->getMessage());
        $isXhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        header($isXhr ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: text/html; charset=UTF-8');
        echo $isXhr ? json_encode(['error' => 'Internal Server Error']) : '<h1>500 Internal Server Error</h1>';
        return;
    }
    http_response_code(response_code: 500);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // XHR request

        $response = [];
        $throwable = [
            'msg' => $t->getMessage(),
            'file' => $t->getFile(),
            'line' => $t->getLine(),
        ];
        $trace = $t->getTrace();

        if($conf->exception->show_trace){
            $throwable["trace"] = $trace;
        }

        $msg = "An error occured during the execution of the application";

        $response = [
            "log" => [
                "error" => $msg,
            ],
            "dialog" => [
                "error" => [
                    "title" => $msg,
                    "text" => "Contact the system manager or try again",
                ],
            ],
        ];

        if($conf->debug->backend){
            $response['log']['debug'] = $throwable;
        }

        if($conf->exception->show_detail){
            $response['dialog']['error']['text'] = $t->getMessage();
        }

        error_log($t->getFile() ."# ". $t->getLine() . " " .  $t->getMessage());
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
    }else{
        // HTTP request

        error_log($t->getFile() ."# ". $t->getLine() . " " .  $t->getMessage());
        $trace = $t->getTrace();
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

        header('Content-Type: text/html; charset=UTF-8');
        require __DIR__."/../../resources/html_courtains/500.html";
    }
}