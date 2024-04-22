<?php

include('inc/env.inc');
include('inc/functions.inc');
include('inc/display_options.inc');
include('inc/charts.inc');
include('inc/plotly.inc');
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
$chart       = get_param('chart', 'standard');
$chartlegend = get_param('legend', 'type');
$userscores  = get_param('userscores', 'no');


include 'header.php';

echo("<h1>OPUS-MT Dashboard</h1>");
echo('<div id="chart">');



// Create the link list with different display options

echo("<ul>");
print_langpair_link();
print_model_selection_links();
print_benchmark_link();
print_metric_links();
print_chart_type_links();
echo("</ul>");


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
    set_param('model1', 'unknown');
    set_param('model2', 'unknown');
    if ($renderlib == 'gd'){
        plot_score_comparison($chart);
    }
    else{
        plot_score_comparison_plotly($chart);
    }
    echo('<ul>');
    echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
    echo '</div><div id="scores" class="query">';
    if ($chart == "diff"){
        print_topscore_differences($langpair, $benchmark, $metric, 'no');
    }
    else{
        print_topscore_differences($langpair, $benchmark, $metric, $userscores);
    }
    echo('</div>');
}


// (2) top scores for OPUS-MT or external

elseif ($model == 'all' && $benchmark == 'all'){
    // if ($chart == 'scatterplot' || $renderlib == 'gd'){
    if ($renderlib == 'gd'){
        plot_scores($chart);
    }
    else{
        plot_topscores_plotly();
    }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
    echo '</div><div id="scores" class="query">';
    print_scores($model, $langpair,$benchmark,$package,$metric);
    echo('</div>');
}


// (3) averaged scores for all models

elseif ($benchmark == 'avg'){
    // if ($chart == 'scatterplot' || $renderlib == 'gd'){
    if ($renderlib == 'gd'){
        plot_scores($chart);
    }
    else{
        plot_benchmark_scores_plotly($chart);
    }
    if ($chartlegend == 'size'){ print_size_legend(); }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
    echo '</div><div id="scores" class="query">';
    print_scores($model, $langpair,$benchmark,$package,$metric);
    echo('</div>');
}


// (4) scores for a specific model

elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    $chartlegend = 'type';
    if ($chart == 'heatmap'){
        print_langpair_heatmap($model, $metric, $benchmark, $package);
    }
    else{
        if ($renderlib == 'gd'){
            plot_scores($chart);
        }
        else{
            plot_model_scores_plotly();
        }
        if ($chartlegend == 'size'){ print_size_legend(); }
        echo('<ul>');
        echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
        echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
        print_renderlib_link();
        print_contributed_link();
        echo('</ul>');
        echo '</div><div id="scores" class="query">';
        print_model_scores($model,$showlang,$benchmark,$package,$metric);
        echo('</div>');
    }
}


// (5) scores for a specific benchmark

elseif ($benchmark != 'avg' && $benchmark != 'all'){
    // if ($chart == 'scatterplot' || $renderlib == 'gd'){
    if ($renderlib == 'gd'){
        plot_scores($chart);
    }
    else{
        plot_benchmark_scores_plotly($chart);
    }
    if ($chartlegend == 'size'){ print_size_legend(); }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
    echo '</div><div id="scores" class="query">';
    print_scores($model, $langpair,$benchmark,$package,$metric);
    echo('</div>');
}


// (6) compare scores for 2 selected models
// different script (compare.php)


// echo("</ul>");
include('footer.php');

?>
</body>
</html>
