#!/usr/bin/php
<?php

require __DIR__.'/../vendor/autoload.php';

use robot\Bot;
use robot\tools\Debug;

ignore_user_abort(true);
set_time_limit(-1);

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/../');
$dotenv->load();


ini_set('log_errors', 1);
ini_set('error_log', getenv('LOG_FILE'));


if (!isset($argv[1])) {
    exit("Parâmetro da fila não informado.");
}

$queueId = $argv[1];

try {
    $bot = new Bot($queueId);
    $bot->do();
} catch(\Exception $e) {
    Debug::error("Falha ao executar o robo:" . $e->getMessage());
}
