<?php

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php
//

// TODO: combine with barchart.php to have only one script
//       for generating barcharts


include('inc/env.inc');
include('inc/functions.inc');
include('inc/tables.inc');

include('inc/Graphics.inc');
include('inc/ScoreReader.inc');


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

$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);


//-----------------------------------------
// read scores from file or cache
//-----------------------------------------

$scores = array();
$models = array();
$scores[2] = array();

$nr_score_files = 2;
$scores_exist = array(true, true, false);

if ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    
    $scores[0] = $opusmt->get_model_scores($name1, $metric, $pkg1, $benchmark, $showlang);
    $scores[1] = $opusmt->get_model_scores($name2, $metric, $pkg2, $benchmark, $showlang);

    $topscores = false;
}
// elseif ($model == 'top'){
else{
    list($scores[0],$models[0]) = $opusmt->get_topscores($langpair, $metric, 'opusmt');
    list($scores[1],$models[1]) = $opusmt->get_topscores($langpair, $metric, 'external');
    if ($userscores == "yes"){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            list($scores[2],$models[2]) = $opusmt->get_topscores($langpair, $metric, 'external');
            $scores_exist[2] = true;
            $nr_score_files = 3;
        }
    }
    $topscores = true;
}


//-----------------------------------------
// prepare data array for the chart
//-----------------------------------------

$data = array();
$colors = array();

$nrscores = 0;
$maxscore = 0;

$model2_color = $model2 == 'unknown' ? 'grey' : 'orange';
$model_colors = array('blue', $model2_color, 'purple');
  
foreach($scores[0] as $key => $value) {
    if ($nrscores > $chart_max_scores){
        break;
    }

    if ((array_key_exists($key,$scores[1])) or ($topscores)){
        $nrscores++;
        array_push($data,$value);
        array_push($colors,'blue');
        if ($value > $maxscore){
            $maxscore=$value;
        }
    
        foreach (array(1, 2) as $v) {
            if ($scores_exist[$v]){
                if (array_key_exists($key,$scores[$v])){
                    array_push($data,$scores[$v][$key]);
                    array_push($colors,$model_colors[$v]);
                    if ($scores[$v][$key] > $maxscore){
                        $maxscore=$scores[$v][$key];
                    }
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
$chart = $graphics->barchart($data, $maxscore, $colors, $index_label, $metric, $scale, 0, $nr_score_files);

header('Content-Type: image/png');
imagepng($chart);
