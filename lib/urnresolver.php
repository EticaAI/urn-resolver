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
    private $resolvers = array();
    private $active_uri;
    private $active_urn = false;
    private $active_rule_prefix = false;
    private $active_rule_conf = false;

    public function __construct()
    {
        $this->active_uri = ltrim($_SERVER['REQUEST_URI'], '/');
        if (str_starts_with($this->active_uri, 'urn:')) {
            $this->active_urn = $this->active_uri;
        }
        // $this->resolvers = [];
        $this->_init_rules();
    }

    private function _init_rules()
    {
        $prefixes = [];
        foreach (glob(RESOLVER_RULE_PATH . "/*.urnr.yml") as $filepath) {
            $filename = str_replace(RESOLVER_RULE_PATH, '', $filepath);
            $filename = ltrim($filename, '/');
            $urn_prefix = str_replace('.urnr.yml', '', $filename) . ':';
            $this->resolvers[$urn_prefix] = $filepath;
            array_push($prefixes, $urn_prefix);
        }

        usort($prefixes, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($prefixes as $key => $value) {
            if (str_starts_with($this->active_uri, $value)) {
                $this->active_rule_prefix = $value;
                // $this->active_rule_conf = \yaml_parse_file($this->resolvers[$urn_prefix]);
                break;
            }
        }

        return $this->resolvers;
    }

    // private function _active_rule()
    // {
    //     $rule = ltrim($_SERVER['REQUEST_URI'], '/');
    //     foreach (glob(RESOLVER_RULE_PATH . "/*.urnr.yml") as $filepath) {
    //         $filename = str_replace(RESOLVER_RULE_PATH, '', $filepath);
    //         $filename = ltrim($filename, '/');
    //         $urn_prefix = str_replace('.urnr.yml', '', $filename);
    //         $this->$resolvers[$urn_prefix] = $filepath;
    //     }
    //     return $this->$resolvers;
    // }

    public function meta()
    {
        // $rule = ltrim($_SERVER['REQUEST_URI'], '/');
        $meta = [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'active_rule_prefix' => $this->active_rule_prefix,
            'active_rule_conf' => $this->active_rule_conf,
            'active_urn' => $this->active_urn,
            'rules' => $this->_init_rules(),
            '_all' => var_export($this, true),
        ];

        return $meta;
    }
}
