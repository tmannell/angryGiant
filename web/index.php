<?php

require_once('../vendor/autoload.php');

$f3 = Base::instance();

$f3->config('config.ini');
$f3->config('routes.ini');

$db = new \DB\SQL($f3->get('sqliteDB'));
new \DB\SQL\Session($db, 'sessions', true);
Controller::checkAuthorization();

$f3->run();