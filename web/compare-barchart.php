<?php

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php
//

// TODO: combine with barchart.php to have only one script
//       for generating barcharts


include 'inc/env.inc';
include 'inc/functions.inc';
// include 'inc/charts.inc';
include('inc/gd.inc');


// get query parameters
$package    = get_param('pkg', 'opusmt');
$benchmark  = get_param('test', 'all');
$metric     = get_param('metric', 'bleu');
$model      = get_param('model', 'top');
$model1     = get_param('model1', 'unknown');
$model2     = get_param('model2', 'unknown');
$userscores = get_param('userscores', 'no');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


//-----------------------------------------
// read scores from file or cache
//-----------------------------------------

$lines = array();
$lines[3] = array();

if ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $lines[1] = read_scores($langpair, 'all', $metric, $name1, $pkg1);

    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    $lines[2] = read_scores($langpair, 'all', $metric, $name2, $pkg2);
    $topscores = false;
}
elseif ($model == 'verified'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[2] = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    $topscores = true;
}
elseif ($model == 'unverified'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[3] = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');
    $topscores = true;
}
elseif ($model == 'top'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[2] = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    if ($userscores == "yes"){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            $lines[3] = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');
        }
    }
    $topscores = true;
}
else{
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
}


//-----------------------------------------
// extract scores from score file lines
//-----------------------------------------

$data = array();
$colors = array();

$maxscore = 0;
$scores = array();

$nr_score_files = 0;
foreach (array(1, 2, 3) as $v) {
    $scores_exist[$v] = false;
    if (count($lines[$v])){
        $scores[$v] = array();
        $nr_score_files++;
        $scores_exist[$v] = true;
        foreach($lines[$v] as $line) {
            $array = explode("\t", $line);
            if ($topscores){
                $score = (float) $array[1];
                $key = $array[0];
                $scores[$v][$key] = $score;
                if ( $maxscore < $score ){
                    $maxscore = $score;
                }
            }
            elseif ($showlang == 'all' || $showlang == $array[0]){
                if ($benchmark == 'all' || $benchmark == $array[1]){
                    $score = (float) $array[2];
                    $key = $array[0].'/'.$array[1];
                    $scores[$v][$key] = $score;
                    if ( $maxscore < $score ){
                        $maxscore = $score;
                    }
                }
            }
        }
    }
}



//-----------------------------------------
// prepare data array for the chart
//-----------------------------------------


$nrscores=0;
$model2_color = $model2 == 'unknown' ? 'grey' : 'orange';
$model_colors = array('orange', 'blue', $model2_color, 'purple');
  
foreach($scores[1] as $key => $value) {
    if ($nrscores > $chart_max_scores){
        break;
    }

    if ((array_key_exists($key,$scores[2])) or ($topscores)){
        $nrscores++;
        array_push($data,$value);
        array_push($colors,'blue');
    
        foreach (array(2, 3) as $v) {
            if ($scores_exist[$v]){
                if (array_key_exists($key,$scores[$v])){
                    array_push($data,$scores[$v][$key]);
                    array_push($colors,$model_colors[$v]);
                }
                else{
                    array_push($data,0);
                    array_push($colors,$model_colors[$v]);
                }
            }
        }
    }
}

if (sizeof($data) == 0){
    $data[0] = 0;
}
$nrscores = sizeof($data);

if ($metric == 'bleu' || $metric == 'spbleu'){
  $scale = 100;
}
else{
  $scale = 1;
}

$index_label = 'benchmark index (see ID in table of scores)';
$chart = barchart($data, $maxscore, $colors, $index_label, $metric, $scale, 0, $nr_score_files);

header('Content-Type: image/png');
imagepng($chart);
