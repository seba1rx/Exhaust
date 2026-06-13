<?php

namespace Exhaust\Response;

use Exhaust\Exceptions\LogicException;
use Exhaust\Tools\StringTool;
use Exhaust\Tools\CastingTool;
use Exhaust\Tools\MinifyTool;

/**
 * Valida que la respuesta tenga un formato correcto.
 */
class Commands
{

    /**
     * The array that contains the commands the response will respond to the XHR request.
     * @var array
     */
    private static $commands = [];

    /**
     * Listado con los comandos conocidos por el sistema que pueden ser ejecutados por el
     * response handler del lado del cliente (public/assets/js/app.js)
     * @var array
     */
    private static $knownCommands = [

        /**
         * "assignValue" se ocupa para asignar directamente un valor
         * a una variable en javascript, usar de la siguiente manera:
         * var mi_variable_en_js = App.request.post({module: 'Demo', fn: 'assignValue2', payload:{foo: 'value'}})
         */
        "assignValue",

        /**
         * Utilizado para asignar contenido HTML a algun elemento HTML usando el identificador de dicho elemento.
         * Puede contener varias asignaciones para asignar contenido a multiples elementos en una misma respuesta.
         * El orden de asignacion es FIFO
         */
        "html",

        /**
         * "script" es un callback general que debe contener codigo javascript para ser ejecutado
         * una vez la respuesta sea recibida.
         * Ejemplo de uso:
         * Habiendo definido una funcion en javascript myFunction(args)
         * "script" => "myFunction({$php_array_con_datos_obtenidos_desde_la_db})"
         */
        "script",

        /**
         * "console_log" debe contener un string.
         * Se puede pasar datos para ser mostrado por consola.
         * Este comando ejecuta un console.log()
         */
        "console_log",

        /**
         * "log" puede contener los siguientes subcomandos:
         * "info" -> sobrecargará el comando console.log para generar un log con una etiqueta de INFO con color CIAN
         * "error" -> sobrecargará el comando console.log para generar un log con una etiqueta de ERROR con color RED
         * "debug" -> sobrecargará el comando console.log para generar un log con una etiqueta de DEBUG con color GOLD
         * "warning" -> sobrecargará el comando console.log para generar un log con una etiqueta de WARNING con color ORANGE
         *
         * Este comando hace override del parametro de configuracion "debug_request"
         */
        "log",

        /**
         * "dialog" puede contener los siguientes subcomandos:
         * "info" -> producirá un dialogo usando la librería SweetAlert2 con icono INFO
         * "warning" -> producirá un dialogo usando la librería SweetAlert2 con icono WARNING
         * "error" -> producirá un dialogo usando la librería SweetAlert2 con icono ERROR
         * "success" -> producirá un dialogo usando la librería SweetAlert2 con icono SUCCESS
         * "question" -> producirá un dialogo usando la librería SweetAlert2 con icono QUESTION
         *
         * Cada uno de los subcomandos usan los siguientes argumentos:
         * title -> string que aparece en texto grande
         * text -> string con cuerpo del mensaje
         * buttons (opcional) -> se pueden indicar los botones a agregar, sus clases css y el callback al hacer click
         */
        "dialog",

        /**
         * "toast" puede contener los siguientes subcomandos:
         * "info" -> producirá un toast usando la librería SweetAlert2 con icono INFO
         * "warning" -> producirá un toast usando la librería SweetAlert2 con icono WARNING
         * "error" -> producirá un toast usando la librería SweetAlert2 con icono ERROR
         * "success" -> producirá un toast usando la librería SweetAlert2 con icono SUCCESS
         * "question" -> producirá un toast usando la librería SweetAlert2 con icono QUESTION
         * "any" -> producirá un toast usando la librería SweetAlert2 usando configuracion personalizada
         *
         * Cada uno de los subcomandos usan los siguientes argumentos:
         * title -> texto a mostrarse
         * duration (opcional) -> int con duracion en milisegundos, default 3000, para indicar cuanto tiempo debe ser visible el toast
         * position (opcional) -> string para indicar la posicion del toast, [top, top-start, top-end, center, center-start, center-end, bottom, bottom-start, bottom-end]
         */
        "toast",

        /**
         * Used to echo an entire html code.
         * The content of this command is not intended to be handled by engine.js
         * This is mainly used to load the main html code of the single page application
         */
        "echo",

        /**
         * Used to add content that will be echoed as json.
         * The content of this command is not intended to be handled by engine.js
         */
        "api",
        /**
         * Used to add a javascript file to the body of the document.
         * Since commands are executed in FIFO order, put this command
         * BEFORE a script/html command that will use this included file.
         */
        "includeScript",
        /**
         * Used to add a css file to the head of the document.
         * Since commands are executed in FIFO order, put this command
         * BEFORE a script/html command that will use this included file.
         */
        "includeCss",
    ];

