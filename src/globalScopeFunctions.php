<?php

/**
 * Wrapper for error_log function
 *
 * @param mixed $arg - [numeric, string, array, object]
 * @return void
 */
function clog(mixed $arg): void
{
    if(is_array($arg) || is_object($arg)){
        error_log(json_encode($arg));
    }else{
        error_log($arg);
    }
}