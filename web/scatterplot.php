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
$chartlegend = get_param('legend', 'type');


list($srclang, $trglang, $langpair) = get_langpair();

$lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);


if ($benchmark == 'avg'){
    $averaged_benchmarks = array_shift($lines);
}

$data = array();
$type = array();

$maxscore = 0;
$maxsize = 0;

$minscore = 100;
$minsize = 1000;

$index_label = 'benchmark index (see ID in table of scores)';

$id = sizeof($lines);
foreach($lines as $line) {
    $array = explode("\t", rtrim($line));
    $score = (float) $array[0];
    $size = ceil(model_size($array[count($array)-1], modelurl_to_model($array[1])));
    if ($chartlegend == 'size'){
        $color = $size;
    }
    else{
        $color = model_color($array[count($array)-1], modelurl_to_model($array[1]));
    }

    $id--;
    if ($size == 0){
        continue;
    }
    $data[$id] = array($size, $score);
    $type[$id] = $color;

    if ( $maxscore < $score ){
        $maxscore = $score;
    }
    if ( $maxsize < $size ){
        $maxsize = $size;
    }
    
    if ( $minscore > $score ){
        $minscore = $score;
    }
    if ( $minsize > $size ){
        $minsize = $size;
    }

}


$chart = scatter_plot($data, $type, 'size in millions of parameters', $metric,
                      ceil($maxsize), ceil($maxscore*10)/10, floor($minsize), floor($minscore*10)/10);
header('Content-Type: image/png');
imagepng($chart);
