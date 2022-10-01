<?php

// only router.php if cli-server
if (php_sapi_name() == 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'])['path'];

    if (file_exists("./$path")) {
        return false;
    } else {
        // In production, these paths are expected to be served by Apache/Nginx
        $hardcoded = [
            '/urn:resolver:schema:urnr' => '_/meta/urnresolver-urnr.schema.json',
            '/urn:resolver:schema:api:base' => '_/meta/urnresolver-api-base.schema.json',
            '/urn:resolver:context:api:base' => '_/meta/urnresolver-api-base.context.jsonld',
            '/urn:resolver:context:api:extra' => '_/meta/urnresolver-api-extra.context.jsonld',
        ];
        if (!empty($hardcoded[$path])) {
            print(file_get_contents($hardcoded[$path]));
            return true;
        }

        include_once "index.php";
        return true;
    }
}
