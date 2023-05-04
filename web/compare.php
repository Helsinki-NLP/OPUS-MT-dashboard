<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard - Compare Models</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php
          
include 'functions.php';


// get query parameters
$chart     = get_param('chart', 'standard');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);



// DEBUGGING: show parameters in session variable
/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/



include 'header.php';
echo('<h1>OPUS-MT Dashboard: Compare Models</h1>');


if ($model1 != 'unknown'){
    echo('<div id="chart"><ul>');

    $parts = explode('/',$model1);
    $m1_pkg = array_shift($parts);
    $m1_name = implode('/',$parts);
    $url_param = make_query(['model' => $m1_name, 'pkg' => $m1_pkg]);
    // list($m1_pkg, $m1_lang, $m1_name) = explode('/',$model1);
    // $url_param = make_query(['model' => $m1_lang.'/'.$m1_name, 'pkg' => $m1_pkg]);
    $m_link = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">";
    // echo('<li><b>Model 1 (blue):</b> '.$m_link.$model1.'</a></li>');
    echo('<li><b>Model 1 (blue):</b> '.$m_link.$m1_name.'</a></li>');

    if ($model2 != 'unknown'){
        $parts = explode('/',$model2);
        $m2_pkg = array_shift($parts);
        $m2_name = implode('/',$parts);
        $url_param = make_query(['model' => $m2_name, 'pkg' => $m2_pkg]);
        // list($m2_pkg, $m2_lang, $m2_name) = explode('/',$model2);
        // $url_param = make_query(['model' => $m2_lang.'/'.$m2_name, 'pkg' => $m2_pkg]);
        $m_link = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">";
        // echo('<li><b>Model 2 (orange):</b> '.$m_link.$model2.'</a></li>');
        echo('<li><b>Model 2 (orange):</b> '.$m_link.$m2_name.'</a></li>');
    }
    echo("<li><b>Evaluation metric:</b> ");
    print_metric_options($metric);
    echo('</li><li><b>Chart Type:</b> ');
    print_chart_type_options($chart);
    echo('</li></ul>');
}

if (($model1 != 'unknown') && ($model2 != 'unknown')){
    $chart_script = $chart == 'diff' ? 'diff-barchart.php' : 'compare-barchart.php';
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo('<img src="'.$chart_script.'?'. SID .'" alt="barchart" /><br/><ul>');
    }
    else{
        $url_param = make_query([]);
        echo('<img src="'.$chart_script.'?'. $url_param .'" alt="barchart" /><br/><ul>');
    }


    echo('</div><div id="scores">');
    $langpairs = print_score_table($model1,$model2,$showlang,$benchmark, $metric);
    echo('</div>');
}




