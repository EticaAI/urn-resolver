<?php

declare(strict_types=1);

namespace URNResolver;

date_default_timezone_set('UTC');
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

class Config
{
    public $global_conf;
    public string $base_iri;
    public array $resolver_status_pages;

    public function __construct()
    {
        $source_config = ROOT_PATH . '/urnresolver.dist.conf.json';
        $conf = json_decode(file_get_contents($source_config), true);

        if (php_sapi_name() == 'cli-server') {
            // @TODO potential confs if running local devel test server
        } elseif (\file_exists(ROOT_PATH . '/urnresolver.conf.json')) {
            $source_config2 = ROOT_PATH . '/urnresolver.conf.json';
            $conf2 = json_decode(file_get_contents($source_config2), true);
            $conf = array_replace_recursive($conf, $conf2);
        }
        // $json = file_get_contents($source_config);
        // die($json);

        // var_dump($conf);
        // die($conf);

        $this->global_conf = $conf;
        $this->base_iri = $conf['base_iri'] ?? null;
        $this->resolver_status_pages = $conf['resolver_status_pages'] ?? null;
        // $this->aaa = $conf->aaa ?? null;
    }

    public function transform_if_necessary(string $variable)
    {
        if (strpos('{{ ', $variable) === -1) {
            return $variable;
        }

        $variable = str_replace('{{ urnresolver }}', $this->global_conf['base_iri'], $variable);

        // @TODO implement dot notation
        // $all_options = [];

        return $variable;
    }
}

class Response
{
    private $global_conf;
    private string $_cc_prefix = 'public';
    private int $max_age = 0;
    private int $s_maxage = 0;
    private int $stale_while_revalidate = 0;
    private int $stale_if_error = 0;

    private array $_opts = [
        // '_cc_mode' => '_cc_mode', // special case, pre initialize defaults
        '_cc_prefix' => '_cc_prefix',
        'max_age' => 'max-age',
        's_maxage' => 'max-age',
        's_maxage' => 's-maxage',
        'stale_while_revalidate' => 'stale-while-revalidate',
        'stale_if_error' => 'stale-if-error',
    ];

    public function __construct(Config $config, string $mode = 'default')
    {
        $this->global_conf = $config->global_conf;
        $cc_active = $this->global_conf['Cache-Control'][$mode];
        $this->_set_options($cc_active);
    }

    private function _set_options($options)
    {
        // RECURSIVE WARNING: _cc_mode MUST NOT be used on global configuration
        //                    this block allow initialize defaults
        if (isset($options['_cc_mode'])) {
            $mode = $options['_cc_mode'];
            $this->_set_options($this->global_conf['Cache-Control'][$mode]);
        }
        foreach ($this->_opts as $key => $value) {
            if (isset($options[$value])) {
                $this->{$key} = $options[$value];
            }
        }
    }

    public function set_active_urnr($urnr_group, $urnr_specific = null)
    {
    }

    public function execute_output_2xx(
        string $base,
        array $data,
        int $http_status_code = 200
    ) {
        http_response_code($http_status_code);
        header("Cache-Control: {$this->_cc_prefix}, max-age={$this->max_age}, s-maxage={$this->s_maxage}, stale-while-revalidate={$this->stale_while_revalidate}, stale-if-error={$this->stale_if_error}");
        // header('Content-Type: application/json; charset=utf-8');
        header('Content-Type: application/vnd.api+json; charset=utf-8');
        // header("Access-Control-Allow-Origin: *");

        $result = [
            '$schema' => 'https://jsonapi.org/schema',
            '$id' => $base,
            '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            'data' => $data,
            'meta' => [
                '@type' => 'schema:Message',
                'schema:name' => 'URN Resolver',
                'schema:dateCreated' => date("c"),
                // // 'schema:mainEntityOfPage' => 'https://github.com/EticaAI/urn-resolver',
                // "schema:potentialAction" => [
                //     "schema:name" => "uptime",
                //     "schema:url" => "https://stats.uptimerobot.com/jYDZlFY8jq"
                // ]
            ]
          ];

        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        die();
    }

