<?php
session_start();

echo("<h1>Info about the current session</h1>");
echo('<p><a href="index.php">return to dashboard</a></p>');


echo("<h2>Session parameters</h2>");

foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}

echo("<h2>Cached score files</h2>");

if (array_key_exists('cached-scores', $_SESSION)){
    foreach ($_SESSION['cached-scores'] as $key => $value){
        echo "$key => $value <br/>";
    }
}

echo("<h2>Temporary files (translated benchmarks and scores)</h2>");

if (array_key_exists('cached-files', $_SESSION)){
    foreach ($_SESSION['cached-files'] as $key => $value){
        echo "$key => $value <br/>";
    }
}

echo('<br/>Temporary file names:<br/>');
if (array_key_exists('files', $_SESSION)){
    foreach ($_SESSION['files'] as $key => $value){
        echo "$key => $value <br/>";
    }
}

// echo(sys_get_temp_dir());
echo '<pre>';
system("ls -alh ".sys_get_temp_dir());
echo '</pre>';

echo('<br/>Temporary evaluation files:<br/>');

echo '<pre>';
system("find ".sys_get_temp_dir()." -name '*.eval.zip'");
echo '</pre>';


?>
