<?php

require(__DIR__ . '/../vendor/autoload.php');

date_default_timezone_set('America/New_York');

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = false;

$config = array(
  'token' => 'simpletoken',
);

// Register the monolog logging service
if ($app['debug']) {
  $app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
  ));
}


$app->get('/', function(Request $request) use($app, $config) {
  if ($request->headers->get('token') == $config['token']) {
    $file = __DIR__ . '/./testData.json';
    if (!file_exists($file)) {
        return $app->abort(404, 'The image was not found.');
    }

    $stream = function () use ($file) {
        readfile($file);
    };

    return $app->stream($stream, 200, array('Content-Type' => 'application/json'));
  }
  return $app->json(
    array(
      'reason' => 'wrong token',
      'text' => 'Please provide a proper token',
    ),
    403
  );
});

$app->run();