    public function execute_output_4xx(
        string $base,
        // string $data,
        int $http_status_code = 404,
        string $http_status_msg = 'Not found'
    ) {
        $this->_set_options($this->global_conf['Cache-Control']['default404']);

        http_response_code($http_status_code);
        header("Cache-Control: {$this->_cc_prefix}, max-age={$this->max_age}, s-maxage={$this->s_maxage}, stale-while-revalidate={$this->stale_while_revalidate}, stale-if-error={$this->stale_if_error}");
        // header('Content-Type: application/json; charset=utf-8');
        header('Content-Type: application/vnd.api+json; charset=utf-8');
        // header("Access-Control-Allow-Origin: *");

        $result = [
            '$schema' => 'https://jsonapi.org/schema',
            '$id' => $base,
            '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            'error' => [
                'status' => $http_status_code,
                'title' => $http_status_msg,
            ],
            'meta' => [
                '@type' => 'schema:Message',
                'schema:dateCreated' => date("c"),
                "schema:potentialAction" => [[
                    "schema:name" => "urn:resolver:index",
                    "schema:url" => "{$this->global_conf['base_iri']}/urn:resolver:index"
                ]]
            ]
          ];

        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        die();
    }

    public function execute_output_5xx(
        string $base,
        // string $data,
        int $http_status_code = 501,
        // string $http_status_msg = 'Internal Server Error',
        string $http_status_msg = 'Not Implemented',
        string $mode = null
    ) {
        $mode = $mode ?? "default{$http_status_code}";
        $this->_set_options($this->global_conf['Cache-Control'][$mode]);

        http_response_code($http_status_code);
        header("Cache-Control: {$this->_cc_prefix}, max-age={$this->max_age}, s-maxage={$this->s_maxage}, stale-while-revalidate={$this->stale_while_revalidate}, stale-if-error={$this->stale_if_error}");
        // header('Content-Type: application/json; charset=utf-8');
        header('Content-Type: application/vnd.api+json; charset=utf-8');
        // header("Access-Control-Allow-Origin: *");

        $result = [
            '$schema' => 'https://jsonapi.org/schema',
            '$id' => $base,
            '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            'error' => [
                'status' => $http_status_code,
                'title' => $http_status_msg,
            ],
            'meta' => [
                '@type' => 'schema:Message',
                'schema:dateCreated' => date("c"),
                "schema:potentialAction" => [[
                    "schema:name" => "uptime",
                    "schema:url" => "https://stats.uptimerobot.com/jYDZlFY8jq"
                ],[
                    "schema:name" => "urn:resolver:index",
                    "schema:url" => "{$this->global_conf['base_iri']}/urn:resolver:index"
                ]]
            ]
          ];

        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        die();
    }

    public function execute_redirect(
        string $objective_iri,
        int $http_status_code = 302
    ) {
        http_response_code($http_status_code);
        // @see https://developers.cloudflare.com/cache/about/cache-control/
        header("Cache-Control: {$this->_cc_prefix}, max-age={$this->max_age}, s-maxage={$this->s_maxage}, stale-while-revalidate={$this->stale_while_revalidate}, stale-if-error={$this->stale_if_error}");
        // header('Vary: Accept-Encoding');
        // header("Access-Control-Allow-Origin: *");
        // header('Location: ' . $this->active_urn_to_uri);
        header('Location: ' . $objective_iri);
        die();
    }
}

/**
 * Specialized class to create content for resolver itself.
 * This is necessary since (most of the time) makes no sense redirect
 * to external server internal data about the resolver
 */
class ResponseURNResolver
{
    public int $http_status = 200;
    public string $urn;
    public Router $router;
    public $data;
    public $errors;

