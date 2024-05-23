<?php

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


$data = array();
$type = array();

if ($benchmark == 'avg'){
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    foreach ($scores as $key => $score){
        list($pkg,$modelid) = explode("\t",$key);
        $color = $chartlegend == 'size' ? (int) model_size($pkg, $modelid) : $graphics->modelid_color($modelid, $pkg);
        array_push($type,$color);
    }
}
else{
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    foreach ($scores as $key => $score){
        list($pkg,$modelid) = explode("\t",$key);
        $color = $chartlegend == 'size' ? (int) model_size($pkg, $modelid) : $graphics->modelid_color($modelid, $pkg);
        array_push($type,$color);
    }
}



$maxscore = 0;
$maxsize = 0;

$minscore = 100;
$minsize = 1000;

$index_label = 'benchmark index (see ID in table of scores)';

$id = sizeof($scores);
foreach($scores as $key => $score) {
    list($pkg,$modelid) = explode("\t",$key);
    $size = model_size($pkg, $modelid);
    $color = $chartlegend == 'size' ? ceil($size) : $graphics->modelid_color($modelid, $pkg);
    array_push($type,$color);

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


$chart = $graphics->scatter_plot($data, $type, 'size in millions of parameters', $metric,
                                 ceil($maxsize), ceil($maxscore*10)/10, floor($minsize), floor($minscore*10)/10);
header('Content-Type: image/png');
imagepng($chart);
