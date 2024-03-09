<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

include 'functions.php';
include 'charts.php';

// get query parameters
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$model     = get_param('model', 'top');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


if ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $lines1 = read_scores($langpair, 'all', $metric, $name1, $pkg1);

    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    $lines2 = read_scores($langpair, 'all', $metric, $name2, $pkg2);
    $topscores = false;
}
elseif ($model == 'top'){
    list($srclang, $trglang, $langpair) = get_langpair();
    $lines1 = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines2 = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    $topscores = true;
}



$data = array();
$colors = array();


if ($metric == 'bleu' || $metric == 'spbleu'){
    $maxscore = 1;
    $minscore = -1;
    $scale = 100;
}
else{
    $maxscore = 0.01;
    $minscore = -0.01;
    $scale = 1;
}


// read model-specific scores
$scores1 = array();
foreach($lines1 as $line1) {
    $array = explode("\t", $line1);
    if ($topscores){
        $score = (float) $array[1];
        $key = $array[0];
        $scores1[$key] = $score;
    }
    elseif ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores1[$key] = $score;
        }
    }
}

foreach($lines2 as $line2) {
    $array = explode("\t", $line2);
    if ($topscores){
        $score = (float) $array[1];
        $key = $array[0];
        $scores2[$key] = $score;
    }
    elseif ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores2[$key] = $score;
        }
    }
}

$nrscores=0;
foreach($scores1 as $key => $value) {
    if ($nrscores > $chart_max_scores){
        break;
    }

    if (array_key_exists($key,$scores2)){
        $diff = $value - $scores2[$key];
        array_push($data,$diff);
        $nrscores++;
        if ($diff > 0){
            array_push($colors,'blue');
            if ( $maxscore < $diff ){
                $maxscore = $diff;
            }
        }
        else{
	  if ($topscores){
            array_push($colors,'grey');
	  }
	  else{
	    array_push($colors,'orange');
	  }
	  if ( $diff < $minscore ){
	    $minscore = $diff;
	  }
        }
    }
}


// $maxscore = ceil($maxscore);
// $minscore = floor($minscore);

if (sizeof($data) == 0){
    $data[0] = 0;
}
$nrscores = sizeof($data);

$index_label = 'benchmark index (see ID in table of scores)';
$value_label = 'difference in '.$metric;

$chart = barchart($data, $maxscore, $colors, $index_label, $value_label, $scale, $minscore);

/*
 * Output image to browser
 */


header('Content-Type: image/png');
imagepng($chart);