    // @TODO create shortcuts such as
    //       https://json-ld.org/playground/#json-ld=https://urn.etica.ai/urn:resolver:index
    // @TODO - https://github.com/json-api/json-api/pull/1611
    //       - https://www.simonthiboutot.com/jsonapi-browser/#/

    // - https://github.com/json-api/json-api/blob/5916f19833847df8fb05fdd42641bd4b111be178/_schemas/1.1/schema_create_resource.json
    // - https://github.com/json-api/json-api/pull/1603

    public function __construct(Router $router, string $urn)
    {
        $this->router = $router;
        $this->urn = $urn;
    }

    public function execute()
    {
        if ($this->urn === 'urn:resolver:ping') {
            return $this->operation_ping();
        }

        if ($this->urn === 'urn:resolver:index') {
            return $this->operation_index();
        }

        if ($this->urn === 'urn:resolver:help') {
            // @TODO
            // return $this->operation_index();
        }

        if (strpos($this->urn, 'urn:resolver:_explore') === 0) {
            return $this->operation_explore();
        }

        $this->http_status = 501; // 501 Not Implemented
        $errors = [
            'status' => 501,
            'title' => 'Not Implemented'
        ];
        return $this->is_success();
    }

    public function is_success()
    {
        return empty($this->errors) && $this->http_status < 500;
    }

    public function operation_explore()
    {
        $resolver_paths = [];

        $this->data = [
            'json-ld' => "https://json-ld.org/playground/#json-ld={$this->router->config->base_iri}/{$this->urn}",
            'openapi' => "https://editor.swagger.io/?url=https://raw.githubusercontent.com/EticaAI/urn-resolver/main/openapi.yml",
        ];

        return true;
        // return $this->is_success();
    }

    public function operation_index()
    {
        $resolver_paths = [];
        foreach ($this->router->resolvers as $key => $value) {
            $parts = explode('/.well-known/urn/', $value);
            array_shift($parts);
            $path = '/.well-known/urn/' . $parts[0];
            $resolver_paths[$key] = $path;
        }

        $this->data = [
            'resolvers' => $resolver_paths,
        ];
        return true;
        // return $this->is_success();
    }

    public function operation_ping()
    {
        $this->data = [
            'message' => "PONG"
        ];
        return true;
        // return $this->is_success();
    }
}

class Router
{
    public Config $config;
    public array $resolvers = array();
    private $active_base;
    private $active_uri;
    private $active_urn = false;
    private $active_urn_to_uri = false;
    private $active_urn_to_httpstatus = 302;
    private $active_rule_prefix = false;
    private $active_rule_conf = false;
    private array $_logs = [];
    private ?bool $_is_error = null;
    private ?bool $_is_home = false;
    private bool $_internal = false;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->active_base = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->active_uri = ltrim($_SERVER['REQUEST_URI'], '/');

        if (strlen($this->active_uri) == 0) {
            $this->_is_home = true;
        } elseif (strpos($this->active_uri, 'urn:') == 0) {
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

        $this->_is_error = true;
        foreach ($urns_pattern_list as $key => $urn_pattern) {
            $full_pattern = '/' . $urn_pattern . '/i';
            $matches = null;
            // if (str_starts_with($this->active_uri, $value)) {
            if (preg_match($full_pattern, $this->active_uri, $matches)) {
                $this->active_rule_prefix = $urn_pattern;
                $json = file_get_contents($this->resolvers[$urn_pattern]);
                // Decode the JSON file
                $json_data = json_decode($json, false);
                // $this->active_rule_conf = [$json_data, $matches, $urn_pattern];
                $this->active_rule_conf = $json_data;
                $this->_is_error = false;
                $this->_rule_calc($full_pattern);
                break;
            }
        }

        return $this->resolvers;
    }

