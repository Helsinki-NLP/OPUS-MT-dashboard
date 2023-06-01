<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

include 'functions.php';
include 'charts.php';

// get query parameters
$package   = get_param('pkg', 'opusmt');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'top');
$userscores = get_param('userscores', 'no');


list($srclang, $trglang, $langpair) = get_langpair();

$lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);


if ($benchmark == 'avg'){
    $averaged_benchmarks = array_shift($lines);
}

$data = array();
$type = array();
$nrscores = 0;

// get model-specific scores
if ($model != 'all' && $model != 'top'){
    $maxscore = 0;
    $index_label = 'benchmark index (see ID in table of scores)';
    foreach($lines as $line) {
        if ($nrscores > $chart_max_scores){
            break;
        }
        $array = explode("\t", rtrim($line));
        if ($showlang != 'all'){
            if ($showlang != $array[0]){
                continue;
            }
        }
        if ($benchmark != 'all'){
            if ($array[1] != $benchmark){
                continue;
            }
        }
        // $score = $metric == 'bleu' ? $array[3] : $array[2];
        $score = (float) $array[2];
        array_push($data,$score);
        array_push($type,model_color($array[count($array)-1], $model));

        $nrscores++;
        if ( $maxscore < $score ){
            $maxscore = $score;
        }
    }
}
// get scores from benchmark-specific leaderboard
elseif ($benchmark != 'all'){
    $index_label = 'model index (see ID in table of scores)';
    foreach($lines as $line) {
        $array = explode("\t", rtrim($line));
        array_unshift($data,$array[0]);
        array_unshift($type,model_color($array[count($array)-1], $array[1]));
        $nrscores++;
    }
    $maxscore = end($data);
 }
// get top-scores
else{
    $maxscore = 0;
    $index_label = 'benchmark index (see ID in table of scores)';
    foreach($lines as $line) {
        $array = explode("\t", rtrim($line));
        array_push($data,$array[1]);
        array_push($type,model_color($array[count($array)-1], $array[2]));
        $nrscores++;
        if ( $maxscore < $array[1] ){
            $maxscore = $array[1];
        }
    }
}



$chart = barchart($data, $metric, $maxscore, $type);
header('Content-Type: image/png');
imagepng($chart);
