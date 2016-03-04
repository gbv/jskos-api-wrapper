<?php

/**
 * Router script for PHP built in webserver.
 *
 * DON'T USE FOR PRODUCTION!
 */

$request = substr($_SERVER['REQUEST_URI'],1);

if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $request)) {

    // directly serve static files
    return false;

} elseif( preg_match('/^([^\/?]+)/',$request, $match) ) {

    // server wrapper if it exists
    $file = "wrappers/".$match[1].".php";
    if (file_exists($file)) {
        include $file;
        exit;
    }
}

// show listing otherwise
echo "<h1>Available wrappers</h1>";
echo "<ul>";
foreach (scandir('wrappers') as $name) {
    if (substr($name,0,1) != '.') {
        $name = substr($name, 0, -4);
        echo "<li>";
        echo "<a href='$name'>$name</a>";
        echo "</li>";
    }
}
echo "</ul>";
