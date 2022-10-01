<?php

declare(strict_types=1);

namespace URNResolver;

date_default_timezone_set('UTC');
define("ROOT_PATH", dirname(dirname(__FILE__)));
define("RESOLVER_RULE_PATH", ROOT_PATH . '/public/.well-known/urn');
$global_conf = new Config();
define("URNRESOLVER_BASE", $global_conf->base_iri);


class Common
{
    public const EXT_TO_MEDIATYPE = [
        '.csv' => 'text/csv; charset=utf-8',
        '.json' => 'application/json; charset=utf-8',
        '.jsonld' => 'application/ld+json; charset=utf-8',
        '.tsv' => 'text/tab-separated-values; charset=utf-8',
        '.txt' => 'text/plain; charset=utf-8',
        '.hxl.csv' => 'text/csv; charset=utf-8',
        '.hxl.tsv' => 'text/tab-separated-values; charset=utf-8',
    ];

    public const EXT_TABULAR_DELIMITER = [
        '.csv' => ",",
        '.hxl.csv' => ",",
        '.tsv' => "\t",
        '.hxl.tsv' => "\t",
    ];
}

// @TODO implement profiles https://www.w3.org/TR/dx-prof-conneg/

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

/**
 * Pretty print JSON (2 spaces and newline)
 *
 * @param     object    $data
 * @return    string
 */
