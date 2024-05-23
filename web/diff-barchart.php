<?php


include('inc/env.inc');
include('inc/functions.inc');
include('inc/Graphics.inc');
include('inc/ScoreReader.inc');



// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php


// get query parameters
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$model     = get_param('model', 'top');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);

$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);

$scores1 = array();
$scores2 = array();

if ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $m1_pkg = array_shift($parts);
    $m1_name = implode('/',$parts);
    $parts = explode('/',$model2);
    $m2_pkg = array_shift($parts);
    $m2_name = implode('/',$parts);
    $scores1 = $opusmt->get_model_scores($m1_name, $metric, $m1_pkg, $benchmark, $showlang);
    $scores2 = $opusmt->get_model_scores($m2_name, $metric, $m2_pkg, $benchmark, $showlang);
    $topscores = false;
}
elseif ($model == 'top'){
    $models1 = array();
    $models2 = array();
    list($scores1,$models1) = $opusmt->get_topscores($langpair, $metric, 'opusmt');
    list($scores2,$models2) = $opusmt->get_topscores($langpair, $metric, 'external');
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

$chart = $graphics->barchart($data, $maxscore, $colors, $index_label, $value_label, $scale, $minscore);

/*
 * Output image to browser
 */


header('Content-Type: image/png');
imagepng($chart);
