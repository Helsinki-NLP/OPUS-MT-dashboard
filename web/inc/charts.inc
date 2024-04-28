<?php

include('inc/display_options.inc');
include('inc/gd.inc');
include('inc/plotly.inc');

// other methods and info for plotting:
// https://blog.ckportfolio.com/tutorial/canvas-visualization-with-php-and-javascript-library
// https://www.w3schools.com/graphics/tryit.asp?filename=trychartjs_bars_colors_more
// https://www.w3schools.com/graphics/tryit.asp?filename=tryplotly_bars
// https://www.w3schools.com/graphics/default.asp


function plot_model_comparison($chart){
    global $renderlib;
    if ($renderlib == 'gd'){
        plot_score_comparison_gd($chart);
    }
    else{
        plot_model_comparison_plotly($chart);
    }
    echo('<ul>');
    echo('<li>blue = model 1, orange = model 2</li>');
    print_renderlib_link();
    echo('</ul>');
}

function plot_topscore_comparison(&$scores, &$models, $metric, $chart){
    global $renderlib;
    if ($renderlib == 'gd'){
        plot_score_comparison_gd($chart);
    }
    else{
        plot_topscore_comparison_plotly($scores, $models, $metric, $chart);
    }
    echo('<ul>');
    echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
}

function plot_topscores(&$scores, &$models, $chart, $chartlegend='type'){
    global $renderlib;
    if ($renderlib == 'gd'){
        plot_scores_gd($chart);
    }
    else{
        plot_topscores_plotly($scores, $models);
    }
    print_chart_legend($chartlegend);
}

function plot_benchmark_scores(&$scores, $chart, $chartlegend='type'){
    global $renderlib;
    if ($renderlib == 'gd'){
        plot_scores_gd($chart);
    }
    else{
        plot_benchmark_scores_plotly($scores, $chart);
    }
    print_chart_legend($chartlegend);
}


function plot_model_scores(&$scores, $chart, $chartlegend='type'){
    global $renderlib;
    if ($renderlib == 'gd'){
        plot_scores_gd($chart);
    }
    else{
        plot_model_scores_plotly($scores);
    }
    print_chart_legend($chartlegend);
}


function print_chart_legend($chartlegend='type'){
    if ($chartlegend == 'size'){ print_size_legend(); }
    echo('<ul>');
    echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
    echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
    print_renderlib_link();
    print_contributed_link();
    echo('</ul>');
}



function model_size_color($size, $chart){
  list($red,$green,$blue) = size_to_rgb($size);
  return imagecolorallocate($chart, $red,$green,$blue);
}


// return a color for a given model and model package

function model_color($package, $model){

    $type2color = array( 'contributed' => 'purple',
                         'external'    => 'grey',
                         'Tatoeba-MT-models' => 'blue',
                         'OPUS-MT-models' => 'orange',
                         'HPLT-MT-models'    => 'darkred');

    // return array_key_exists($package, $type2color) ? $type2color[$package] : 'grey';

    if ($package != 'opusmt'){
        return $type2color[$package];
    }
    elseif (strpos($model,'transformer-small') !== false){
        return 'green';
    }
    elseif (strpos($model,'transformer-tiny') !== false){
        return 'green';
    }
    else{
        list($modelid, $modelurl) = normalize_modelname($model);
        $modelparts = explode('/',$modelid);
        return array_key_exists($modelparts[0], $type2color) ? $type2color[$modelparts[0]] : 'grey';
    }
}

function modelid_color($modelid){
    $type2color = array( 'contributed' => 'purple',
                         'external'    => 'grey',
                         'Tatoeba-MT-models' => 'blue',
                         'OPUS-MT-models' => 'orange',
                         'HPLT-MT-models'    => 'darkred');
    if (strpos($modelid,'transformer-small') !== false){
        return 'green';
    }
    elseif (strpos($modelid,'transformer-tiny') !== false){
        return 'green';
    }
    $modelparts = explode('/',$modelid);
    return array_key_exists($modelparts[0], $type2color) ? $type2color[$modelparts[0]] : 'grey';
}


?>
