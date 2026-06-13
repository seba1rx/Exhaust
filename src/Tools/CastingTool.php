<?php

namespace Exhaust\Tools;

class CastingTool
{
    /**
     * Casts an array to an object
     *
     * @param array $input
     * @param object
     */
    public static function arrayToObject(array $input): object
    {
         return json_decode(json_encode($input));
    }

    /**
     * Casts an object into an array
     *
     * @param array $input
     * @param object
     */
    public static function objectToArray(array|\stdClass $input): array
    {
        return json_decode(json_encode($input), true);
    }

    /**
     * If type of $input is not array then try to cast it to array or wrap it into an assoc array
     *
     * @param mixed $input
     * @param object
     */
    public static function preferAssocArray(mixed $input): array
    {
        if(is_array($input)){
            if(array_is_list($input)){
                /**
                 * if it is an array list, example: ['red', 'blue', green]
                 * then conver to ['red' => 'red', 'blue' => 'blue', 'green' => 'green']
                 */
                $assoc = [];
                foreach($input as $item){
                    $assoc["{$item}"] = $item;
                }
                return $assoc;
            }else{
                ## if it is an assoc array return without changes
                return $input;
            }
        }
        if(is_object($input)){
            ## if it is an object, convert to array
            return json_decode(json_encode($input), true);
        }else{
            /**
             * if it is a var, wrap in an assoc array
             * convert 'foo' into ['foo' => 'foo']
             * convert 123 into ['123' => 123]
             */
            $asString = (string)$input;
            return [$asString => $input];
        }
    }

    /**
     * Casts the value to the identified type (int, double, bool, string)
     *
     * @param mixed $value
     */
    public static function castToDetectedType(mixed $value): mixed
    {
        $str_bools = ["TRUE", "FALSE"];

        if (is_numeric($value)) {
            if(is_int($value)){
                // int
                return (int)$value;
            }
            // float / double
            return (double)$value;
        }else{
            $value = (string)$value;
            if(in_array(strtoupper($value), $str_bools)){
                // bool
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }else{
                // string
                return $value;
            }
        }
    }
}