    /**
     * Adds to the array of commands a command to assign a value to a variable, this will be executed by the JS instance in the client side.
     *
     * @param mixed $var_name
     * @param mixed $value
     * @return void
     */
    public static function assignValue(string $var_name, mixed $value): void
    {
        if(!isset(self::$commands["assignValue"])) self::$commands["assignValue"] = [];
        self::$commands["assignValue"] = [
            $var_name => $value,
        ];
    }

    /**
     * Agrega al array de comandos un comando de asignacion de html a ser ejecutado por la instancia de js del cliente
     *
     * @param string $node_id - the id of the html element
     * @param string $html_content - the text/html content to be assigned into the element
     * @return void
     */
    public static function html(string $node_id, string $html_content): void
    {
        if(!isset(self::$commands["html"])) self::$commands["html"] = [];
        self::$commands['html'][$node_id] = $html_content;
    }

    /**
     * Agrega al array de comandos un comando de console.log a ser ejecutado por la instancia de js del cliente
     *
     * @param mixed $value - can be any type of data
     * @return void
     */
    public static function console_log(mixed $value): void
    {
        self::$commands["console_log"] = $value;
    }

    /**
     * Agrega al array de comandos un comando de log tipificado a ser ejecutado por la instancia de js del cliente
     *
     * @param string $type - [info, error, debug, warning]
     * @param ?string $text
     * @param array|object|null $details
     * @return void
     */
    public static function log(string $type, string $text = "", array|object|null $details = null): void
    {
        if(!isset(self::$commands["log"])) self::$commands["log"] = [];
        $log = [
            "text" => $text,
        ];
        if(!is_null($details)) $log['details'] = $details;
        self::$commands["log"][$type] = $log;
    }

    /**
     * Agrega al array de comandos un comando de dialogo a ser ejecutado por la instancia de js del cliente
     *
     * @param string $type - [info, error, debug, warning, question]
     * @param array $options
     * @return void
     */
    public static function dialog(string $type, array $options): void
    {
        if(!isset(self::$commands["dialog"])) self::$commands["dialog"] = [];
        self::$commands["dialog"][$type] = $options;
    }

    /**
     * Agrega al array de comandos un comando de script a ser ejecutado por la instancia de js del cliente
     *
     * @param string $script
     * @return void
     */
    public static function script(string $script): void
    {
        self::$commands["script"] = MinifyTool::js($script);
    }

    /**
     * Returns the commands in the response object
     *
     * @return array
     */
    public static function getCommands(): array
    {
        return self::$commands;
    }

    /**
     * Alias of getCommands.
     * Returns the commands in the response object
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$commands;
    }

    /**
     * Adds html content to be echoed to client
     *
     * @param string $html
     * @return void
     */
    public static function echo(string $html): void
    {
        // no need to minify here as app()->render() minifies the content
        self::$commands["echo"] = $html;
    }

    /**
     * Adds content to be sent to client as json.
     *
     * @param array $data
     * @return void
     */
    public static function apiResponse(array $data): void
    {
        self::$commands["api"] = CastingTool::arrayToObject($data);
    }

    /**
     * Adds a javascript file to the body of the document
     *
     * @param string|array $src
     * @return void
     */
    public static function includeScript(string|array $src): void
    {
        if(!isset(self::$commands["src"])) self::$commands["src"] = [];
        $index = StringTool::getStringAfterLast("/", $src);
        if($index === false) $index = $src;
        self::$commands["src"][$index] = $src;
    }

