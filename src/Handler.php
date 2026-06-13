<?php

namespace Exhaust;

use Exhaust\Logging\Logger;
use Exhaust\Routing\Router;
use Exhaust\Request\Request;
use Exhaust\Response\Response;
use Exhaust\Tools\CastingTool;

final class Handler
{

    /**
     * checks if app has required properties to be returned
     *
     * @return void
     */
    public static function prepareResponse(): void
    {
        if(!isset(app()->response)){
            Commands::addcmd_dialog("error", [
                "title" => "An error occurred",
                "text" => "The system could not create a response",
            ]);

            app()->response = CastingTool::arrayToObject(Commands::getCommands());

            return;
        }

        if(app()->terminate_session){
            self::terminate();
        }

        ## detection of error code 403
        if(http_response_code() == 403){
            Commands::apiResponse([
                'msg' => 'No tiene permitido el acceso al recurso',
            ]);
        }
    }

    /**
     * Sends the response to the client, it can be html content or a json.
     * This is the last method the backend executes.
     *
     * @return void
     */
    public static function sendResponse(): void
    {
        $commands = Commands::getCommands();

        if(isset($commands['echo'])){
            // header('Content-Type: text/html; charset=iso-8859-1');
            header('Content-Type: text/html; charset=UTF-8');
            echo $commands['echo'];

        }else{

            echo json_encode($commands);
        }
    }

    /**
     * clear session vars if they exist
     *
     * @return void
     */
    private static function terminate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $dialog = Commands::dialog(
            icon: 'info',
            title: 'Session terminated',
            text: 'your session has finished',
            btn_confirm: [
                'text' => 'Ok',
                'callback' => "Exhaust.route('/login')",
            ],
        );
        Commands::addcmd_dialog('info', $dialog);
    }

    /**
     * Gets the request payload and routes the request to its controller
     *
     * @param Request $request  $request = new Lablnet\Request();
     * @return void
     */
    // public static function handleRequest(App &$app): void
    public static function handleRequest(): void
    {
        ## creates the request instance
        app()->request = new Request();

        ## validates remote address is allowed
        self::validateRemoteAddress();

        ## if app uses jwt token then tries to validate it
        self::validateJwtToken();

        ## calls the controller, the controller should return an array of commands
        $commands = app()->router->direct();

        ## validate response, if not ok will thow an exception
        Commands::validateCommands($commands);

        ## set response if not already set
        if(!isset(app()->response)) app()->response = CastingTool::arrayToObject($commands);
    }

    /**
     * If app uses jwt token then tries to validate it
     *
     * @return void
     */
    private static function validateJwtToken(): void
    {
        if(app()->conf->jwt->uses_token){
            ## gets the token if it exists (null if bearer authorization header not found)
            app()->conf->jwt->token = self::getBearerToken();
        }
    }

    /**
     * get access token from header in request
     * https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
     *
     * if header has another type of authorization it will not use it since we are looking for 'Bearer'
     *
     * @return string|null
     */
    private static function getBearerToken(): string|null
    {
        $headers = self::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get header Authorization
     * https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
     *
     * This method is calld from private method getBearerToken
     *
     * @return string|null
     * */
    private static function getAuthorizationHeader(): string|null
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * validates if the remote request address is in the allowed remotes (see /config.php)
     *
     * @throws \Exception
     *
     * @return void
     */
    private static function validateRemoteAddress(): void
    {
        if(app()->conf->access->validate_remote_request_address){
            $remoteIp = app()->request->getRemoteAddress();
            if(!in_array($remoteIp, (array)app()->conf->access->allowed_remote_request_addresses)){
                throw new \Exception('El origen de su solicitud no tiene permitido el acceso a la app ('. $remoteIp .')');
            }
        }
    }
}