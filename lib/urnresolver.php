<?php

declare(strict_types=1);

namespace URNResolver;

define("ROOT_PATH", dirname(dirname(__FILE__)));
define("RESOLVER_RULE_PATH", ROOT_PATH . '/public/.well-known/urn');

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
    private $active_urn_to_uri = false;
    private $active_urn_to_httpstatus = 302;
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
        $urns_pattern_list = [];
        foreach (glob(RESOLVER_RULE_PATH . "/*.urnr.json") as $filepath) {
            $filename = str_replace(RESOLVER_RULE_PATH, '', $filepath);
            $filename = ltrim($filename, '/');
            // $urn_prefix = str_replace('.urnr.yml', '', $filename) . ':';
            $urn_pattern = str_replace('.urnr.json', '', $filename);
            $this->resolvers[$urn_pattern] = $filepath;
            array_push($urns_pattern_list, $urn_pattern);
        }

        usort($urns_pattern_list, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($urns_pattern_list as $key => $urn_pattern) {
            $full_pattern = '/' . $urn_pattern . '/i';
            $matches = NULL;
            // if (str_starts_with($this->active_uri, $value)) {
            if (preg_match($full_pattern, $this->active_uri, $matches)) {
                $this->active_rule_prefix = $urn_pattern;
                $json = file_get_contents($this->resolvers[$urn_pattern]);
                // Decode the JSON file
                $json_data = json_decode($json, false);
                $this->active_rule_conf = [$json_data, $matches, $urn_pattern];
                // $this->_rule_calc($this->active_uri, $urn_pattern, $json_data, );
                break;
            }
        }

        return $this->resolvers;
    }

    private function _rule_calc($active_uri, $in_urn_rule, $active_rule)
    {
        // $new = preg_replace($in_urn_rule, $in_urn, $active_uri);
        // $this->active_rule_conf = [$active_rule, $in_urn, $urn_pattern];
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
