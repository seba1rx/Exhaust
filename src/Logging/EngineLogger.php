<?php

namespace Exhaust\Logging;

use Psr\Log\AbstractLogger;
use Exhaust\Tools\StringTool;

/**
 * This class is used in the Logger.php class to delegate the log work to the defined log methods here
 *
 * @package Thruway
 */
class ExhaustLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message  it can be a json string, in order to add details
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = []) : void
    {
        $config = require(__DIR__ . "/../../config/config.php");

        if($config['logging']['is_active']){
            ################## TODO: move this code to bootstrap or app wrapper class, it should not be necessary to run this code each time log fn is called
            ## finds out if the namespace is enabled to log
            $classes = [];
            $is_enabled_to_log = false;
            foreach($config['logging']['classes'] as $ns_class => $is_enabled){
                // only the enabled classes will be added to the $classes array
                if($is_enabled){
                    $ns_classes = [];
                    $is_class = class_exists($ns_class); // see if it is a class
                    if($is_class){
                        $classes[] = $ns_class;
                    }
                }
            }
            ##################

            $msg_is_json = json_validate($message);
            $msg_obj = null;
            if($msg_is_json){
                $msg_obj = json_decode($message);
                // if(in_array($msg['class'], $classes)) $is_enabled_to_log = true;
                if(in_array($msg_obj->class, $classes)) $is_enabled_to_log = true;
            }

            if($is_enabled_to_log){

                $output = "";
                if($config['logging']['details']){
                    $now = date("Y-m-d\TH:i:s");
                    $microtime = substr((string)microtime(), 1, 8);
                    $log_level = str_pad($level, 10, " ");
                    $output .= $now . "." . $microtime . " ";
                    $output .= strtoupper($log_level) . " ";
                }

                $output .= $msg_is_json ? $msg_obj->message : $message;

                if($config['logging']['details'] && !empty($context)){
                    $output .= " | Context: " . json_encode($context);
                }

                error_log($output);
            }
        }
    }
}