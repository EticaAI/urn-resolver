<?php

// @see https://blog.10kilobyte.com/blog/view/43/php-built-in-server-and-routing-static-content
// @see https://www.php.net/manual/en/features.commandline.webserver.php

// only router.php if cli-server
if (php_sapi_name() == 'cli-server') {
    $info = parse_url($_SERVER['REQUEST_URI']);
    if (file_exists("./$info[path]")) {
        return false;
    } else {
        include_once "index.php";
        return true;
    }
}
