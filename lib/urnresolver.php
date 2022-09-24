<?php

declare(strict_types=1);

namespace URNResolver;

define("ROOT_PATH", dirname(dirname(__FILE__)));
define("RESOLVER_RULE_PATH", ROOT_PATH . '/resolvers');

// https://www.php-fig.org/psr/psr-12/

function debug()
{
    $info = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $chunks = explode('_', $key);
            $header = '';
            for ($i = 1; $y = sizeof($chunks) - 1, $i < $y; $i++) {
                $header .= ucfirst(strtolower($chunks[$i])).'-';
            }
            $header .= ucfirst(strtolower($chunks[$i])).': '.$value;
            array_push($info, $header);
            // echo $header."\n";
        }
    }
    return $info;
}

class Router
{
    public const XX = "foo";

    private $resolvers = array();

    public function __construct()
    {
        // $this->value = $value;
    }

    private function _rules()
    {
        // $result = [];
        foreach (glob(RESOLVER_RULE_PATH . "/*.urnr.yml") as $filepath) {
            $filename = str_replace(RESOLVER_RULE_PATH, '', $filepath);
            $filename = ltrim($filename, '/');
            $urn_prefix = str_replace('.urnr.yml', '', $filename);
            // array_push($result, [$filepath, $urn_prefix]);
            $this->$resolvers[$urn_prefix] = $filepath;
        }
        // array_push($result, ['RESOLVER_RULE_PATH', RESOLVER_RULE_PATH]);
        return $this->$resolvers;
    }

    public function meta()
    {
        $rule = ltrim($_SERVER['REQUEST_URI'], '/');
        $meta = [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'rule_now' => $rule,
            'rules' => $this->_rules(),
        ];

        return $meta;
    }
}
