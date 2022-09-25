<?php


  require '../lib/urnresolver.php';
  $router = new URNResolver\Router();

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

  if ($router->is_success()){
    $router->execute();
  } else {
    $router->execute_welcome();
    header("Content-type: application/json; charset=utf-8");
    // @see https://web.dev/stale-while-revalidate/
    // header('Cache-Control: public, max-age=3600, s-maxage=120, stale-while-revalidate=600, stale-if-error=3600');
    // header('Cache-Control: public, max-age=3600, s-maxage=30, stale-while-revalidate=3600, stale-if-error=3600');
    header('Cache-Control: public, max-age=3600, s-maxage=600, stale-while-revalidate=600, stale-if-error=600');
    // header('Vary: Accept-Encoding');
    // http_response_code(500);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

