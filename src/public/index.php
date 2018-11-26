<?php

use Firxworx\SlimFormHandler\App;

require __DIR__ . '/../../vendor/autoload.php';

$config = file_get_contents(__DIR__ . '/../config.json');
$config = json_decode($config);

$app = (new App($config))->get();
$app->run();
