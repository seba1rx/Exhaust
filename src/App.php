<?php

namespace Exhaust;

use Exhaust\Patterns\Singleton;
use Exhaust\Tools\StringTool;
use Exhaust\Tools\MinifyTool;
use Exhaust\Tools\CastingTool;
use Exhaust\Facades\Templating\SmartyFacade;
use Exhaust\Facades\Templating\TwigFacade;
use Exhaust\Facades\Templating\PistonFacade;
use Exhaust\Facades\Templating\PlatesFacade;
use Exhaust\Facades\Templating\BladeFacade;
use Exhaust\Exceptions\LogicException;
use Exhaust\Routing\Router;
use Exhaust\Request\Request;
use Exhaust\Response\Commands;
use Exhaust\DB\DBConnection\DatabaseHandler;

final class App extends Singleton
{
    /**
     * Identifier for the app instance
     * @var string
     */
    public string $appid;

    /**
     * An object containing all the configuration in /conf.php
     * @var object
     */
    public Object $conf;

    /**
     * The instance of the session manager
     * @var \Exhaust\Session\ExhaustSessionAdmin
     */
    public $sessionManager;

    /**
     * The instance of the template engine to use
     * @var \Exhaust\Contracts\TemplateBlueprint
     */
    public $templateExhaust;

    /**
     * The request instance
     * @var \Exhaust\Request\Request
     */
    public $request;

    /**
     * The payload in the request instance
     * @var \stdClass
     */
    public $payload;

    /**
     * Holds the bool to indicate if the request should destroy the session and respond with a dialog
     * @var bool
     */
    public $terminate_session = false;

    /**
     * Holds the router instance
     * @var \Exhaust\Routing\Router
     */
    public $router;

    /**
     * Holds the commands that will be sent to the frontend to be executed
     * @var object
     */
    private $validatedCommands;

    /**
     * Will hold the database link
     *
     * @var \Exhaust\DB\DBConnection\DatabaseHandler
     */
    public $dbLink;

    /**
     * Initialize values or properties in the singleton instance
     *
     * @return void
     */
    public function init(): void
    {
        ## app id
        $this->appid = StringTool::generateRandomSerial();

        ## sets the Request
        $this->request = new Request();

        ## sets router
        $this->router = new Router(require(__DIR__ . '/../config/routes.php'));

        ## prepare empty commands
        $this->validatedCommands = new \stdClass;

        ## DB link
        $this->dbLink = new DatabaseHandler();

        ## router (requires the routes defined for this app)
        $routes = require $_SERVER["DOCUMENT_ROOT"] . "/../config/routes.php";
        $this->router = new Router(routes: $routes);

        ## template engine
        $this->templateExhaust = $this->getTemplateExhaustInstance();
        $this->templateExhaust->addGlobal(name: 'DEBUG_FRONTEND', value: DEBUG_FRONTEND);

    }

    /**
     * Load the configuration into the App instance
     *
     * @param array $conf
     * @return void
     */
    public function loadconfiguration(array $conf): void
    {
        $this->appid = StringTool::generateRandomSerial();
        if(gettype($conf) == "array"){
            $this->conf = $this->toObject($conf);
            $this->checkconf();
        }
        $this->createConstants();
    }

    /**
     * Converts the given array into object
     *
     * @param array $arg
     * @return object
     */
    private function toObject(array $arg): object
    {
        return json_decode(json_encode($arg));
    }

    /**
     * Check the app conf data
     *
     * @return void
     */
    private function checkconf(): void
    {
        $templateExhaustToUse = $this->conf->template_engine->use;
        $tplConfig = $this->conf->template_engine->configuration;
        if($templateExhaustToUse == "twig"){
            // check the twig parameters:
            // remove the starting slash for convenience to use it concatenating it with a readable slash in in a string in bootstrap.php
            if(str_starts_with($tplConfig->pathToTemplates, "/")){
                $tplConfig->pathToTemplates = ltrim($tplConfig->pathToTemplates, "/");
            }
            if(str_starts_with($tplConfig->pathToCompilation, "/")){
                $tplConfig->pathToCompilation = ltrim($tplConfig->pathToCompilation, "/");
            }
        }elseif($templateExhaustToUse == "piston"){
            // TODO
        }
    }

    /**
     * Creates constants
     *
     * @return void
     */
    private function createConstants(): void
    {
        define( constant_name: 'DEBUG_FRONTEND', value: $this->conf->debug->frontend ?? FALSE);
        define( constant_name: 'DEBUG_BACKEND', value: $this->conf->debug->backend ?? FALSE);
        define( constant_name: 'DEBUG_DATABASE', value: $this->conf->debug->database ?? FALSE);

        foreach($this->conf->constants as $name => $value){
            define($name, $value);
        }
    }