    // private function _rule_calc($in_urn_rule, $active_rule)
    private function _rule_calc(string $urn_pattern)
    {
        if (isset($this->active_rule_conf->_meta) && !empty($this->active_rule_conf->_meta->_internal)) {
            // urn:resolver:(*)
            $this->_internal = true;
            return null;
        }

        // var_dump($this->active_rule_conf);
        // die;

        $all_options = [];
        // $in_urn_rule = 'TODO';
        $matches = null;
        preg_match($urn_pattern, $this->active_uri, $matches);
        foreach ($matches as $key => $value) {
            $all_options['{{ in[' . (string) $key . '] }}'] = $value;
            // array_push($this->_logs, $in_urn_rule);
        }

        $rule = null;
        // var_dump($urn_pattern);die;
        // First, we try exact match
        foreach ($this->active_rule_conf->rules as $key => $potential_rule) {
            if ($this->active_urn === $potential_rule->in->urn) {
                $rule = $potential_rule;
                // var_dump($potential_rule); die;
                break;
            }
        }
        if ($rule === null) {
            foreach ($this->active_rule_conf->rules as $key => $potential_rule) {
                if (empty($potential_rule->out)) {
                    // Rules without out rules cant be generalized
                    continue;
                }

                $urn_pattern_2 = '/' . $potential_rule->in->urn . '/';
                $matches = null;
                if (preg_match($urn_pattern_2, $this->active_uri, $matches)) {
                    $rule = $potential_rule;
                    foreach ($matches as $key => $value) {
                        $all_options['{{ in[' . (string) $key . '] }}'] = $value;
                        // array_push($this->_logs, $in_urn_rule);
                    }
                    break;
                }
            }
        }
        if ($rule === null || empty($rule->out)) {
            $this->_is_error = true;
            return false;
        }
        // var_dump($rule);
        // die('teste');

        // $out_iri = $this->active_rule_conf->rules[0]['iri'];
        // @TODO implement load balancing on this part: out[0]
        // $rule = $this->active_rule_conf->rules[0];

        if (is_array($rule->out)) {
            $out_rule = $rule->out[0];
        } else {
            $out_rule = $rule->out;
        }

        $out_iri = $out_rule->iri;

        if (isset($out_rule->http_status)) {
            $out_http_status = $out_rule->http_status;
            if ($out_http_status) {
                $this->active_urn_to_httpstatus = $out_http_status;
            }
        }

        if ($this->active_rule_conf == false && empty($this->active_urn)) {
            $this->_is_error = true;
            return false;
        }

        // array_push($this->_logs, $out_iri);
        // array_push($this->_logs, $this->active_rule_conf);

        $iri_final = strtr($out_iri, $all_options);
        $this->active_urn_to_uri = $iri_final ;
    }

    public function meta()
    {
        // $rule = ltrim($_SERVER['REQUEST_URI'], '/');
        $meta = [
            // 'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'active_rule_prefix' => $this->active_rule_prefix,
            'active_rule_conf' => $this->active_rule_conf,
            'active_urn' => $this->active_urn,
            'active_urn_to_httpstatus' => $this->active_urn_to_httpstatus,
            'active_urn_to_uri' => $this->active_urn_to_uri,
            // 'rules' => $this->_init_rules(),
            // '_all' => var_export($this, true),
            // '_logs' => $this->_logs,
        ];

        return $meta;
    }

    public function execute()
    {
        // echo strpos($this->active_urn, 'urn:resolver:') ;
        // die($this->active_urn);
        // $mode = 'default';
        if (strpos($this->active_urn, 'urn:resolver:') === 0) {
            $urnr = new ResponseURNResolver($this, $this->active_urn);
            if ($urnr->execute()) {
                $data = $urnr->data;
                $resp = new Response($this->config);
                $resp->execute_output_2xx($this->active_uri, $data);
            } else {
                $resp = new Response($this->config);
                $resp->execute_output_5xx($this->active_uri);
            }
            die;
        }
        // die($mode);
        $resp = new Response($this->config, $mode);
        $target = $this->config->transform_if_necessary($this->active_urn_to_uri);
        $resp->execute_redirect($target, $this->active_urn_to_httpstatus);
        die();

        http_response_code($this->active_urn_to_httpstatus);
        // @see https://developers.cloudflare.com/cache/about/cache-control/
        // This log really needs be reviewned later
        header('Cache-Control: public, max-age=3600, s-maxage=600, stale-while-revalidate=600, stale-if-error=600');
        // header('Vary: Accept-Encoding');
        // header("Access-Control-Allow-Origin: *");
        header('Location: ' . $this->active_urn_to_uri);
        die();
        // header("HTTP/1.1 301 Moved Permanently");
    }

