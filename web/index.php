<?php

include('inc/env.inc');
include('inc/functions.inc');
include('inc/tables.inc');

include('inc/Graphics.inc');
include('inc/ScoreReader.inc');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>
<?php


// get query parameters
// $chart       = get_param('chart', 'standard');
$chart       = get_param('chart', 'diff');
$chartlegend = get_param('legend', 'type');
$userscores  = get_param('userscores', 'no');


include('inc/header.inc');

echo("<h1>OPUS-MT Dashboard</h1>");
echo('<div id="chart">');


// different views that will be available:
//
// (1) compare top scores OPUS-MT vs external (+ contributed in table)
// (2) top scores for OPUS-MT or external
// (3) averaged scores for all models
// (4) scores for a specific model
// (5) scores for a specific benchmark
// (6) compare scores for 2 selected models


$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);


// (1) compare top scores OPUS-MT vs external (+ contributed in table)

if ($model == 'top' && $benchmark == 'all'){
    
    $models = array();
    $scores = array();
    list($scores[0],$models[0]) = $opusmt->get_topscores($langpair, $metric, 'opusmt');
    list($scores[1],$models[1]) = $opusmt->get_topscores($langpair, $metric, 'external');
    if ($userscores == "yes" && $chart != 'diff'){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            list($scores[2],$models[2]) = $opusmt->get_topscores($langpair, $metric, 'contributed');
        }
    }
    
    print_display_options();    
    $graphics->plot_topscore_comparison($scores, $models, $metric, $chart);
    echo '</div><div id="scores" class="query">';
    print_topscore_comparison_table($scores, $models, $langpair, $benchmark, $metric, $userscores, $chart);
    echo('</div>');
}


// (2) top scores for OPUS-MT or external

elseif ($model == 'all' && $benchmark == 'all'){
    list($scores,$models) = $opusmt->get_topscores($langpair, $metric, $package);
    print_display_options();
    $graphics->plot_topscores($scores, $models, $chart);
    // plot_topscores($scores, $models, $chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_topscore_table($scores, $models, $langpair, $metric, $package);
    echo('</div>');
}


// (3) averaged scores for all models

elseif ($benchmark == 'avg'){
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    print_display_options();
    $graphics->plot_benchmark_scores($scores, $chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_testscores_table($scores, $langpair, $benchmark, $metric, $package, $model);
    echo('</div>');
}


// (4) scores for a specific model

elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    $chartlegend = 'type';
    print_display_options();
    if ($chart == 'heatmap' && $multilingual_model){
        print_langpair_heatmap($model, $metric, $benchmark, $package);
    }
    else{
        $scores = $opusmt->get_model_scores($model, $metric, $package, $benchmark, $showlang, $table_max_scores);
        $graphics->plot_model_scores($scores, $chart, $chartlegend);
        echo '</div><div id="scores" class="query">';
        print_modelscore_table($scores, $model,$showlang, $benchmark, $package, $metric);
        echo('</div>');
    }
}


// (5) scores for a specific benchmark

elseif ($benchmark != 'avg' && $benchmark != 'all'){
    $scores = $opusmt->get_benchmark_scores($langpair, $benchmark, $metric, $package, $model, $userscores);
    print_display_options();
    $graphics->plot_benchmark_scores($scores, $chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_testscores_table($scores, $langpair, $benchmark, $metric, $package, $model);
    echo('</div>');
}


// (6) compare scores for 2 selected models
// different script (compare.php)


include('inc/footer.inc');

?>
</body>
</html>
