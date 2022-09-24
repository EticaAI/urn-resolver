<?php


  require '../lib/urnresolver.php';

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

//   $result->_debug['_kv'] = URNResolver\debug();
  $router = new URNResolver\Router();
  $result->_debug['_router'] =  $router->meta();

//   header("Content-type: application/json; charset=utf-8");
//   echo json_encode($result, JSON_PRETTY_PRINT);
//   die();

  if ($router->is_success()){
    $router->execute();
  } else {
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