    /**
     * Adds a css file to the head of the document
     * @param string|array $src
     * @return void
     */
    public static function includeCss(string|array $css): void
    {
        if(!isset(self::$commands["css"])) self::$commands["css"] = [];
        $index = StringTool::getStringAfterLast("/", $css);
        if($index === false) $index = $css;
        self::$commands["css"][$index] = $css;
    }

    /**
     * Helper method to build a dialog, it is encouraged to use named parameters in order to improve readability
     *
     * @param string $icon - [success, error, info, warning, question]
     * @param string|null $title
     * @param string|null $text
     * @param string|null $html - this will override swal2 text property if passed as a parameter
     * @param array|null $btn_confirm {text: string, ?class: string, ?callback: text}
     * @param array|null $btn_deny {text: string, ?class: string, ?callback: text}
     * @param array|null $btn_cancel {text: string, ?class: string, ?callback: text}
     * @param array|null $timer {?time: int, ?callback: text}
     * @param string|true|null $showLoading - string or true
     * @return array
     */
    public static function dialogBuilder(
        string $icon,
        string|null $title = null,
        string|null $text = null,
        string|null $html = null,
        array|null $btn_confirm = null,
        array|null $btn_deny = null,
        array|null $btn_cancel = null,
        array|null $timer = null,
        string|true|null $showLoading = null
    ): array
    {
        $dialog = [];
        if(!is_null($icon)) $dialog['icon'] = $icon;
        if(!is_null($title)) $dialog['title'] = $title;
        if(!is_null($text)) $dialog['text'] = $text;
        if(!is_null($html)) $dialog['html'] = MinifyTool::html($html);

        if(!is_null($btn_confirm)){
            $dialog['buttons'] = [];

            // text key, there must be at least a 'text' key
            if(isset($btn_confirm['text'])){
                if(!isset($dialog['buttons']['confirm'])) $dialog['buttons']['confirm'] = [];

                $dialog['buttons']['confirm']['text'] = $btn_confirm['text'];

            }else{
                throw new LogicException("Button confirm needs at least a 'text' key when calling the Response::dialog method");
            }

            // class key, optional
            if(isset($btn_confirm['class'])){
                if(!isset($dialog['buttons']['confirm'])) $dialog['buttons']['confirm'] = [];

                $dialog['buttons']['confirm']['class'] = $btn_confirm['class'];
            }

            // callback key, optional
            if(isset($btn_confirm['callback'])){
                if(!isset($dialog['buttons']['confirm'])) $dialog['buttons']['confirm'] = [];

                $dialog['buttons']['confirm']['callback'] = MinifyTool::js($btn_confirm['callback']);
            }
        }

        if(!is_null($btn_deny)){
            $dialog['buttons'] = [];

            // text key, there must be at least a 'text' key
            if(isset($btn_deny['text'])){
                if(!isset($dialog['buttons']['deny'])) $dialog['buttons']['deny'] = [];

                $dialog['buttons']['deny']['text'] = $btn_deny['text'];

            }else{
                throw new LogicException("Button deny needs at least a 'text' key when calling the Response::dialog method");
            }

            // class key, optional
            if(isset($btn_deny['class'])){
                if(!isset($dialog['buttons']['deny'])) $dialog['buttons']['deny'] = [];

                $dialog['buttons']['deny']['class'] = $btn_deny['class'];
            }

            // callback key, optional
            if(isset($btn_deny['callback'])){
                if(!isset($dialog['buttons']['deny'])) $dialog['buttons']['deny'] = [];

                $dialog['buttons']['deny']['callback'] = MinifyTool::js($btn_deny['callback']);
            }
        }

        if(!is_null($btn_cancel)){
            $dialog['buttons'] = [];

            // text key, there must be at least a 'text' key
            if(isset($btn_cancel['text'])){
                if(!isset($dialog['buttons']['cancel'])) $dialog['buttons']['cancel'] = [];

                $dialog['buttons']['cancel']['text'] = $btn_cancel['text'];

            }else{
                throw new LogicException("Button cancel needs at least a 'text' key when calling the Response::dialog method");
            }

            // class key, optional
            if(isset($btn_cancel['class'])){
                if(!isset($dialog['buttons']['cancel'])) $dialog['buttons']['cancel'] = [];

                $dialog['buttons']['cancel']['class'] = $btn_cancel['class'];
            }

            // callback key, optional
            if(isset($btn_cancel['callback'])){
                if(!isset($dialog['buttons']['cancel'])) $dialog['buttons']['cancel'] = [];

                $dialog['buttons']['cancel']['callback'] = MinifyTool::js($btn_cancel['callback']);
            }
        }

        if(!is_null($timer)){
            $dialog['timer'] = [];

            if(empty($timer))
                throw new LogicException("Timer needs at least one key to set it up when calling the Response::dialog method");

            if(isset($timer['time']))
                $dialog['timer']['time'] = $timer['time'];

            if(isset($timer['callback']))
                $dialog['timer']['callback'] = MinifyTool::js($timer['callback']);
        }

        if(!is_null($showLoading)){
            $loading = $showLoading;
            if(empty($showLoading) || !(is_string($showLoading) || is_bool($showLoading))){
                $loading = true;
            }
            $dialog['showLoading'] = $loading;
        }

        return $dialog;
    }

