<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php

include 'functions.php';

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'top');
$chart     = get_param('chart', 'standard');

list($srclang, $trglang, $langpair) = get_langpair();

if ($showlang != 'all'){
    if ($showlang != $langpair){
        set_langpair($showlang);
        list($srclang, $trglang, $langpair) = get_langpair();
    }
}



// DEBUGGING: show parameters in session variable
/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/


include 'header.php';

echo("<h1>OPUS-MT Dashboard</h1>");

echo('<pre>');
print_translation_logfile ($benchmark, $langpair, $model, $package);
echo('</pre>');


include('footer.php');

?>
</body>
</html>
