<?php

include('inc/env.inc');
include 'inc/functions.inc';
include 'inc/display_options.inc';
include 'inc/charts.inc';
include 'inc/plotly.inc';
include 'inc/tables.inc';

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
$package     = get_param('pkg', 'opusmt');
$benchmark   = get_param('test', 'all');
$metric      = get_param('metric', 'bleu');
$showlang    = get_param('scoreslang', 'all');
$model       = get_param('model', 'top');
$chart       = get_param('chart', 'standard');
$chartlegend = get_param('legend', 'type');
$userscores  = get_param('userscores', 'no');


list($srclang, $trglang, $langpair) = get_langpair();

if ($showlang != 'all'){
    if ($showlang != $langpair){
        set_langpair($showlang);
        list($srclang, $trglang, $langpair) = get_langpair();
    }
}


include 'header.php';

echo("<h1>OPUS-MT Dashboard</h1>");
echo('<div id="chart">');



// Create the link list with different options

$multilingual_model = is_multilingual_model($model);
$userscores_exists = local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');

$langpair_link = langpair_link();
$model_selection_links = model_selection_links();
$compare_link = compare_link();
$benchmark_link = benchmark_link();


// create the list with settings and options

echo("<ul>");
echo("<li><b>Language pair:</b> $langpair_link</li>");
echo("<li><b>Models:</b> $model_selection_links $compare_link</li>");
if ($model != 'all' && $model != 'top'){
    echo("<li><b>Selected:</b> $model</li>");
}
echo("<li><b>Benchmark:</b> $benchmark_link</li>");
echo("<li><b>Evaluation metric:</b> ");
print_metric_options($metric);
echo("</li>");
print_chart_type_links();
echo("</ul>");


$heatmap_shown = false;
$barchart_script = 'barchart.php';



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
    $barchart_script = $chart == 'diff' ? 'diff-barchart.php' : 'compare-barchart.php';
    $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown']);
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"$barchart_script?". SID .'&'.$url_param."\" alt=\"barchart\" />");
    }
    else{
        echo("<img src=\"$barchart_script?$url_param\" alt=\"barchart\" />");
    }
    echo('<ul>');
    echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
    print_contributed_link();
    echo('</ul>');
}


// (2) top scores for OPUS-MT or external

elseif ($model == 'all' && $benchmark == 'all'){
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
    list($data,$type) = read_barchart_scores($lines, $model, $benchmark, $chartlegend, $showlang);
    barchart_plotly($data,$type, $metric);
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_contributed_link();
    echo('</ul>');
}


// (3) averaged scores for all models

elseif ($benchmark == 'avg'){
    if ($chart == 'scatterplot'){
        $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown']);
        if ( isset( $_COOKIE['PHPSESSID'] ) ) {
            echo("<img src=\"scatterplot.php?". SID .'&'.$url_param."\" alt=\"barchart\" />");
        }
        else{
            echo("<img src=\"scatterplot.php?$url_param\" alt=\"barchart\" />");
        }
    }
    else{
        $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
        list($data,$type) = read_barchart_scores($lines, $model, $benchmark, $chartlegend, $showlang);
        barchart_plotly($data,$type, $metric);
    }
    if ($chartlegend == 'size'){ print_size_legend(); }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_contributed_link();
    echo('</ul>');
}


// (4) scores for a specific model

elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    if ($chart == 'heatmap'){
        $heatmap_shown = print_langpair_heatmap($model, $metric, $benchmark, $package);
    }
    else{
        $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
        list($data,$type) = read_barchart_scores($lines, $model, $benchmark, $chartlegend, $showlang);
        barchart_plotly($data,$type, $metric);
        if ($chartlegend == 'size'){ print_size_legend(); }
        echo('<ul>');
        echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
        echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
        print_contributed_link();
        echo('</ul>');
    }
}


// (5) scores for a specific benchmark

elseif ($benchmark != 'avg' && $benchmark != 'all'){
    if ($chart == 'scatterplot'){
        $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown']);
        if ( isset( $_COOKIE['PHPSESSID'] ) ) {
            echo("<img src=\"scatterplot.php?". SID .'&'.$url_param."\" alt=\"barchart\" />");
        }
        else{
            echo("<img src=\"scatterplot.php?$url_param\" alt=\"barchart\" />");
        }
    }
    else{
        $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
        list($data,$type) = read_barchart_scores($lines, $model, $benchmark, $chartlegend, $showlang);
        barchart_plotly($data,$type, $metric);
    }
    if ($chartlegend == 'size'){ print_size_legend(); }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_contributed_link();
    echo('</ul>');
}


// (6) compare scores for 2 selected models
// different script (compare.php)






/////////////////////////////////////////////////////
// score table
/////////////////////////////////////////////////////


if ( ! $heatmap_shown ){
    echo '</div><div id="scores" class="query">';
    if ($model == 'top' && $benchmark == 'all'){
        if ($chart == "diff"){
            print_topscore_differences($langpair, $benchmark, $metric, 'no');
        }
        else{
            print_topscore_differences($langpair, $benchmark, $metric, $userscores);
        }
    }
    elseif ($model != 'all' && $model != 'top'){
        print_model_scores($model,$showlang,$benchmark,$package,$metric);
    }
    else{
        print_scores($model, $langpair,$benchmark,$package,$metric);
    }
    echo('</div>');
}


include('footer.php');

?>
</body>
</html>
