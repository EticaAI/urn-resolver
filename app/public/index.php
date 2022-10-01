<?php


  require '../lib/urnresolver.php';

  try {
    $app = new URNResolver\App();
    $app->execute_web();
  }
  catch (\Throwable $e) {
  // catch (Error $e) {
    $data = [
      'error' => [
        'status' => 500,
        'title' => 'Internal Server error',
        '_context' => [
          $e->getMessage(),
          $e->getFile(),
          $e->getLine()
        ]
      ]
    ];
    http_response_code(500);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($data);
    die;
  }

  /*
  $config = new URNResolver\Config();
  $router = new URNResolver\Router($config);

  try {
    // @TODO make the router itself decide when is home
    if ($router->is_success()){
      $router->execute();
    } else {
      $router->execute_welcome();
    }
    // $router->execute();
  }
  // catch (\Throwable $t) {
  catch (Error $e) {
    // echo "caught!\n";
    $data = [
      'error' => [
        'status' => 500,
        'title' => 'Internal Server error',
        '_context' => [
          $e->getMessage(),
          $e->getFile(),
          $e->getLine()
        ]
      ]
    ];
    // echo $t->getMessage(), " at ", $t->getFile(), ":", $t->getLine(), "\n";
    http_response_code(500);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($data);
    die;
  }
  */