    /**
     * Validates each command has the required properties to prevent the
     * front-end controller does not throw an exception.
     * Throws exception if the response has a command that will produce an error
     *
     * @param array $responseData
     * @return void
     * @throws \Exception
     */
    public static function validateCommands(array $responseData): array
    {
        foreach($responseData as $commandName => $commandContent){
            if(in_array($commandName, self::$knownCommands)){

                $commandName = strtolower($commandName);

                $cannot_be_empty = ["assignValue", "script", "console_log", "echo"];
                if(in_array($commandName, $cannot_be_empty)){
                    if(empty($commandContent)) throw new \Exception("Comando '{$commandName}' no tiene contenido");
                }
                if("html" == $commandName){
                    if(!is_array($commandContent)) throw new \Exception("Comando '{$commandName}' no contiene un array y debe contener un array asociativo");
                    if(empty($commandContent)) throw new \Exception("Comando '{$commandName}' tiene un array sin elementos y debe contener al menos un elemento ['node_id' => 'html content']");

                    foreach($commandContent as $node_id => $html){
                        if(empty($html)) throw new \Exception("Comando '{$commandName}' contiene un elemento que no indica contenido html");
                    }
                }
                if("log" == $commandName){
                    if(!is_array($commandContent)) throw new \Exception("Comando '{$commandName}' no contiene un array y debe contener un array asociativo");
                    if(empty($commandContent)) throw new \Exception("Comando '{$commandName}' tiene un array sin elementos y debe contener al menos un elemento. Ejemplo['warning' => 'mensaje']");

                    $allowed_log_types = ["info", "error", "debug", "warning"];
                    foreach($commandContent as $log_type => $log_items){
                        if(!in_array($log_type, $allowed_log_types)) throw new \Exception("Comando '{$commandName}' no reconoce tipo de log '{$log_type}'");
                        if(!isset($log_items['text']) && !isset($log_items['details'])) throw new \Exception("Comando log de tipo {$log_type} debe contener item 'text' o bien 'details'");
                    }
                }
                if("dialog" == $commandName){
                    if(!is_array($commandContent)) throw new \Exception("Comando '{$commandName}' no contiene un array y debe contener un array asociativo");
                    if(empty($commandContent)) throw new \Exception("Comando '{$commandName}' tiene un array sin elementos y debe contener al menos un elemento. Ejemplo['info' => [...]]");

                    $allowed_dialog_types = ["info", "warning", "error", "success", "question", "any"];
                    foreach($commandContent as $dialog_type => $dialog_conf){
                        if(!in_array($dialog_type, $allowed_dialog_types)) throw new \Exception("Comando '{$commandName}' no reconoce tipo de dialogo '{$dialog_type}'");
                        if(empty($dialog_conf)) throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' no tiene configuración para el diálogo");

                        foreach($dialog_conf as $conf_item => $conf){
                            switch($conf_item){
                                case "title":
                                case "html":
                                case "text":
                                    if(empty(trim($conf))) throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' debe indicar un texto");
                                    break;
                                case "buttons":
                                    foreach($conf as $type => $btn_properties){
                                        switch($type){
                                            case "confirm":
                                            case "deny":
                                            case "cancel":
                                                if(empty($btn_properties))
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' tipo '{$type}' no indica propiedades");
                                                if(!isset($btn_properties['text']))
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' tipo '{$type}' debe contener items 'text'");
                                                if(isset($btn_properties['callback']) && empty($btn_properties['callback']))
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' tipo '{$type}' contiene itme 'callback' pero no contiene text");
                                                if(isset($btn_properties['class']) && empty($btn_properties['class']))
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' tipo '{$type}' item 'class' debe indicar un texto");
                                                break;
                                            default:
                                                throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' permite tipos 'confirm', 'cancel' y 'deny' y se ha indicado tipo '{$type}'");
                                        }
                                    }
                                    break;
                                case "timer":
                                    foreach($conf as $timer_item => $timer_conf){
                                        switch($timer_item){
                                            case "time":
                                                if(!is_int($timer_conf) || $timer_conf < 0 || $timer_conf % 1000 !== 0)
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' debe indicar un entero positivo en unidades de miles");
                                                break;
                                            case "callback":
                                                if(empty($timer_properties))
                                                    throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' key '{$timer_item}' está vacío");
                                                break;
                                            default:
                                                throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' en item '{$conf_item}' key '{$timer_item}' no es reconocido como propiedad de 'buttons'");
                                        }
                                    }
                                    break;
                                default: throw new \Exception("Comando '{$commandName}' de tipo '{$dialog_type}' no reconoce item '{$conf_item}'");
                            }
                        }
                    }
                }
                if("toast" == $commandName){
                    if(!is_array($commandContent)) throw new \Exception("Comando '{$commandName}' no contiene un array y debe contener un array asociativo");
                    if(empty($commandContent)) throw new \Exception("Comando '{$commandName}' tiene un array sin elementos y debe contener al menos un elemento. Ejemplo['info' => [...]]");

                    $allowed_toast_types = ["info", "warning", "error", "success", "question", "any"];
                    foreach($commandContent as $toast_type => $toast_conf){
                        if(!in_array($toast_type, $allowed_toast_types)) throw new \Exception("Comando '{$commandName}' no reconoce tipo de toast '{$toast_type}'");
                        if(empty($toast_conf)) throw new \Exception("Comando '{$commandName}' de tipo '{$toast_type}' no tiene configuración para el toast");

                        foreach($toast_conf as $conf_item => $conf){
                            switch($conf_item){
                                case "title":
                                    if(empty(trim($conf))) throw new \Exception("Comando '{$commandName}' de tipo '{$toast_type}' en item '{$conf_item}' debe indicar un texto");
                                    break;
                                case "duration":
                                    if(!is_int($conf) || $conf < 0 || $conf % 1000 !== 0)
                                        throw new \Exception("Comando '{$commandName}' de tipo '{$toast_type}' en item '{$conf_item}' debe indicar un entero positivo en unidades de miles");
                                    break;
                                case "position":
                                    $allowed_positions = ['top', 'top-start', 'top-end', 'center', 'center-start', 'center-end', 'bottom', 'bottom-start', 'bottom-end'];
                                    if(!in_array($conf, $allowed_positions))
                                        throw new \Exception("Comando '{$commandName}' de tipo '{$toast_type}' en item '{$conf_item}' indica una posicion desconocida");
                                    break;
                                default: throw new \Exception("Comando '{$commandName}' de tipo '{$toast_type}' no reconoce item '{$conf_item}'");
                            }
                        }
                    }
                }

            }else{
                throw new \LogicException("Comando '{$commandName}' no reconocido");
            }
        }

        return $responseData;
    }
}