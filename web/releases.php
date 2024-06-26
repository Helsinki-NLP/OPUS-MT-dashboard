<?php

include('inc/env.inc');
include('inc/functions.inc');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard - Release History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php
                               

list($srclang, $trglang, $langpair) = get_langpair();

include 'header.php';
echo('<h1>OPUS-MT Dashboard: Release History</h1>');


$releases_url = $leaderboard_url."/release-history.txt";
$releases = file($releases_url);
$storage  = 'https://object.pouta.csc.fi';

if ($base_leaderboard == 'HPLT-MT-leaderboard'){
    sort($releases);
    echo '<ul>';
}

$lastdate = '';
foreach ($releases as $release){
    list($date,$pkg,$langpair,$model) = explode("\t",$release);
    $model = rtrim($model);
    if ($pkg == "HPLT-MT-models"){
        if ($model != ''){
            $model_url = urlencode("$langpair/$model");
            echo("<li><a rel=\"nofollow\" href='https://huggingface.co/HPLT/$model'>$langpair/$model</a> (<a href='index.php?pkg=opusmt&model=$pkg/$model_url&test=all&scoreslang=all'>benchmark results</a>)</li>");
        }
    }
    else{
        if ($lastdate != $date){
            if ($lastdate != ''){
                echo '</ul>';
            }
            echo "<h2>$date</h2><ul>";
            $lastdate = $date;
        }
        if ($model != ''){
            $model_url = urlencode("$langpair/$model");
            echo "<li><a rel=\"nofollow\" href='$storage/$pkg/$langpair/$model.zip'>$langpair/$model</a> (<a href='index.php?pkg=opusmt&model=$pkg/$model_url&test=all&scoreslang=all'>benchmark results</a>)</li>";
        }
    }
}

if ($base_leaderboard == 'HPLT-MT-leaderboard'){
    echo '</ul>';
}


include('footer.php');

?>
</body>
</html>


