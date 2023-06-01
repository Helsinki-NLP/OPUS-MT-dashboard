<?php
session_start();

echo("<h1>Info about the current session</h1>");

echo('<ul><li><a href="index.html">return to admin home</a></li>');
echo('<li><a href="../index.php">return to dashboard</a></li></ul>');


if (array_key_exists('action', $_GET)){
    echo("<h2>Admin action</h2>");
    if ($_GET['action'] == "cleanupcache"){
        echo("Cleanup cache - remove files that are older than 1 day:");
        echo '<pre>';
        system("find ".sys_get_temp_dir()." -maxdepth 1 -mtime +1 -type f -name 'opusmteval*'");
        system("find ".sys_get_temp_dir()." -maxdepth 1 -mtime +1 -type f -name 'opusmteval*' -delete");
        system("find ".sys_get_temp_dir()." -maxdepth 1 -type f -name 'opusmteval*' -empty -delete");
        echo '</pre>';
    }

    if ($_GET['action'] == "cleanuptmp"){
        echo("Cleanup cache - remove temporary files that are older than 1 day:");
        echo '<pre>';
        system("find ".sys_get_temp_dir()." -mtime +1 -type f -name '*.eval.zip'");
        system("find ".sys_get_temp_dir()." -mtime +1 -type f -name '*.eval.zip' -delete");
        echo '</pre>';
    }
}


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
system("find ".sys_get_temp_dir()." -type f -name '*.eval.zip'");
echo '</pre>';


?>
