<?php

declare(strict_types=1);

namespace Exhaust\DB\DBConnection;

use Exhaust\Exceptions\LogicException;
use Exhaust\Logging\Logger;
use PDO;
use PDOException;
use stdClass;
use Exhaust\Tools\CastingTool;

final class DataBaseHandler{

    private $should_log_queries = false;
    private $add_debug = false;
    private $statement;
    private $settings;
    private $debugContents;
    private $connectionName;
    private $respuesta = [
        'is_ok' => true,
        'data' => null,
    ];

    public $link;

    /**
     * Create new link
     *
     * @param object|null &$link
     * @param string|null $environment (if null use default environment)
     * @throws LogicException
     */
    public function __construct(object|null &$link = null, string|null $environment = null)
    {
        try{

            $this->settings = new stdClass;

            $environment_name = app()->conf->DB->use;
            $configuredEnvironments = array_keys(CastingTool::objectToArray(app()->conf->DB->environment));

            if(is_null($environment)){

                ## Use the default configured DB environment set in /config.php DB->use
                if(!in_array(needle: $environment_name, haystack: $configuredEnvironments)){
                    throw new LogicException('The system could not identify the database environment');
                }
            }

            ## gets the database configuration in order to create the link
            $environment_conf = app()->conf->DB->environment->{$environment_name};
            $this->connectionName = $environment_name;

            $this->settings->driver = $environment_conf->driver;
            $this->settings->port = $environment_conf->port;
            $this->settings->host = $environment_conf->host;
            $this->settings->user = $environment_conf->user;
            $this->settings->password = $environment_conf->password;
            $this->settings->database = $environment_conf->database;

            $dsn = $this->settings->driver. ':host=' . $this->settings->host . ';dbname=' . $this->settings->database . ";charset=utf8mb4" . ';port=' . $this->settings->port;

            if(is_null($link)){
                try{
                    $options = [
                        PDO:: ATTR_ERRMODE                  => PDO:: ERRMODE_EXCEPTION,
                        PDO:: ATTR_DEFAULT_FETCH_MODE       => PDO:: FETCH_ASSOC,
                        PDO:: ATTR_EMULATE_PREPARES         => false,
                    ];

                    $this->link = new PDO($dsn, $this->settings->user, $this->settings->password, $options);

                } catch(PDOException $e) {
                    error_log("PDO E " . $e->getMessage());
                    throw new PDOException($e->getMessage(), (int)$e->getcode());
                }catch(\Exception $e){
                    error_log("E " .$e->getMessage());
                }
            }else{
                $this->link =& $link;
            }

            $this->should_log_queries = app()->conf->debug->database;

        }catch(PDOException $e){
            $this->respuesta['is_ok'] = false;
            $this->respuesta['msg'] = $e->getMessage();
        }
    }

    /**
     * Executes a raw query, can accept parameters
     *
     * @param string $query (example: select * from table where col1 = ?)
     * @param string|null $queryType (['select_one', 'select_scalar', 'select_many', 'update', 'insert', 'delete'])
     * @param array|null $args (example: ['foo'])
     * @return array
     */
    public function rawQuery(string $query, string|null $queryType = 'select_one', array|null $args = []): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        $this->statement = $this->link->prepare($query);
        $this->bindParamsUsingQuestionMarks($args);

