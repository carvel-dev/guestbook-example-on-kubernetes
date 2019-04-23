<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Predis/Autoloader.php';

Predis\Autoloader::register();

$bg = getenv('GUESTBOOK_BG');

if (isset($_GET['cmd']) === true) {
  $host = 'redis-master';
  header('Content-Type: application/json');

  if ($_GET['cmd'] == 'set') {
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => getenv('GUESTBOOK_REDIS_PORT'),
    ]);

    $client->set($_GET['key'], $_GET['value']);
    print('{"message": "Updated"}');
  } else {
    $host = 'redis-slave';
    $client = new Predis\Client([
      'scheme' => 'tcp',
      'host'   => $host,
      'port'   => getenv('GUESTBOOK_REDIS_PORT'),
    ]);

    $value = $client->get($_GET['key']);
    print('{"data": "' . $value . '", "bg": "' . $bg . '"}');
  }
} else {
  phpinfo();
}

?>
