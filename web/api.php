<?php

include('inc/env.inc');
include('inc/functions.inc');
include('inc/ScoreReader.inc');

header("Content-Type: text/plain");

// get query parameters
// $chart       = get_param('chart', 'standard');
$chart       = get_param('chart', 'diff');
$chartlegend = get_param('legend', 'type');
$userscores  = get_param('userscores', 'no');

$show_max_scores = 0;
$chart_max_scores = 0;
$table_max_scores = 0;


// different views that will be available:
//
// (1) compare top scores OPUS-MT vs external (+ contributed in table)
// (2) top scores for OPUS-MT or external
// (3) averaged scores for all models
// (4) scores for a specific model
// (5) scores for a specific benchmark
// (6) compare scores for 2 selected models


$opusmt = ScoreReader::new($score_reader);
$data = array();

// (1) compare top scores OPUS-MT vs external (+ contributed in table)



if ($model == 'top' && $benchmark == 'all'){

    list($data['opusmt']['scores'],$data['opusmt']['models']) = $opusmt->get_topscores($langpair, $metric, 'opusmt');
    list($data['external']['scores'],$data['external']['models']) = $opusmt->get_topscores($langpair, $metric, 'external');
    if ($userscores == "yes" && $chart != 'diff'){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            list($data['contributed']['scores'],$data['contibuted']['models']) = $opusmt->get_topscores($langpair, $metric, 'contributed');
        }
    }
}


// (2) top scores for OPUS-MT or external

elseif ($model == 'all' && $benchmark == 'all'){
    list($data['scores'],$data['models']) = $opusmt->get_topscores($langpair, $metric, $package);
}


// (3) averaged scores for all models

elseif ($benchmark == 'avg'){
    $data['scores'] = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
}


// (4) scores for a specific model

elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    $data['scores'] = $opusmt->get_model_scores($model, $metric, $package, $benchmark, $showlang, $table_max_scores);
}


// (5) scores for a specific benchmark

elseif ($benchmark != 'avg' && $benchmark != 'all'){
    $data['scores'] = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
}

echo json_encode($data,JSON_PRETTY_PRINT);

// (6) compare scores for 2 selected models
// different script (compare.php)



?>