    /**
     * Wrapper arround template engine render method,
     * will minify the html content according to configuration
     *
     * @param string $template - the template to load
     * @param array $context - the vars to pass to the template
     * @return string
     * @throws LogicException
     */
    public function render(string $template, array $context = []): string
    {
        if(is_null($this->templateExhaust)){
            throw new LogicException("No template engine set, check configuration");
        }

        $html = $this->templateExhaust->render(
            name: $template,
            context: $context
        );

        if($this->conf->template_engine->configuration->shouldMinifyOutput){
            return MinifyTool::html($html);
        }

        return $html;
    }

    /**
     * Loads the html "maintainance" courtain
     * @return void
     */
    public function enMantencion(): void
    {
        $request = new Request();
        $this->request = $request;

        $html = $this->render(CURTAINS."/maintainance.html");

        if($this->conf->template_engine->configuration->shouldMinifyOutput){
            $html = MinifyTool::html($html);
        }

        Commands::echo(html: $html);
        $commands = Commands::all();
        Commands::validateCommands($commands);
        $this->validatedCommands = CastingTool::arrayToObject($commands);

        $this->sendResponse();
    }

     /**
     * Loads the html "under construction" courtain
     * @return void
     */
    public function enConstruccion(): void
    {
        $request = new Request();
        $this->request = $request;

        $html = $this->render(CURTAINS."/construction.html");

        if($this->conf->template_engine->configuration->shouldMinifyOutput){
            $html = MinifyTool::html($html);
        }

        Commands::echo(html: $html);
        $commands = Commands::all();
        Commands::validateCommands($commands);
        $this->validatedCommands = CastingTool::arrayToObject($commands);

        $this->sendResponse();
    }

     /**
     * This is the main app function, this method does the following:
     * + handles the request
     * + routes the request to the controller
     * + sends the response back to the client
     * @return void
     */
    public function run(): void
    {
        if(!$this->request->shouldBeIgnored){

            $commands = [];
            if($this->request->isAsync()){

                // routes the action to the controller
                $commands = $this->router->direct();
            }elseif($this->request->isNavigation()){

                // echoes to the front controller
                $main = $this->conf->template_engine->default->main;
                // $main = "/landing_page/landing.html.twig";
                $html = $this->render($main);
                Commands::echo($html);
                $commands = Commands::all();

            }else{
                throw new LogicException("Tipo de request no identificado");
            }

            // validates the commands
            Commands::validateCommands($commands);
            $this->validatedCommands = CastingTool::arrayToObject($commands);

            // delegate the  to the method depending on the type of request
            $this->sendResponse();
        }
    }

    /**
     * Sends back the response to the client
     * @return void
     */
    private function sendResponse(): void
    {
        if($this->request->isNavigation()){
            echo $this->sendHTTPResponse();
        }elseif($this->request->isAsync()){
            echo $this->sendXHRResponse();
        }else{
            throw new LogicException("El sistema no pudo construir una respuesta a la solicitud");
        }
    }

    /**
     * Returns the HTTP response as html string
     * @return string
     * @throws LogicException
     */
    private function sendHTTPResponse(): string
    {
        if(!isset($this->validatedCommands->echo)){
            throw new LogicException("No se ha encontrado una respuesta en el comando echo");
        }
        header('Content-Type: text/html; charset=UTF-8');
        return $this->validatedCommands->echo;
    }

    /**
     * Returns the XHR response as a json string
     * @return string
     * @throws LogicException
     */
    private function sendXHRResponse(): string
    {
        if(empty($this->validatedCommands)){
            throw new LogicException("No hay comandos preparados para ser ejecutados por el front end");
        }

        header('Content-Type: application/json; charset=utf-8');
        return json_encode($this->validatedCommands);
    }

    /**
     * Creates the template engine instance and returns it
     * according to config/config.php template_engine->use
     *
     * @return void
     */
    private function getTemplateExhaustInstance(): \Exhaust\Contracts\TemplateBlueprint
    {
        $engine = $this->conf->template_engine->use;
        $instance = null;
        switch($engine){
            case "twig":
                $instance = new TwigFacade();
                break;
            case "smarty":
                $instance = new SmartyFacade();
                break;
            case "piston":
                $instance = new PistonFacade();
                break;
            case "plates":
                $instance = new PlatesFacade();
                break;
            case "blade":
                $instance = new BladeFacade();
                break;
            default: throw new LogicException("Template engine not identified, could not create the object");
        }
        return $instance;
    }
}