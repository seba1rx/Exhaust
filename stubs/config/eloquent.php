<?php

/**
 * ################################
 * Setting DB connection with illuminate
 */

use Illuminate\Database\Capsule\Manager as Capsule;

$selected = conf()->DB->use;

$capsule = new Capsule;
$capsule->addConnection([
   "driver" => conf()->DB->environment->{$selected}->driver,
   "host" => conf()->DB->environment->{$selected}->host,
   "database" => conf()->DB->environment->{$selected}->database,
   "username" => conf()->DB->environment->{$selected}->user,
   "password" => conf()->DB->environment->{$selected}->password,
   "charset" => conf()->DB->environment->{$selected}->charset,
   "collation" => conf()->DB->environment->{$selected}->collation,
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();