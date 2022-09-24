<?php


  $result = (object) [
    'message' => '@TODO',
    'status' => 200,
    '_debug' => [
        '_REQUEST' => $_REQUEST,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        '_kv' => []
    ],
  ];


  foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $chunks = explode('_', $key);
        $header = '';
        for ($i = 1; $y = sizeof($chunks) - 1, $i < $y; $i++) {
            $header .= ucfirst(strtolower($chunks[$i])).'-';
        }
        $header .= ucfirst(strtolower($chunks[$i])).': '.$value;
        array_push($result->_debug['_kv'], $header);
        // echo $header."\n";
    }
}

  // $result = {'message': 'todo'}


  header("Content-type: application/json; charset=utf-8");

  echo json_encode($result, JSON_PRETTY_PRINT);
