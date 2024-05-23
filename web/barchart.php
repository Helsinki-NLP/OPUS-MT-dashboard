<?php

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php


include('inc/env.inc');
include('inc/functions.inc');
include('inc/Graphics.inc');
include('inc/ScoreReader.inc');


// get query parameters
$package   = get_param('pkg', 'opusmt');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'top');
$userscores = get_param('userscores', 'no');
$chartlegend = get_param('legend', 'type');


list($srclang, $trglang, $langpair) = get_langpair();


$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);


$scores = array();
$models = array();
$type = array();

if ($model == 'all' && $benchmark == 'all'){
    $index_label = 'benchmark index (see ID in table of scores)';
    list($scores,$models) = $opusmt->get_topscores($langpair, $metric, $package);
    foreach ($models as $key => $model){
        array_push($type,$graphics->modelid_color($model, $package));
    }
}
elseif ($benchmark == 'avg'){
    $index_label = 'model index (see ID in table of scores)';
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    $scores = array_reverse($scores);
    foreach ($scores as $key => $score){
        list($pkg,$modelid) = explode("\t",$key);
        $color = $chartlegend == 'size' ? (int) model_size($pkg, $modelid) : $graphics->modelid_color($modelid, $pkg);
        array_push($type,$color);
    }
}
elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    $index_label = 'benchmark index (see ID in table of scores)';
    $scores = $opusmt->get_model_scores($model, $metric, $package, $benchmark, $showlang, $table_max_scores);
    $color = $graphics->modelid_color($model, $package);
    foreach ($scores as $key => $score){
        array_push($type,$color);
    }
}
elseif ($benchmark != 'avg' && $benchmark != 'all'){
    $index_label = 'model index (see ID in table of scores)';
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    $scores = array_reverse($scores);
    foreach ($scores as $key => $score){
        list($pkg,$modelid) = explode("\t",$key);
        $color = $chartlegend == 'size' ? (int) model_size($pkg, $modelid) : $graphics->modelid_color($modelid, $pkg);
        array_push($type,$color);
    }
}

$data = array();
$nrscores = 0;
$maxscore = 0;

foreach ($scores as $key => $score){
    array_push($data,$score);
    $nrscores++;
    if ( $maxscore < $score ){
        $maxscore = $score;
    }
}

if ($metric == 'bleu' || $metric == 'spbleu'){
  $scale = 100;
}
else{
  $scale = 1;
}

$chart = $graphics->barchart($data, $maxscore, $type, $index_label, $metric, $scale);
header('Content-Type: image/png');
imagepng($chart);