if ($model1 != 'unknown'){
    if ($model2 != 'unknown'){
        echo('<br/><div id="list">');
        if (count($langpairs) > 1 && count($langpairs) < 20){
            echo('<ul><li><b>Langpair(s):</b> ');
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
            echo('</li></ul>');
        }
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
    global $leaderboard_urls;

    $scores_url = $leaderboard_urls[$pkg].'/scores';

// TODO: do we also want to cache model lists in the SESSION variable?
$models = file(implode('/',[$scores_url,$langpair,'model-list.txt']));

$sorted_models = array();
foreach ($models as $model){
    $parts = explode('-',rtrim($model));
    $day = array_pop($parts);
    $month = array_pop($parts);
    $year = array_pop($parts);
    $sorted_models[$model] = "$year$month$day";
}
arsort($sorted_models);


echo("<ul>");
foreach ($sorted_models as $model => $release){
    $parts = explode('/',rtrim($model));
    $modelzip = array_pop($parts);

    $modelbase = modelurl_to_model(rtrim($model));
    $new_model = implode('/',[$pkg, $modelbase]);

    if (($model1 != 'unknown') && ($model2 == 'unknown')){
        if ($model1 == $new_model){
            echo("<li>$modelbase</li>");
            // echo("<li>$new_model</li>");
        }
        else{
            $url_param = make_query(['model1' => $model1, 'model2' => $new_model]);
            echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$modelbase</a></li>");
            // echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$new_model</a></li>");
        }
    }
    else{        
        $url_param = make_query(['model1' => $new_model, 'model2' => 'unknown']);
        echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$modelbase</a></li>");
        // echo("<li><a rel=\"nofollow\" href=\"compare.php?".$url_param."\">$new_model</a></li>");
    }   
}
echo("</ul>");

}



// print a table with all scores and score differences

function print_score_table($model1,$model2,$langpair='all',$benchmark='all', $metric='bleu'){
    global $table_max_scores;
    
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $lines1 = read_scores($langpair, 'all', $metric, $name1, $pkg1);
    // list($pkg1, $lang1, $name1) = explode('/',$model1);
    // $lines1 = read_scores($langpair, 'all', $metric, implode('/',[$lang1,$name1]), $pkg1);

    $testsets = array();
    $langpairs = array();
    $scores1 = array();
    foreach($lines1 as $line1) {
        // echo $line1;
        $array = explode("\t", $line1);
        $langpairs[$array[0]]++;
        $testsets[$array[1]]++;
        if ($langpair == 'all' || $langpair == $array[0]){
            if ($benchmark == 'all' || $benchmark == $array[1]){
                $key = $array[0].'/'.$array[1];
                // $score = $metric == 'bleu' ? $array[3] : $array[2];
                $score = $array[2];
                $scores1[$key] = $score;
            }
        }
    }

    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    $lines2 = read_scores($langpair, 'all', $metric, $name2, $pkg2);
    // list($pkg2, $lang2, $name2) = explode('/',$model2);
    // $lines2 = read_scores($langpair, 'all', $metric, implode('/',[$lang2,$name2]), $pkg2);


    $common_langs = array();
    $common_tests = array();
    $scores2 = array();
    foreach($lines2 as $line2) {
        $array = explode("\t", $line2);
        if (array_key_exists($array[0],$langpairs)){
            $common_langs[$array[0]]++;
        }
        if (array_key_exists($array[1],$testsets)){
            $common_tests[$array[1]]++;
        }
        if ($langpair == 'all' || $langpair == $array[0]){
            if ($benchmark == 'all' || $benchmark == $array[1]){
                $key = $array[0].'/'.$array[1];
                // $score = $metric == 'bleu' ? $array[3] : $array[2];
                $score = $array[2];
                $scores2[$key] = $score;
            }
        }
    }
    
    $avg_score1 = 0;
    $avg_score2 = 0;
    $count_scores1 = 0;
    $count_scores2 = 0;

    
    echo('<div id="scores"><div class="query"><table>');
    if (count($scores1) > $table_max_scores){
        echo "<p>There are ".count($scores1)." $metric scores for model 1. Show max $table_max_scores!</p>";
    }

    echo("<tr><th>ID</th><th>Language</th><th>Benchmark ($metric)</th><th>Output</th><th>Model 1</th><th>Model 2</th><th>Diff</th></tr>");
    $id = 0;

    foreach($scores1 as $key => $score1) {
        if ($id > $table_max_scores){
            break;
        }
        if (array_key_exists($key,$scores2)){
            $score2 = $scores2[$key];

            $diff = $score1 - $score2;
            $diff_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff) : sprintf('%5.3f',$diff);

            list($lang, $test) = explode('/',$key);
            $testsets[$test]++;
            $common_langs[$lang]++;

            $lang_query = array();
            $testset_query = array();
            
            if ($langpair == 'all' || $langpair == $lang){
                if ($benchmark == 'all' || $benchmark == $test){
                    $avg_score1 += $score1;
                    $count_scores1++;
                    $avg_score2 += $score2;
                    $count_scores2++;
                    
                    if (! array_key_exists($lang,$lang_query)){
                        $query = make_query(['scoreslang' => $lang]);
                        $lang_query[$lang] = '<a rel="nofollow" href="compare.php?'.$query.'">'.$lang.'</a>';
                    }
                    if (! array_key_exists($test,$test_query)){
                        $query = make_query(['test' => $test]);
                        $test_query[$test] = '<a rel="nofollow" href="compare.php?'.$query.'">'.$test.'</a>';
                    }

                    $query = make_query(['test' => $test,'langpair' => $lang, 'start' => 0, 'end' => 9]);
                    $translink = "<a rel=\"nofollow\" href=\"compare-translations.php?".SID.'&'.$query."\">compare</a>";
                    
                    echo('<tr><td>');
                    echo(implode('</td><td>',[$id, $lang_query[$lang], $test_query[$test], $translink, $score1, $score2, $diff_pretty]));
                    echo('</td></tr>');
                    $id++;
                }
            }
        }
    }
        
    if ($count_scores1 > 1){
        $avg_score1 /= $count_scores1;
    }
    if ($count_scores2 > 1){
        $avg_score2 /= $count_scores2;
    }
    $diff = $avg_score1 - $avg_score2;
    
    if ($metric == 'bleu'){
        $avg1 = sprintf('%4.1f',$avg_score1);
        $avg2 = sprintf('%4.1f',$avg_score2);
        $diff = sprintf('%4.1f',$diff);
    }
    else{
        $avg1 = sprintf('%5.3f',$avg_score1);
        $avg2 = sprintf('%5.3f',$avg_score2);
        $diff = sprintf('%5.3f',$diff);
    }

    $langlink = '';
    $testlink = '';
    if ($langpair != 'all'){
        if (sizeof($common_langs) > 1){
            $url_param = make_query(['scoreslang' => 'all']);
            $langlink = "<a rel=\"nofollow\" href=\"compare.php?".$url_param."\">show all</a>";
        }
    }
    if ($benchmark != 'all'){
        if (sizeof($testsets) > 1){
            $url_param = make_query(['test' => 'all']);
            $testlink = "<a rel=\"nofollow\" href=\"compare.php?".$url_param."\">show all</a>";
        }
    }
    echo("<tr><th></th><th>$langlink</th><th>$testlink</th><th>average</th><th>$avg1</th><th>$avg2</th><th>$diff</th></tr>");

    echo('</table></div></div>');
    return $common_langs;
}

include('footer.php');

?>
</body>
</html>
