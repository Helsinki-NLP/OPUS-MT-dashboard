<?php

include('inc/env.inc');
include('inc/functions.inc');
include('inc/scores.inc');
include('inc/charts.inc');
include('inc/tables.inc');

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


include 'header.php';

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


// (1) compare top scores OPUS-MT vs external (+ contributed in table)

if ($model == 'top' && $benchmark == 'all'){
    delete_param('model1');
    delete_param('model2');
    print_display_options();
    plot_topscore_comparison($chart);
    echo '</div><div id="scores" class="query">';
    print_topscore_differences_table($langpair, $benchmark, $metric, $userscores);
    echo('</div>');
}


// (2) top scores for OPUS-MT or external

elseif ($model == 'all' && $benchmark == 'all'){
    print_display_options();
    plot_topscores($chart);
    // plot_topscores($chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_topscore_table($langpair, $metric, $package);
    echo('</div>');
}


// (3) averaged scores for all models

elseif ($benchmark == 'avg'){
    print_display_options();
    plot_benchmark_scores($chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_testscores_table($langpair, $benchmark, $metric, $package, $model);
    echo('</div>');
}


// (4) scores for a specific model

elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    $chartlegend = 'type';
    print_display_options();
    if ($chart == 'heatmap'){
        print_langpair_heatmap($model, $metric, $benchmark, $package);
    }
    else{
        plot_model_scores($chart, $chartlegend);
        echo '</div><div id="scores" class="query">';
        print_modelscore_table($model,$showlang, $benchmark, $package, $metric);
        echo('</div>');
    }
}


// (5) scores for a specific benchmark

elseif ($benchmark != 'avg' && $benchmark != 'all'){
    print_display_options();
    plot_benchmark_scores($chart, $chartlegend);
    echo '</div><div id="scores" class="query">';
    print_testscores_table($langpair, $benchmark, $metric, $package, $model);
    echo('</div>');
}


// (6) compare scores for 2 selected models
// different script (compare.php)


include('footer.php');

?>
</body>
</html>