function to_json($data)
{
    $json_string_4spaces = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    // https://stackoverflow.com/a/31689850/894546
    $json_string = preg_replace_callback('/^ +/m', function ($m) {
        return str_repeat(' ', strlen($m[0]) / 2);
    }, $json_string_4spaces);
    return $json_string . "\n";
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

    private string $content_type = 'application/json; charset=utf-8';

    # https://emojipedia.org/pt/envelope/
    # https://urn.etica.ai/urn:resolver:ping?✉️=txt

    private array $_opts = [
        // '_cc_mode' => '_cc_mode', // special case, pre initialize defaults
        '_cc_prefix' => '_cc_prefix',
        'max_age' => 'max-age',
        's_maxage' => 'max-age',
        's_maxage' => 's-maxage',
        'stale_while_revalidate' => 'stale-while-revalidate',
        'stale_if_error' => 'stale-if-error',
    ];

    public function __construct(
        Config $config,
        string $mode = 'default',
        array $meta = null
    ) {
        $this->global_conf = $config->global_conf;
        $cc_active = $this->global_conf['Cache-Control'][$mode];
        $this->_set_options($cc_active);

        if (!empty($meta)) {
            $this->content_type = $meta['content_type'] ?? $this->content_type;
        }
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

    private function _output_tabular(
        array $data_tabular,
        $delimiter = "\t"
    ) {
        $out = fopen('php://output', 'w');
        foreach ($data_tabular as $line) {
            fputcsv($out, $line, $delimiter);
        }
        fclose($out);
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
        header("Content-type: application/json; charset=utf-8");
        // header("Access-Control-Allow-Origin: *");

        $result = [
            // '$schema' => 'https://jsonapi.org/schema',
            // @TODO make this also an URN (with htaccess rewirte for performance reason)
            '$schema' => URNRESOLVER_BASE . '/urn:resolver:schema:api:base',
            // '$id' => $base,
            // '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            '@context' => URNRESOLVER_BASE . '/urn:resolver:context:api:base',
            '@id' => $base,
            'data' => $data,
            'meta' => [
                // '@type' => 'schema:Message',
                // 'schema:name' => 'URN Resolver',
                // 'schema:dateCreated' => date("c"),
                'datetime' => date("c"),
                'json-ld' => 'https://json-ld.org/playground/#json-ld=' . URNRESOLVER_BASE . '/' . $base
                // // 'schema:mainEntityOfPage' => 'https://github.com/EticaAI/urn-resolver',
                // "schema:potentialAction" => [
                //     "schema:name" => "uptime",
                //     "schema:url" => "https://stats.uptimerobot.com/jYDZlFY8jq"
                // ]
            ]
          ];

        // echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo to_json($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
        header("Content-type: application/json; charset=utf-8");
        // header("Access-Control-Allow-Origin: *");

        $result = [
            // '$schema' => 'https://jsonapi.org/schema',
            // @TODO make this also an URN (with htaccess rewirte for performance reason)
            '$schema' => URNRESOLVER_BASE . '/urn:resolver:schema:api:base',
            // '$id' => $base,
            // '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            '@context' => URNRESOLVER_BASE . '/urn:resolver:context:api:base',
            '@id' => $base,
            'error' => [
                'status' => $http_status_code,
                'title' => $http_status_msg,
                'seeAlso' => ['urn:resolver:index'],
            ],
            'meta' => [
                'datetime' => date("c"),
                'uptime' => 'https://stats.uptimerobot.com/jYDZlFY8jq',
                'urn:resolver:index' => "{$this->global_conf['base_iri']}/urn:resolver:index",
                // '@type' => 'schema:Message',
                // 'schema:dateCreated' => date("c"),
                // "schema:potentialAction" => [[
                //     "schema:name" => "urn:resolver:index",
                //     "schema:url" => "{$this->global_conf['base_iri']}/urn:resolver:index"
                // ]]
            ]
          ];

        // echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo to_json($result);
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
        header("Content-type: application/json; charset=utf-8");
        // header("Access-Control-Allow-Origin: *");

        $result = [
            // '$schema' => 'https://jsonapi.org/schema',
            // @TODO make this also an URN (with htaccess rewirte for performance reason)
            '$schema' => URNRESOLVER_BASE . '/urn:resolver:schema:api:base',
            // '$id' => $base,
            // '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            '@context' => URNRESOLVER_BASE . '/urn:resolver:context:api:base',
            '@id' => $base,
            'error' => [
                'status' => $http_status_code,
                'title' => $http_status_msg,
                'seeAlso' => ['urn:resolver:index'],
            ],
            'meta' => [
                'datetime' => date("c"),
                'uptime' => 'https://stats.uptimerobot.com/jYDZlFY8jq',
                'urnresolver-issues' => 'https://github.com/EticaAI/urn-resolver/issues',
                // '@type' => 'schema:Message',
                // 'schema:dateCreated' => date("c"),
                // 'json-ld' => 'https://json-ld.org/playground/#json-ld=' . URNRESOLVER_BASE,
                // "schema:potentialAction" => [[
                //     "schema:name" => "uptime",
                //     "schema:url" => "https://stats.uptimerobot.com/jYDZlFY8jq"
                // ],[
                //     "schema:name" => "urn:resolver:index",
                //     "schema:url" => "{$this->global_conf['base_iri']}/urn:resolver:index"
                // ]]
            ]
          ];

        // echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo to_json($result);
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
 * @see https://www.rfc-editor.org/rfc/rfc8141
 * @see https://www.php.net/manual/en/function.parse-url.php
 *
 * @example
 * // https://www.rfc-editor.org/rfc/rfc8141#section-2.3.1
 * urn:example:weather?=op=map&lat=39.56&lon=-104.85&datetime=1969-07-21T02:56:15Z
 *
 */
class URNParser
{
    public ?string $raw_urn;

    // Generic IRI/URI/URL/URN
    public ?string $scheme;
    public ?string $host;
    public ?string $port;
    public ?string $user;
    public ?string $pass;
    public ?string $path;
    public ?string $query;
    public ?string $fragment;

    // convenience (break query parts)
    public ?array $query_parts;

    // Specific to URNs
    // https://www.rfc-editor.org/rfc/rfc8141
    public ?string $nid; // Lower case (if applicable)
    public ?string $nss;
    public ?array $nss_parts;
    public ?string $q_component;
    public ?string $f_component;

    public ?array $q_component_parts;

    public function __construct(string $raw_urn)
    {
        $this->raw_urn = $raw_urn;
        $parsed = parse_url($raw_urn);
        if ($parsed) {
            $this->scheme = $parsed['scheme'] ?? null;
            $this->host = $parsed['host'] ?? null;
            $this->port = $parsed['port'] ?? null;
            $this->user = $parsed['user'] ?? null;
            $this->pass = $parsed['pass'] ?? null;
            $this->path = $parsed['path'] ?? null;
            $this->query = $parsed['query'] ?? null;
            $this->fragment = $parsed['fragment'] ?? null;
            # parse_str
        }
        if (!is_null($this->query)) {
            parse_str($this->query, $this->query_parts);
        }
        if ($this->scheme === 'urn' && !empty($this->path)) {
            $path_parts = explode(':', $this->path);
            $this->nid = strtolower(array_shift($path_parts));
            $this->nss = implode(':', $path_parts);
            $this->nss_parts = $path_parts;

            // https://www.rfc-editor.org/rfc/rfc8141#section-2.3.2
            $temp_q = explode('?=', $this->raw_urn);
            if (count($temp_q) > 1) {
                $q_and_maybe_f = $temp_q[1];
                $temp_qf = explode('#', $q_and_maybe_f);
                $this->q_component = $temp_qf[0];
                parse_str($this->q_component, $this->q_component_parts);
            }
            // https://www.rfc-editor.org/rfc/rfc8141#section-2.3.3
            $temp_f = explode('#', $this->raw_urn);
            if (count($temp_f) > 1) {
                $this->f_component = array_pop($temp_f);
            }
        }
    }
}

class URNParserResolver extends URNParser
{
    public ?string $file_extension;
    public ?string $media_type;

    public function __construct(string $urn)
    {
        parent::__construct($urn);
        // if ($this->nid === 'resolver') {
        //     if (!empty($this->query_parts['u2709'])) {
        //         $this->file_extension = $this->query_parts['u2709'];
        //         $this->media_type = Common::EXT_TO_MEDIATYPE[$this->file_extension];
        //     }
        // }
        // var_dump($this);die;
        if (!empty($this->q_component_parts)) {
            if (!empty($this->q_component_parts['u2709'])) {
                $this->file_extension = $this->q_component_parts['u2709'];
                $this->media_type = Common::EXT_TO_MEDIATYPE[$this->file_extension];
            }
        }
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
    public array $data_tabular;
    public string $format = 'json'; // json(ld), txt, tsv
    public $errors;

    private ?string $content_type = null;
    private ?string $file_extension = null;
    private bool $file_is_tabular = false;

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

    private function _get_urnr_values(array $filters = null)
    {
        $examples = [];
        $resolver_ops = new \stdClass();
        // array_unshift($this->data, ['@type' => '_:TODO']);
        $resolver_ops->{'@type'} = 'vurnr:todo_ops';

        foreach (glob(RESOLVER_RULE_PATH . "/*.urnr.json") as $filepath) {
            $filename = str_replace(RESOLVER_RULE_PATH, '', $filepath);
            $filename = ltrim($filename, '/');
            // $urn_prefix = str_replace('.urnr.yml', '', $filename) . ':';
            $urn_pattern = str_replace('.urnr.json', '', $filename);
            $json = file_get_contents($filepath);

            $json_data = json_decode($json, false);

            if (isset($json_data->{'@id'}) && $json_data->{'@id'} === 'urn:resolver') {
                // if (isset($json_data->{'@id'})){
                // var_dump($json_data->meta); die;
                foreach ($json_data->meta->examples as $key => $value) {
                    $resexemp = array_values((array) $value)[0];
                    // var_dump($value, $resexemp);
                    // die($json_data->meta->examples);
                    $resolver_ops->{$resexemp} = URNRESOLVER_BASE . '/' . $resexemp;
                }
            }

            if (isset($json_data->meta) && isset($json_data->meta->examples)) {
                foreach ($json_data->meta->examples as $key => $value) {
                    array_push($examples, $value->{'in.urn'});
                    // Only get the first example
                    break;
                }
            }
        }

        usort($examples, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $examples2 = new \stdClass();
        foreach ($examples as $key => $value) {
            // var_dump($result->examples);

            // $result->examples[$value] = URNRESOLVER_BASE . '/' . $value;
            // array_push($examples2, [$value => URNRESOLVER_BASE . '/' . $value]);
            $examples2->{$value} = URNRESOLVER_BASE . '/' . $value;
            // var_dump($result->examples);
            // die('aa');
        }

        // var_dump($examples2);
        // var_dump($examples);
        // var_dump($result->examples);
        // die;

        return (object) [
            'examples_all' => $examples2,
            'resolver_ops' => $resolver_ops
        ];
    }

    public function execute()
    {
        // var_dump(parse_url($this->urn));

        // $test = 'urn:example:weather?=op=map&lat=39.56&lon=-104.85&datetime=1969-07-21T02:56:15Z#lalala#lelele';

        // $parsed_urn = new URNParserResolver($test);
        // var_dump($parsed_urn);

        // $parsed_urn = new URNParserResolver($this->urn);
        // var_dump($parsed_urn);
        // die;

        if ($this->urn === 'urn:resolver:ping') {
            return $this->operation_ping();
        }

        if ($this->urn === 'urn:resolver:ping?u2709=.txt') {
            return $this->operation_ping('txt');
        }

        if ($this->urn === 'urn:resolver:ping?u2709=.tsv') {
            return $this->operation_ping('tsv');
        }

        // if (in_array($this->urn, [
        //     // 'urn:resolver:ping?✉️=txt',
        //     // 'urn:resolver:ping?%E2%9C%89=txt',
        //     'urn:resolver:ping?u2709=txt',
        //     'urn:resolver:ping?u2709=tsv',
        //     ])) {
        //     return $this->operation_ping($envelope='txt');
        // }

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

        if (strpos($this->urn, 'urn:resolver:_summary') === 0) {
            return $this->operation_summary();
        }

        $this->http_status = 501; // 501 Not Implemented
        $errors = [
            'status' => 501,
            'title' => 'Not Implemented'
        ];
        return $this->is_success();
    }

    public function get_output_meta()
    {
        return [
            'content_type' => $this->content_type ?? null,
            'file_extension' => $this->file_extension ?? null,
            'file_is_tabular' => $this->file_is_tabular
        ];
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

    public function operation_summary()
    {
        $summary = $this->_get_urnr_values();

        $this->data = (array) $summary;

        // array_unshift($this->data, ['@type' => '_:TODO']);
        // var_dump($this->data);die;

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

    public function operation_ping(string $envelope = null)
    {
        if ($envelope === 'txt') {
            header("Content-type: text/plain; charset=utf-8");
            echo "PONG\n";
            echo date("c") . "\n";
            die;
        }

        if ($envelope === 'tsv') {
            header("Content-type: text/tab-separated-values; charset=utf-8");
            echo "#item+request+id\t#item+response+body\t#date\n";
            echo "urn:resolver:ping\tPONG\t" . date("c") . "\n";
            // echo  . "\n";
            die;
        }

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
    private $active_urn_to_uri = null;
    private $active_urn_to_httpstatus = 302;
    private $active_rule_prefix = false;
    private $active_rule_conf = null;
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
        $mode = 'default';
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
        } else {
            // die($mode);
            $resp = new Response($this->config, $mode);
            $target = $this->config->transform_if_necessary($this->active_urn_to_uri);
            $resp->execute_redirect($target, $this->active_urn_to_httpstatus);
        }
    }

    public function execute_welcome()
    {
        if (!$this->_is_home && $this->_is_error) {
            $mode = 'internal';
            $resp = new Response($this->config, $mode);
            $resp->execute_output_4xx($this->active_base, 404);
            die;
        }

        header('Cache-Control: public, max-age=600, s-maxage=60, stale-while-revalidate=600, stale-if-error=600');
        header("Content-type: application/json; charset=utf-8");

        $resolver_paths = [];
        foreach ($this->resolvers as $key => $value) {
            $parts = explode('/.well-known/urn/', $value);
            array_shift($parts);
            $path = '/.well-known/urn/' . $parts[0];
            $resolver_paths[$key] = $path;
        }

        $urnr = new ResponseURNResolver($this, 'urn:resolver:_summary');
        $urnr->execute();

        // var_dump($urnr->data['resolver_ops']);die;
        $welcome_ops = $urnr->data['resolver_ops'];
        // $welcome_ops->{'@type'} = '_:TODO';

        // var_dump( $welcome_ops);die;

        $result = [
            // '$schema' => 'https://jsonapi.org/schema',
            // @TODO make this also an URN (with htaccess rewirte for performance reason)
            '$schema' => URNRESOLVER_BASE . '/urn:resolver:schema:api:base',
            '@context' => URNRESOLVER_BASE . '/urn:resolver:context:api:base',
            // '$id' => $base,
            // '@context' => 'https://urn.etica.ai/urnresolver-context.jsonld',
            // '@id' => $base,
            '@id' => URNRESOLVER_BASE, // home page, display the site URL
            // '@type' => ['hydra:Collection', 'schema:Action'],
            // '@type' => 'hydra:Collection',
            'data' => $welcome_ops,
            'meta' => [
                'datetime' => date("c"),
                // 'schema:mainEntityOfPage' => 'https://github.com/EticaAI/urn-resolver',
                'json-ld' => 'https://json-ld.org/playground/#json-ld=' . URNRESOLVER_BASE,
                'uptime' => 'https://stats.uptimerobot.com/jYDZlFY8jq',
            ]
          ];

        http_response_code(200);
        // $result->_debug['_router'] =  $this->meta();
        // echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo to_json($result);
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
