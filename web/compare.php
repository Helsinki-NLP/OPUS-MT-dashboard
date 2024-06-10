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
  <title>OPUS-MT Dashboard - Compare Models</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>
<?php
          

// get additional query parameters
$chart     = get_param('chart', 'standard');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');
$showlang  = get_param('scoreslang', $langpair);
$userscores  = get_param('userscores', 'no');

set_param('scoreslang', $showlang);

include('inc/header.inc');
echo('<h1>OPUS-MT Dashboard: Compare Models</h1>');

$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);

echo('<div id="chart"><form action="'.$_SERVER['PHP_SELF'].'" method="get"><ul>');
print_model_selection($langpair, $model1, $model2);

if ($model1 != 'unknown'){

    $parts = explode('/',$model1);
    $m1_pkg = array_shift($parts);
    $m1_name = implode('/',$parts);
    
    if ($model2 != 'unknown'){
        
        $parts = explode('/',$model2);
        $m2_pkg = array_shift($parts);
        $m2_name = implode('/',$parts);
    
        echo("<li><b>Evaluation metric:</b> ");
        print_metric_options($metric);
        echo('</li><li><b>Chart Type:</b> ');
        print_chart_type_options($chart, true);
        echo('</li>');
    }
}
echo('</ul></form>');



if (($model1 != 'unknown') && ($model2 != 'unknown')){
    
    if ($chart == 'heatmap'){
        $heatmap_shown = print_langpair_diffmap($m1_name, $m2_name,
                                                $metric, $benchmark,
                                                $m1_pkg, $m2_pkg);
    }
    else{

        $scores = array();
        $scores[0] = $opusmt->get_model_scores($m1_name, $metric, $m1_pkg, $benchmark, $showlang);
        $scores[1] = $opusmt->get_model_scores($m2_name, $metric, $m2_pkg, $benchmark, $showlang);
        
        $graphics->plot_model_comparison($scores, $metric, $chart);
        echo('</div><div id="scores">');

        // TODO: avoid reading the scores again (but for the table we need to know
        //       whether there are other language pairs and benchmarks as well)
        // BUT: with DB calls this is quite OK and fast enough
        
        $allscores = array();
        $allscores[0] = $opusmt->get_model_scores($m1_name, $metric, $m1_pkg, 'all', 'all');
        $allscores[1] = $opusmt->get_model_scores($m2_name, $metric, $m2_pkg, 'all', 'all');
        
        $langpairs = print_modelscore_differences_table($allscores, $showlang, $benchmark, $metric);
        
        echo('</div><div id="list">');
        echo('<ul>');
        if (count($langpairs) > 1 && count($langpairs) < 20){
            echo('<li><b>Langpair(s):</b> ');
            ksort($langpairs);
            foreach ($langpairs as $lp => $count){
                if ($lp == $showlang){
                    echo("[$showlang]");
                }
                else{
                    $url_param = make_query(['scoreslang' => $lp]);
                    echo("[<a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$lp</a>]");
                }
            }            
            if ($showlang != 'all'){
                $url_param = make_query(['scoreslang' => 'all']);
                echo("[<a rel=\"nofollow\" href=\"compare.php?".$url_param."\">all</a>]");
            }
            echo('</li>');
        }
        echo('</ul>');
    }
    echo('</div>');
}
echo("</div>");



function print_model_selection($langpair, $model1, $model2){
    global $opusmt, $metric, $userscores;

    $models = array();
    $models['opusmt'] = $opusmt->get_langpair_models($langpair, $metric, 'opusmt');
    $models['external'] = $opusmt->get_langpair_models($langpair, $metric, 'external');
    if ($userscores == "yes" && $chart != 'diff'){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            $models['contributed'] = $opusmt->get_langpair_models($langpair, $metric, 'contributed');
        }
    }

    $sorted_models = array();
    foreach ($models as $pkg => $pkg_models){
        if (is_array($pkg_models)){
            foreach ($pkg_models as $model){
                if ($pkg == 'opusmt'){
                    $parts = explode('-',rtrim($model));
                    $day = array_pop($parts);
                    $month = array_pop($parts);
                    $year = array_pop($parts);
                    $sorted_models[$pkg.'/'.$model] = "$pkg/$year$month$day/$model";
                }
                else{
                    $sorted_models[$pkg.'/'.$model] = "$pkg/$model";
                }
            }
        }
        arsort($sorted_models);
    }

    if ($model1 != 'unknown'){
        $parts = explode('/',$model1);
        $m1_pkg = array_shift($parts);
        $m1_name = implode('/',$parts);
        $url_param = make_query(['model' => $m1_name, 'pkg' => $m1_pkg]);
        $m_link = "<a rel=\"nofollow\" title=\"$m1_name\" href=\"index.php?".$url_param."\">";
        echo('<li><b>'.$m_link.'Model 1</a>:</b> ');
    }
    else{
        echo('<li><b>Model 1:</b> ');
    }
    echo '<select name="model1" id="model1" onchange="this.form.submit()">';
    if ($model1 == 'unknown'){
        echo "<option value=\"unknown\" selected>-- select a model --</option>";        
    }
    foreach ($sorted_models as $model => $release){
        if ($model != $model2){
            $model_short = short_model_name($model,60,47,10);
            if ($model1 == $model){
                echo "<option value=\"$model\" selected>$model_short</option>";
            }
            else{
                echo "<option value=\"$model\">$model_short</option>";
            }
        }
    }
    echo '</select>';
    if ($model1 != 'unknown' && $model2 != 'unknown') echo(' (blue)');
    echo '</li>';


    if ($model2 != 'unknown'){
        $parts = explode('/',$model2);
        $m2_pkg = array_shift($parts);
        $m2_name = implode('/',$parts);
        $url_param = make_query(['model' => $m2_name, 'pkg' => $m2_pkg]);
        $m_link = "<a rel=\"nofollow\" title=\"$m2_name\" href=\"index.php?".$url_param."\">";
        echo('<li><b>'.$m_link.'Model 2</a>:</b> ');
    }
    else{
        echo('<li><b>Model 2:</b> ');
    }
    echo '<select name="model2" id="model2" onchange="this.form.submit()">';
    if ($model2 == 'unknown'){
        echo "<option value=\"unknown\" selected>-- select a model --</option>";        
    }
    foreach ($sorted_models as $model => $release){
        if ($model != $model1){
            $model_short = short_model_name($model,60,47,10);
            if ($model2 == $model){
                echo "<option value=\"$model\" selected>$model_short</option>";
            }
            else{
                echo "<option value=\"$model\">$model_short</option>";
            }
        }
    }
    echo '</select>';
    if ($model1 != 'unknown' && $model2 != 'unknown') echo(' (orange)');
    echo '</li>';
}

include('inc/footer.inc');

?>
</body>
</html>
