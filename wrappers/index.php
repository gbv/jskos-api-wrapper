<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JSKOS-PHP Examples</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
<a href="https://github.com/gbv/jskos-php-examples"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>
    <div class="container">

<h1>JSKOS-PHP Examples</h1>

<p>
    Try the following wrappers of existing vocabulary services to JSKOS-API!    
</p>

<?php

include '../vendor/autoload.php';

use Symfony\Component\Yaml\Parser;

$yaml = new Parser();
$examples = $yaml->parse(file_get_contents('examples.yaml'));

$wrappers = [];

foreach (scandir('.') as $name) {
    if (preg_match('/^([^.].+)\.php$/', $name, $match) and $name != 'index.php') {
        $wrappers[] = $match[1];
    }
}

foreach ($wrappers as $name) {
    echo "<h2>$name <small>";
    echo "<a href='https://github.com/gbv/jskos-php-examples/blob/master/wrappers/$name.php'>source</a>";
    echo "</small></h2><p>";
    $queryExamples= $examples[$name]['examples'];
    if ($queryExamples) {
        echo "<ul>";
        foreach ($queryExamples as $query) {
            $url = "$name.php?".http_build_query($query);
            echo "<li><a href='$url'>$url</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<a href='$name.php'>$name.php</a>";
    }
    echo "</p>";
}
?>

    </div>
  </body>
</html>

