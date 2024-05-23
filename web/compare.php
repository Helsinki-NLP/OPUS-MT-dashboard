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

include('inc/header.inc');
echo('<h1>OPUS-MT Dashboard: Compare Models</h1>');

$opusmt = ScoreReader::new($score_reader);
$graphics = Graphics::new($renderlib);


if ($model1 != 'unknown'){
    echo('<div id="chart"><ul>');

    $parts = explode('/',$model1);
    $m1_pkg = array_shift($parts);
    $m1_name = implode('/',$parts);
    $url_param = make_query(['model' => $m1_name, 'pkg' => $m1_pkg]);
    $m_link = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">";
    echo('<li><b>Model 1 (blue):</b> '.$m_link.$m1_name.'</a></li>');

    if ($model2 != 'unknown'){
        $parts = explode('/',$model2);
        $m2_pkg = array_shift($parts);
        $m2_name = implode('/',$parts);
        $url_param = make_query(['model' => $m2_name, 'pkg' => $m2_pkg]);
        $m_link = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">";
        echo('<li><b>Model 2 (orange):</b> '.$m_link.$m2_name.'</a></li>');
    }
    echo("<li><b>Evaluation metric:</b> ");
    print_metric_options($metric);
    echo('</li><li><b>Chart Type:</b> ');
    print_chart_type_options($chart, true);
    echo('</li></ul>');
}

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


if ($model1 != 'unknown'){
    if ($model2 != 'unknown'){
        echo('<h2>Start with a new model</h2>');
    }
    else{
        echo('<h2>Select the second model to compare with</h2>');
    }
}
else{
    echo('<h2>Select a model</h2>');
}

echo('<table><tr><th>OPUS-MT models</th><th>External models</th><tr><tr><td>');
print_model_list('opusmt', $langpair, $model1, $model2);
echo('</td><td>');
print_model_list('external', $langpair, $model1, $model2);
echo('</td></tr></table>');

echo("</div>");





function print_model_list($pkg, $langpair, $model1, $model2){
    global $leaderboard_urls, $opusmt, $metric;

    $scores_url = $leaderboard_urls[$pkg].'/scores';

    // TODO: do we also want to cache model lists in the SESSION variable?
    // $models = file(implode('/',[$scores_url,$langpair,'model-list.txt']));
    $models = $opusmt->get_langpair_models($langpair, $metric, $pkg);

    $sorted_models = array();
    if (is_array($models)){
        foreach ($models as $model){
            $parts = explode('-',rtrim($model));
            $day = array_pop($parts);
            $month = array_pop($parts);
            $year = array_pop($parts);
            $sorted_models[$model] = "$year$month$day";
        }
        arsort($sorted_models);
    }

    echo("<ul>");
    foreach ($sorted_models as $model => $release){
        $parts = explode('/',rtrim($model));
        $modelzip = array_pop($parts);

        list($modelbase,$modelurl) = normalize_modelname(rtrim($model));
        $new_model = implode('/',[$pkg, $modelbase]);

        if (($model1 != 'unknown') && ($model2 == 'unknown')){
            if ($model1 == $new_model){
                echo("<li>$modelbase</li>");
            }
            else{
                $url_param = make_query(['model1' => $model1, 'model2' => $new_model]);
                echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$modelbase</a></li>");
            }
        }
        else{        
            $url_param = make_query(['model1' => $new_model, 'model2' => 'unknown']);
            echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$modelbase</a></li>");
        }   
    }
    echo("</ul>");
}

include('inc/footer.inc');

?>
</body>
</html>