    public function execute_welcome()
    {
        if (!$this->_is_home && $this->_is_error) {
            $mode = 'internal';
            $resp = new Response($this->config, $mode);
            $resp->execute_output_4xx($this->active_base, 404);
            die;

            // http_response_code(404);
            // header("Content-type: application/json; charset=utf-8");
            // header("Access-Control-Allow-Origin: *");
            // header('Cache-Control: public, max-age=900, s-maxage=900, stale-while-revalidate=120');

            // $result = [
            //     '$schema' => 'https://jsonapi.org/schema',
            //     '$id' => $this->active_base,
            //     '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            //     'error' => [
            //         'status' => 404,
            //         'title' => 'Not found',
            //         ],
            //   ];

            // echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            // die();
        }

        // header("Content-type: application/json; charset=utf-8");
        header('Cache-Control: public, max-age=600, s-maxage=60, stale-while-revalidate=600, stale-if-error=600');
        header('Content-Type: application/vnd.api+json; charset=utf-8');

        $resolver_paths = [];
        foreach ($this->resolvers as $key => $value) {
            $parts = explode('/.well-known/urn/', $value);
            array_shift($parts);
            $path = '/.well-known/urn/' . $parts[0];
            $resolver_paths[$key] = $path;
        }

        $result = [
            '$schema' => 'https://jsonapi.org/schema',
            '$id' => $this->active_base . $this->active_uri,
            '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            '@type' => 'schema:Action',
            'schema:endTime' => date("c"),
            'data' => [
                'type' => "schema:Action",
                'id' => $this->config->base_iri,
                'relationships' => [
                    'vurn:resolver:index' => [
                        'links' => [
                            'self' => "{$this->config->base_iri}/urn:resolver:index"
                        ]
                    ],
                    'vurn:resolver:_explore' => [
                        'links' => [
                            'self' => "{$this->config->base_iri}/urn:resolver:_explore"
                        ]
                    ],
                    'vurn:resolver:ping' => [
                        'links' => [
                            'self' => "{$this->config->base_iri}/urn:resolver:ping"
                        ]
                    ]
                ],
                // 'resolvers' => $resolver_paths,
                // Change this later
                // 'attributes' => $resolver_paths,
            ],
            // 'links' => [
            //     'uptime' => 'https://stats.uptimerobot.com/jYDZlFY8jq'
            // ],
            // 'meta' => [
            //     '@type' => 'schema:Message',
            //     'schema:name' => 'URN Resolver',
            //     'schema:dateCreated' => date("c"),
            //     // 'schema:mainEntityOfPage' => 'https://github.com/EticaAI/urn-resolver',
            //     "schema:potentialAction" => [
            //         "schema:name" => "uptime",
            //         "schema:url" => "https://stats.uptimerobot.com/jYDZlFY8jq"
            //     ]
            // ]
          ];

        http_response_code(200);
        // $result->_debug['_router'] =  $this->meta();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        die();
    }

    public function is_success()
    {
        // if (!empty($this->_internal)){
        if (!empty($this->active_urn) && strpos($this->active_urn, 'urn:resolver:') === 0) {
            return true;
        }

        return isset($this->active_urn_to_uri) and !empty($this->active_urn_to_uri);
    }
}