        $execution = $this->statement->execute();
        if ($execution) {

            switch(strtolower($queryType)){
                case 'select_one':
                    $this->respuesta['data'] = $this->statement->fetch(PDO::FETCH_ASSOC);
                    break;

                case 'select_scalar':
                    $this->respuesta['data'] = $this->statement->fetchColumn();
                    break;

                case 'select_many':
                    $this->respuesta['data'] = $this->statement->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'update':
                    $this->respuesta['data'] = $this->statement->rowCount();
                    break;

                case 'insert':
                    if($this->statement->rowCount() > 0){
                        // $this->respuesta['data'] = $this->statement->rowCount();
                        $this->respuesta['data'] = $this->link->lastInsertId();
                    }else{
                        $this->respuesta['data'] = null;
                    }
                    break;

                case 'delete':
                    $this->respuesta['data'] = $this->statement->rowCount();
                    break;

                default:
                    throw new PDOException('No se pudo identificar el tipo de consulta a utilizar');
            }

            return $this->getResponse();

        } else {
            throw new PDOException('fn rawQuery: No se pudo ejecutar la consulta');
        }
    }

    /**
     * Gets a single row with results
     *
     * @param string $query (example: select * from table where col1 = :param1 and col2 > :param2)
     * @param array|null $args (example: [':param1' => 'foo', ':param2' => 123])
     * @return array
     */
    public function selectOne(string $query, array|null $args): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        if(!$this->validateQueryParams($query, false, $args)){
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($args);

        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data'] = $this->statement->fetch(PDO::FETCH_ASSOC);
            return $this->getResponse();

        }else{
            throw new PDOException('fn selectOne: No se pudo obtener los datos');
        }

    }

    /**
     * Same as selectOne, but it expects the parameter to be integer. Param id must be present in query as ":id"
     *
     * @param string $query
     * @param int|null $id
     * @return array
     */
    public function selectById(string $query, int $id): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $id];

        if (!$this->validateQueryParams($query, false, $id)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($id);
        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data'] = $this->statement->fetch(PDO::FETCH_ASSOC);
            return $this->getResponse();

        }else{
            throw new PDOException('fn selectById: No se pudo obtener los datos');
        }
    }

    /**
     * Gets a multidimensional array, each row is a result
     *
     * @param string $query
     * @param array|null $args
     * @return array
     */
    public function selectMany(string $query, array|null $args): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        if (!$this->validateQueryParams($query, false, $args)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($args);

        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data'] = $this->statement->fetchAll();
            return $this->getResponse();

        }else{
            throw new PDOException('fn selectMany: No se pudo obtener los datos');
        }

    }

    /**
     * Inserts one row at a time: insert into tableName (col1,col2) values (a,b);
     *
     * @param string $query
     * @param array $args
     * @return array
     * @throws PDOException
     */
    public function insertOne(string $query, array $args): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        if (!$this->validateQueryParams($query, false, $args)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($args);

        try{

            $this->statement->execute();

        }catch(PDOException $e){

            $this->logQueryToFile();
            throw new PDOException('PDOException: ' . $e->getMessage());
        }

        $lastInsertId = $this->link->lastInsertId();
        $rowCount = $this->statement->rowCount();

        if($lastInsertId === false){
            throw new PDOException('fn insertOne: No se pudo obtener el identificador del último registro insertado');
        }

        if ($rowCount == 0) {
            throw new PDOException('fn insertOne: No se ha insertado ningún registro en l base de datos');
        }

        $this->respuesta['data']['insertId'] = $this->link->lastInsertId();
        $this->respuesta['data']['rowCount'] = $this->statement->rowCount();

        return $this->getResponse();

    }

    /**
     * Inserts many rows using one query: insert into tableName (col1,col2) values (a,b), (c,d), (e,f);
     *
     * @param string $query
     * @param array $args
     * @param array|null $formatoColumnas (las columnas de cada row)
     * @return array
     */
    public function insertManyUsingQuestionMarks(string $query, array $args, array|null $formatoColumnas = []): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        $args_ordenados = $this->ordenarDatosSegunFormato($args, $formatoColumnas);

        $final_query = $this->prepareMultiRowInsertQueryUsingQuestionMarks($query, count($args_ordenados), count($formatoColumnas));

        if (!$this->validateQueryParams($final_query, true, $args_ordenados)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($final_query);

        // $this->bindMultiRowParamsByName($args_ordenados);
        $this->bindMultiRowParamsUsingQuestionMarks($args_ordenados);

        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data']['insertId'] = $this->link->lastInsertId();
            return $this->getResponse();

        } else {
            throw new PDOException('fn insertManyUsingQuestionMarks: No se pudo dejar registro en la base de datos del request');
        }
    }

    /**
     * Executes the UPDATE query
     *
     * @param string $query
     * @param array $args
     * @return array
     */
    public function update(string $query, array $args): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        if (!$this->validateQueryParams($query, false, $args)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($args);

        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data']['affectedRows'] = (int)$this->statement->rowCount();

            return $this->getResponse();
        } else {
            throw new PDOException('fn update: No se pudo modificar los datos');
        }

    }

    /**
     * Executes the DELETE query
     *
     * @param string $query
     * @param array $args
     * @return array
     */
    public function delete(string $query, array $args): array
    {
        $this->checkIfConnectionIsUp();

        $this->debugContents = ['query' => $query, 'args' => $args];

        if (!$this->validateQueryParams($query, false, $args)) {
            $this->respuesta['is_ok'] = false;
            return $this->getResponse();
        }

        $this->statement = $this->link->prepare($query);
        $this->bindParamsByName($args);

        $execution = $this->statement->execute();
        if ($execution) {

            $this->respuesta['data']['deletedRows'] = $this->statement->rowCount();

            return $this->getResponse();
        } else {
            throw new PDOException('fn delete: No se pudo eliminar los datos');
        }

    }

    /**
     * Returns the PDO instance by ref
     *
     * @return PDO
     */
    public function &getLink(): PDO
    {
        return $this->link;
    }

    public function beginTransaction(): bool
    {
        return $this->link->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->link->commit();
    }

    public function rollback(): bool
    {
        return $this->link->rollBack();
    }

    /**
     * Valida que $params sea array o int, si es array debe ser 'associative array' y
     * cada key debe estar presente en la query, a menos que use question marks,
     * en dicho caso usa otra validacion
     *
     * @param string $query
     * @param bool $uses_question_marks
     * @param array|int $params
     * @return bool
     */
    private function validateQueryParams(string $query, bool $uses_question_marks, array|int|null $params): bool
    {
        if($params == null){
            return true;
        }

        if(is_array($params)){

            if(array_is_list($params)){
                if(!isset($params[0])){
                    $this->respuesta['msg'] = 'Parámetros no puede ser una lista, debe ser un array asociativo';
                    return false;
                }
            }

            if($uses_question_marks){
                ## si query es con question marks el count resulta ser cero, por lo que se debe validar de otra forma

            }else{
                $params_in_query_string = substr_count($query, ':');
                $number_of_params = count($params);

                if($params_in_query_string != $number_of_params){

                    $this->respuesta['msg'] = 'Cantidad de parámetros en la query no coincide con cantidad de parámetros asociados';
                    return false;
                }
            }


        }else{

            ## debe ser entero positivo
            if(is_int($params)){
                ## no puede ser cero o menor, solo usa esta validacion si es id
                if(!$params >= 1){

                    $this->respuesta['msg'] = 'Id debe ser entero mayor a 0';
                    return false;
                }
            }else{
                $this->respuesta['msg'] = 'No se ha podido procesar los parámetros de la consulta';
                return false;
            }
        }

        return true;
    }

    /**
     * binds the parameters to the query by name
     *
     * @param array|int $params
     */
    private function bindParamsByName(array|int $params): void
    {
        $binded = [];

        if(is_array($params)){

            foreach($params as $paramName => $paramValue){

                if(!str_starts_with($paramName, ':')){
                    $paramName = ":{$paramName}";
                }

                if(is_numeric($paramValue) && is_int($paramValue)){
                    ## example: $paramValue = 123
                    $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_INT);
                    $binded[$paramName] = ['PARAM_INT', $paramValue];
                    continue;

                }elseif(is_numeric($paramValue) && is_float($paramValue)){
                    ## example: $paramValue = 123.12
                    $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_STR);
                    $binded[$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                }elseif(is_numeric($paramValue) && is_string($paramValue)){
                    ## example: $paramValue = '123' -> integer numeric string
                    ## example: $paramValue = '123.12' -> float numeric string

                    ## ctype_digit should only be used with string to evaluate int string vs float string
                    if(ctype_digit($paramValue)){
                        ## is integer
                        $this->statement->bindValue($paramName, (int)$paramValue, PDO::PARAM_INT);
                        $binded[$paramName] = ['PARAM_INT', $paramValue];
                        continue;

                    }else{
                        ## is float
                        $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_STR);
                        $binded[$paramName] = ['PARAM_STR', $paramValue];
                        continue;
                    }

                }elseif(is_bool($paramValue)){

                    $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_BOOL);
                    $binded[$paramName] = ['PARAM_BOOL', $paramValue];
                    continue;

                }elseif(is_null($paramValue)){

                    $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_NULL);
                    $binded[$paramName] = ['PARAM_NULL', $paramValue];
                    continue;

                } elseif (is_string($paramValue)) {

                    $this->statement->bindValue($paramName, $paramValue, PDO::PARAM_STR);
                    $binded[$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                }elseif(is_array($paramValue)){

                    $this->statement->bindValue($paramName, json_encode($paramValue), PDO::PARAM_STR);
                    $binded[$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                }else{

                    $this->statement->bindValue($paramName, (string)$paramValue, PDO::PARAM_STR);
                    $binded[$paramName] = ['PARAM_STR', $paramValue];
                    continue;
                }
            }

        }else{

            $this->statement->bindValue(':id', $params, PDO::PARAM_INT);
            $binded[] = ['PARAM_INT', $params];
        }

        $xxx = null; // used to place a breakpoint for xdebug to look what's in $binded
    }

    /**
     * adds the 'values' part of the query to the insert query
     * $query = "insert into tableName (a,b,c) values"
     * return = "insert into tableName (a,b,c) values (?,?,?), (?,?,?), ..."
     *
     * @param string $query
     * @param int $numberOfRows
     * @param int $numberOfCols
     * @return string
     */
    private function prepareMultiRowInsertQueryUsingQuestionMarks(string $query, int $numberOfRows, int $numberOfCols): string
    {
        $singleRowUsingQuestionMarks = '(' . implode(',', array_fill(0, $numberOfCols, '?')) . ')';

        $a = array_fill(0, $numberOfRows, $singleRowUsingQuestionMarks);
        $b = implode(',', $a);

        $values = $b;

        return $query . $values;
    }


    /**
     * binds the parameters to the query using question marks, used only with raw query
     *
     * @param array $params
     */
    private function bindParamsUsingQuestionMarks(array $params): void
    {
        $x = 1;
        $binded = [];
        foreach ($params as $paramName => $paramValue) {

            if (is_numeric($paramValue) && is_int($paramValue)){
                ## example: $paramValue = 123
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_INT);
                $x++;
                $binded[$paramName] = ['PARAM_INT', $paramValue];
                continue;

            }elseif(is_numeric($paramValue) && is_float($paramValue)){
                ## example: $paramValue = 123.12
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                $x++;
                $binded[$paramName] = ['PARAM_STR', $paramValue];
                continue;

            }elseif(is_numeric($paramValue) && is_string($paramValue)){
                ## example: $paramValue = '123' -> integer numeric string
                ## example: $paramValue = '123.12' -> float numeric string

                ## ctype_digit should only be used with string to evaluate int string vs float string
                if(ctype_digit($paramValue)){
                    ## is integer
                    $this->statement->bindValue($x, (int)$paramValue, PDO::PARAM_INT);
                    $x++;
                    $binded[$paramName] = ['PARAM_INT', $paramValue];
                    continue;

                }else{
                    ## is float
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                    $x++;
                    $binded[$paramName] = ['PARAM_STR', $paramValue];
                    continue;
                }
            }elseif (is_bool($paramValue)) {
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_BOOL);
                $x++;
                $binded[$paramName] = ['PARAM_BOOL', $paramValue];
                continue;

            } elseif (is_null($paramValue)) {
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_NULL);
                $x++;
                $binded[$paramName] = ['PARAM_NULL', $paramValue];
                continue;

            } elseif (is_string($paramValue)) {
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                $x++;
                $binded[$paramName] = ['PARAM_STR', $paramValue];
                continue;

            } elseif (is_array($paramValue)) {
                $this->statement->bindValue($x, json_encode($paramValue), PDO::PARAM_STR);
                $x++;
                $binded[$paramName] = ['PARAM_STR', $paramValue];
                continue;

            } else {
                $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                $x++;
                $binded[$paramName] = ['PARAM_STR', $paramValue];
                continue;

            }
        }
        $xxx = null; // used to place a breakpoint for xdebug to look what's in $binded
    }

    /**
     * binds the parameters to the query using question marks, used only to insert many
     *
     * @param array $params (multiple rows only)
     */
    private function bindMultiRowParamsUsingQuestionMarks(array $params): void
    {
        foreach($params as $item){
            if(!is_array($item)){
                throw new LogicException('Vincular parámetros para múltiples filas requiere un array asociativo');
            }
        }

        // if (array_is_list($params)) {
        //     if (!isset($params[0])) {
        //         throw new IqampException('Vincular parÃ¡metros para mÃºltiples filas requiere un array asociativo');
        //     }
        // }

        $x = 1;
        $binded = [];
        foreach ($params as $i => $row) {
            foreach ($row as $paramName => $paramValue) {

                if(is_numeric($paramValue) && is_int($paramValue)){
                    ## example: $paramValue = 123
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_INT);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_INT', $paramValue];
                    continue;

                }elseif(is_numeric($paramValue) && is_float($paramValue)){
                    ## example: $paramValue = 123.12
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                }elseif(is_numeric($paramValue) && is_string($paramValue)){
                    ## example: $paramValue = '123' -> integer numeric string
                    ## example: $paramValue = '123.12' -> float numeric string

                    ## ctype_digit should only be used with string to evaluate int string vs float string
                    if(ctype_digit($paramValue)){
                        ## is integer
                        $this->statement->bindValue($x, (int)$paramValue, PDO::PARAM_INT);
                        $x++;
                        $binded[$i][$paramName] = ['PARAM_INT', $paramValue];
                        continue;

                    }else{
                        ## is float
                        $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                        $x++;
                        $binded[$i][$paramName] = ['PARAM_STR', $paramValue];
                        continue;
                    }

                } elseif (is_bool($paramValue)) {
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_BOOL);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_BOOL', $paramValue];
                    continue;

                } elseif (is_null($paramValue)) {
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_NULL);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_NULL', $paramValue];
                    continue;

                } elseif (is_string($paramValue)) {
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                } elseif (is_array($paramValue)) {
                    $this->statement->bindValue($x, json_encode($paramValue), PDO::PARAM_STR);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                } else {
                    $this->statement->bindValue($x, $paramValue, PDO::PARAM_STR);
                    $x++;
                    $binded[$i][$paramName] = ['PARAM_STR', $paramValue];
                    continue;

                }
            }
        }

        $xxx = null; // used to place a breakpoint for xdebug to look what's in $binded
    }

    /**
     * adds the query log to the response of the public methods
     */
    private function addQueryLogIfEnabled(): void
    {
        // if($this->add_debug){
        //     $this->respuesta['debug'] = [
        //         'query' => $this->debugContents['query'],
        //         'params' => $this->debugContents['args'],
        //     ];
        // }
    }

    /**
     * Logs the query and its bounded params to log file if app()->conf->should_log_queries is true
     *
     */
    private function logQueryToFile(): void
    {

        if ($this->should_log_queries) {
            $description  = 'Driver: ' . $this->settings->driver . ' -- ';
            $description .= 'Host: ' . $this->settings->host . ' -- ';
            $description .= 'User: ' . $this->settings->user . ' -- ';
            $description .= 'Database: ' . $this->settings->database;

            // Logger::debug($this, " # ".__LINE__ ." logQueryToFile " . $this->debugContents['query']);

            // $logger = new QueryLogger($this->app);
            // $logger->logToFile([
            //         'query' => $this->debugContents['query'],
            //         'params' => $this->debugContents['args'],
            //     ],
            //     $description
            // );
        }
    }

    /**
     * calls the query logger and the debug method before returning the final response
     *
     * @return array
     */
    private function getResponse(): array
    {
        $this->addQueryLogIfEnabled();
        $this->logQueryToFile();

        return $this->respuesta;
    }

    /**
     * checks if the link object is null or not, if it is null then the database instance was not available
     */
    private function checkIfConnectionIsUp(): void
    {
        if(is_null($this->link)){
            throw new LogicException('La base de datos no está disponible');
        }
    }

    /**
     * Ordena los datos en args segun el formatoColumnas para que cada dato vaya en su lugar
     * correcto al momento de hacer el binding de los parametros
     *
     * @param array $args (solo array asociativo para query de multiples lineas)
     * @param array $formatoColumnas
     * @return array
     */
    private function ordenarDatosSegunFormato(array $args, array $formatoColumnas): array
    {

        $args_ordenados = [];
        foreach($args as $index_fila => $datos_fila){

            $args_ordenados[$index_fila] = array_replace(array_flip($formatoColumnas), $datos_fila);
        }

        return $args_ordenados;
    }
}