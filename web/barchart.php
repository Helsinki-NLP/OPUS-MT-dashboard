<?php

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php


include 'inc/env.inc';
include 'inc/functions.inc';
include 'inc/charts.inc';

// get query parameters
$package   = get_param('pkg', 'opusmt');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'top');
$userscores = get_param('userscores', 'no');
$chartlegend = get_param('legend', 'type');


// echo("test = $benchmark");
// exit;

list($srclang, $trglang, $langpair) = get_langpair();

$lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
# echo(implode("<br/>",$lines));
# exit;


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
        list($modelid, $modelurl) = normalize_modelname($array[1]);
        if ($chartlegend == 'size'){
            $size = ceil(model_size($array[count($array)-1], $modelid));
            array_unshift($type,$size);
        }
        else{
            array_unshift($type,model_color($array[count($array)-1], $modelid));
        }
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


if ($metric == 'bleu' || $metric == 'spbleu'){
  $scale = 100;
}
else{
  $scale = 1;
}

// echo(implode("<br/>",$data));
// exit;


$chart = barchart($data, $maxscore, $type, $index_label, $metric, $scale);
header('Content-Type: image/png');
imagepng($chart);
