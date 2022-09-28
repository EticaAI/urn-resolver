<?php


  require '../lib/urnresolver.php';
  $config = new URNResolver\Config();
  $router = new URNResolver\Router($config);

  $result = (object) [
    'message' => '@TODO',
    'status' => 200,
    '_debug' => [
        '_REQUEST' => $_REQUEST,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        '_kv' => [],
        '_router' => []
    ],
  ];

  $result->_debug['_kv'] = URNResolver\debug();
  $result->_debug['_router'] =  $router->meta();

//   header("Content-type: application/json; charset=utf-8");
//   echo json_encode($result, JSON_PRETTY_PRINT);
//   http_response_code(500);
//   die();

  // @TODO implement some more advanced try/catch here to force return
  //       json even if something not planned happens.
  try {
    if ($router->is_success()){
      $router->execute();
    } else {
      $router->execute_welcome();
      header("Content-type: application/json; charset=utf-8");
      header('Cache-Control: public, max-age=3600, s-maxage=600, stale-while-revalidate=600, stale-if-error=600');
      // header("Access-Control-Allow-Origin: *");
      echo json_encode($result, JSON_PRETTY_PRINT);
    }
    die;
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
      // header('Content-Type: application/json; charset=utf-8');
      header('Content-Type: application/vnd.api+json; charset=utf-8');
    echo json_encode( $data, JSON_PRETTY_PRINT);
    die;
  }


