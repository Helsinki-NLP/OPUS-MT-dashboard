<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT - Leaderboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="index.css" type="text/css">
</head>
<body>

<?php

include 'functions.php';

// get query parameters
$package   = get_param('pkg', 'Tatoeba-MT-models');
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$showlang  = get_param('scoreslang', 'all');
$model     = get_param('model', 'top');
$chart     = get_param('chart', 'standard');

list($srclang, $trglang, $langpair) = get_langpair();

if ($showlang != 'all'){
    if ($showlang != $langpair){
        set_langpair($showlang);
        list($srclang, $trglang, $langpair) = get_langpair();
    }
}



// DEBUGGING: show parameters in session variable
/*
foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}
*/



include 'header.php';

echo("<h1>OPUS-MT leaderboard</h1>");
echo('<div id="chart">');


/*
// links to the test sets
$testset_url = 'https://github.com/Helsinki-NLP/OPUS-MT-testsets/tree/master/testsets';
if ($benchmark == 'flores101-dev'){
    $testset_src = implode('/',[$testset_url,'flores101_dataset','dev',$srclang]).".dev";
    $testset_trg = implode('/',[$testset_url,'flores101_dataset','dev',$trglang]).".dev";
}
elseif ($benchmark == 'flores101-devtest'){
    $testset_src = implode('/',[$testset_url,'flores101_dataset','devtest',$srclang]).".devtest";
    $testset_trg = implode('/',[$testset_url,'flores101_dataset','devtest',$trglang]).".devtest";
}
else{
    $testset_src = implode('/',[$testset_url,$langpair,$benchmark]).".$srclang";
    $testset_trg = implode('/',[$testset_url,$langpair,$benchmark]).".$trglang";
}
*/



echo("<ul>");


$test = $benchmark;
if ($benchmark == 'all' || $benchmark == 'avg'){
    $test = 'all';
}

$url_param = make_query(['model' => 'all', 'test' => $test, 'modelsource' => 'scores']);
$opusmt_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">OPUS-MT</a>";

$url_param = make_query(['model' => 'all', 'test' => $test, 'modelsource' => 'external-scores']);
$external_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">external</a>";


if ($model == 'top'){
    $model_selection_links = "[all models] [$opusmt_link] [$external_link]";
}
else{
    // $url_param = make_query(['test' => 'all', 'model' => 'top']);
    $url_param = make_query(['model' => 'top', 'test' => $test]);
    $top_models_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all models</a>";
    // if (($benchmark == 'all' || $benchmark == 'avg') && $model == 'all'){
    if ($model == 'all'){
        if ($modelsource == 'external-scores'){
            $model_selection_links = "[$top_models_link] [$opusmt_link] [external]";
        }
        else{
            $model_selection_links = "[$top_models_link] [OPUS-MT] [$external_link]";
        }
    }
    else{
        $model_selection_links = "[$top_models_link] [$opusmt_link] [$external_link]";
    }
}


// $url_param = make_query(['test' => 'all', 'model' => 'all']);
$url_param = make_query(['test' => 'all']);
$topmodels_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">top models</a>";

$url_param = make_query(['model' => 'all']);
$allmodels_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all models</a>";

// $url_param = make_query(['test' => 'avg', 'model' => 'all']);
$url_param = make_query(['test' => 'avg']);
$avgscores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">average score</a>";
$allmodelsavg_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all models</a>";

$url_param = make_query(['test' => 'all']);
$alltests_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>";

if ($model == 'all' or $model == 'avg'){
    if ($modelsource == 'scores'){
        $url_param = make_query(['modelsource' => 'external-scores']);
        $model_source_links = "[opus-mt] [<a rel=\"nofollow\" href=\"index.php?$url_param\">external</a>]";
    }
    else{
        $url_param = make_query(['modelsource' => 'scores']);
        $model_source_links = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">opus-mt</a>] [external]";
    }
}


if ($model != 'all' && $model != 'top'){
    $parts = explode('/',$model);
    $modelfile = array_pop($parts);
    $modellang = array_pop($parts);

    $model1 = implode('/',[$package, $model]);

    if ($modellang == $langpair){
        echo("<li><b>Language pair:</b> $langpair</li>");
    }
    else{
        if ($showlang != 'all'){
            $url_param = make_query(['scoreslang' => 'all']);
            $alllangs_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all languages</a>";
            echo("<li><b>Language pair:</b> $langpair [$alllangs_link]</li>");
        }
        else{
            $url_param = make_query(['scoreslang' => $langpair]);
            $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$langpair</a>";
            echo("<li><b>Language pair:</b> [$lang_link] all languages</li>");
        }
    }

    $url_param = make_query(['model1' => $model1, 'model2' => 'unknown']);
    $comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    $modelhome = $storage_url.$package;
    $modelshort = short_model_name($model);
    // $downloadlink = "<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">$modelshort</a>";
    // $downloadlink = "<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">$model</a>";
    $downloadlink = "[<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">download</a>]";
    // echo("<li><b>Model:</b> [$topmodels_link] [$allmodelsavg_link] $modelshort $downloadlink $comparelink</li>");
    // echo("<li><b>Models:</b> [$top_models_link] [$top_opusmt_link] [$top_external_link] [$avg_opusmt_link] [$avg_external_link] $model_source_links</li>");
    echo("<li><b>Models:</b> $model_selection_links</li>");
    // echo("<li><b>Selected:</b> $model $downloadlink $comparelink</li>");
    echo("<li><b>Selected:</b> $package/$model $comparelink</li>");
    
    if ($benchmark != 'all'){
        echo("<li><b>Benchmark:</b> [$alltests_link] $benchmark</li>");
    }
    else{
        echo("<li><b>Benchmark:</b> all benchmarks</li>");
    }
}

// no model is selected
// but a specific benchmark average score is selected

elseif ($benchmark != 'all'){
    // echo("<li><b>Language pair:</b> $langpair [$topmodels_link]</li>");
    echo("<li><b>Language pair:</b> $langpair</li>");
    echo("<li><b>Models:</b> $model_selection_links</li>");
    if ($benchmark != 'avg'){
        echo("<li><b>Benchmark:</b> [$alltests_link] [$avgscores_link] $benchmark</li>");
    }
    else{
        echo("<li><b>Benchmark:</b> [$alltests_link] average score</li>");
    }
}

// no specific model nor benchmark are selected

else{
    echo("<li><b>Language pair:</b> $langpair</li>");
    echo("<li><b>Models:</b> $model_selection_links</li>");
    echo("<li><b>Benchmark:</b> all benchmarks [$avgscores_link]</li>");
}


echo("<li><b>Metrics:</b> ");
print_metric_options($metric);
echo("</li></ul>");

if ($model == 'top' && $benchmark == 'all'){
    $barchart_script = $chart == 'diff' ? 'diff-barchart.php' : 'compare-barchart.php';
}
else{
    $barchart_script = 'barchart.php';
}


if ( isset( $_COOKIE['PHPSESSID'] ) ) {
    echo("<img src=\"$barchart_script?". SID ."\" alt=\"barchart\" />");
}
else{
    $url_param = make_query([]);
    echo("<img src=\"$barchart_script?$url_param\" alt=\"barchart\" />");
}


if ($model == 'top' && $benchmark == 'all'){
    $chart_types = array('standard', 'diff');
    // read_scores($langpair, 'all', $metric, 'all', 'OPUS-MT-models', 'scores');
    // read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    // echo('<li>'.get_score_filename($langpair, 'all', $metric, 'all', 'OPUS-MT-models', 'scores').'</li>');
    // echo('<li>'.get_score_filename($langpair, 'all', $metric, 'all', 'external', 'external-scores').'</li>');
    echo('<ul><li>blue = OPUS-MT / Tatoeba-MT models, grey = external models</li>');
    echo('<li>Chart Type: ');
    foreach ($chart_types as $c){
        if ($c == $chart){
            echo("[$c]");
        }
        else{
            $url_param = make_query(['chart' => $c]);
            echo("[<a rel=\"nofollow\" href=\"index.php?".$url_param."\">$c</a>]");
        }
    }
    echo('</li>');
    /*
    echo('<li>Evaluation Metric: ');
    print_metric_options($metric);
    echo('</li>');
    */
    echo('</ul>');
}
else{
    echo('<ul><li>orange = OPUS-MT models, blue = Tatoeba-MT models, green = compact models</li>');
    echo('<li>grey = external models</li>');
    if ($benchmark == 'all' || (strpos($benchmark,'tatoeba') !== false)){
        echo('<li>Note: Tatoeba test sets are not reliable for OPUS-MT models!</li>');
    }
    /*
    echo('<li>Evaluation Metric: ');
    print_metric_options($metric);
    echo('</li>');
    */
    echo('</ul>');
}


/////////////////////////////////////////////////////
// score table
/////////////////////////////////////////////////////

echo '</div><div id="scores" class="query">';
if ($model == 'top' && $benchmark == 'all'){
    print_topscore_differences($langpair, $benchmark, $metric);
}
elseif ($model != 'all' && $model != 'top'){
    print_model_scores($model,$showlang,$benchmark,$package,$metric);
}
else{
    print_scores($model, $langpair,$benchmark,$package,$metric);
}

echo('</div>');




function print_model_scores($model,$langpair='all',$benchmark='all', $pkg='Tatoeba-MT-models',$metric='all'){
    global $storage_url, $table_max_scores;

    // echo(get_score_filename($langpair, 'all', $metric, $model, $pkg));
    $lines = read_scores($langpair, 'all', $metric, $model, $pkg);

    echo("<h3>Model Scores ($pkg/$model)</h3>");
    if (count($lines) > $table_max_scores){
        echo "<p>There are ".count($lines)." $metric scores for this model. Show max $table_max_scores!</p>";
    }

    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>Output</th><th>$metric</th></tr>");
    $id = 0;
    $langlinks = array();
    $additional_languages = 0;
    $additional_benchmarks = 0;
    $avg1 = 0;
    $avg2 = 0;

    foreach ($lines as $line){
        if ($id > $table_max_scores){
            break;
        }
        $parts = explode("\t",rtrim($line));
        if ($langpair != 'all'){
            if ($parts[0] != $langpair){
                $additional_languages++;
                continue;
            }
        }
        if ($benchmark != 'all'){
            if ($parts[1] != $benchmark){
                $additional_benchmarks++;
                continue;
            }
        }
        if (array_key_exists($parts[0],$langlinks)){
            $langlink = $langlinks[$parts[0]];
        }
        else{
            $query = make_query(['scoreslang' => $parts[0]]);
            $langlink = "<a rel=\"nofollow\" href=\"index.php?$query\">$parts[0]</a>";
            $langlinks[$parts[0]] = $langlink;
        }

        // $modelhome = $storage_url.$pkg;
        // $evallink = "<a rel=\"nofollow\" href=\"$modelhome/$model.eval.zip\">download</a>";
        
        $url_param = make_query(['test' => $parts[1],'langpair' => $parts[0], 'start' => 0, 'end' => 9]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";

        $url_param = make_query(['test' => $parts[1]]);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$parts[1]</a>";

        // echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink, $evallink</td><td>$parts[2]</td></td></tr>");
        echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink</td><td>$parts[2]</td></td></tr>");
        $avg1 += $parts[2];
        $id++;
    }

    if ($id > 0){
        $avg1 /= $id;
        $avg1 = sprintf('%5.3f',$avg1);
    }
        
    $langlink = '';
    $testlink = '';
    if ($additional_languages > 0){
        $url_param = make_query(['scoreslang' => 'all']);
        $langlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    if ($additional_benchmarks > 0){
        $url_param = make_query(['test' => 'all']);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    echo("<tr><th></th><th>$langlink</th><th>$testlink</th><th>average</th><th>$avg1</th></th></tr>");    
    echo('</table>');
}


function print_scores($model='all', $langpair='all', $benchmark='all', $pkg='Tatoeba-MT-models', $metric='bleu', $source='unchanged'){
    global $storage_url;

    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $pkg, $source);
    // $lines = read_scores($langpair, $benchmark, $metric);
    if ($lines == false){
        $lines = array();
    }

    if (count($lines) == 0){
        echo("<h3>No model scores found</h3>");
        return;
    }
    
    if ($benchmark == 'avg'){
        $averaged_benchmarks = implode(', ',explode(' ',rtrim(array_shift($lines))));
        echo("<h3>Model Scores (averaged over $averaged_benchmarks testsets)</h3>");
    }
    elseif ($benchmark == 'all'){
        echo("<h3>Model Scores (top scores on all available benchmarks)</h3>");
    }
    else{
        echo("<h3>Model Scores ($metric scores on the \"$benchmark\" testset)</h3>");
    }
    $id    = sizeof($lines);



    echo('<table><tr><th>ID</th>');
    if ( $benchmark == 'all'){
        echo("<th>Benchmark</th>");
    }
    if ( $benchmark == 'avg'){
        echo("<th>$metric</th><th>Downloads</th><th>Model</th></tr>");
    }
    else{
        echo("<th>$metric</th><th>Output</th><th>Downloads</th><th>Model</th></tr>");
    }
    
    $count=0;
    foreach ($lines as $line){
        $id--;
        $parts = explode("\t",rtrim($line));
        $test = $benchmark == 'all' ? array_shift($parts) : $benchmark;
        $model = explode('/',$parts[1]);
        $modelzip = array_pop($model);

        // some hard-coded decisions about how to get information about the model
        // from the download URL
        // TODO: make this in a principled and less hacky way

        // if there is at least two sub-directories in the URL:
        //    use one as the language pair and another one as the model package name
        //    (e.g. Tatoeba-MT-models/deu-eng, OPUS-MT-models/fin-spa, ...)
        // if not: assume that we at least have one for the package name
        if (count($model) > 4){
            $modellang = array_pop($model);
            $modelpkg = array_pop($model);
            $modelzip = implode('/',[$modellang,$modelzip]);
        }
        else{
            $modelpkg = array_pop($model);
        }
        
        // remove extension .zip if it exists
        if (substr($modelzip, -4) == '.zip'){
            $modelbase = substr($modelzip, 0, -4);
            $baselink = substr($parts[1], 0, -4);
        }
        else{
            $modelbase = $modelzip;
            $baselink = $parts[1];
        }

        $model_download_link = "<a rel=\"nofollow\" href=\"$parts[1]\">model</a>";
        $eval_file_url = $storage_url.'/models/'.$modelpkg.'/'.$modelbase.'.eval.zip';
        $eval_download_link = "<a rel=\"nofollow\" href=\"$eval_file_url\">evaluations</a>";
        // $eval_download_link = "<a rel=\"nofollow\" href=\"$baselink.eval.zip\">evaluations</a>";
        
        $link = "<a rel=\"nofollow\" href=\"$parts[1]\">$modelzip</a>";
        $evallink = "<a rel=\"nofollow\" href=\"$baselink.eval.zip\">download</a>";
        
        $url_param = make_query(['model' => $modelbase, 'pkg' => $modelpkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">scores</a>";
        $modelshort = short_model_name($modelbase);
        $model_scores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$modelpkg/$modelshort</a>";


        if ( $benchmark == 'all'){
            $url_param = make_query(['test' => $test, 'scoreslang' => $langpair ]);
            echo("<tr><td>$count</td><td><a rel=\"nofollow\" href=\"index.php?$url_param\">$test</a></td>");
        }
        else{
            echo("<tr><td>$id</td>");
        }
        $pretty_score = $metric == 'bleu' ? sprintf('%4.1f',$parts[0]) : sprintf('%5.3f',$parts[0]);
        echo("<td>$pretty_score</td>");
        if ( $benchmark != 'avg'){
            $url_param = make_query(['model' => $modelbase,
                                     'pkg' => $modelpkg,'test' => $test,'langpair' => $langpair,
                                     'start' => 0, 'end' => 9 ]);
            $show_translations_link = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";
            echo("<td>$show_translations_link</td>");
        }
        echo("<td>$model_download_link, $eval_download_link</td><td>$model_scores_link</td></tr>");
        $count++;
    }
    echo('</table>');
}





// print a table with all scores and score differences

function print_topscore_differences($langpair='deu-eng', $benchmark='all', $metric='bleu'){

    $lines1 = read_scores($langpair, 'all', $metric, 'all', 'internal', 'scores');
    $lines2 = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');

    $scores1 = array();
    $model1 = array();
    $pkg1 = array();
    $modellinks = array();
    // $url2model = array();
    foreach($lines1 as $line1) {
        // echo $line1;
        $array = explode("\t", rtrim($line1));
        $score = (float) $array[1];
        $key = $array[0];
        $scores1[$key] = $score;
        list($pkg1[$key],$model1[$key]) = modelurl_to_model($array[2]);
    }

    $scores2 = array();
    $model2 = array();
    $pkg2 = array();
    foreach($lines2 as $line2) {
        $array = explode("\t", rtrim($line2));
        if ($benchmark == 'all' || $benchmark == $array[0]){
            $key = $array[0];
            $score = (float) $array[1];
            $scores2[$key] = $score;
            list($pkg2[$key],$model2[$key]) = modelurl_to_model($array[2]);
        }
    }

    if (count($lines1) == 0){
        print_scores('all', $langpair,$benchmark,'external',$metric, 'external-scores');
        return;
    }
    if (count($lines2) == 0){
        print_scores('all', $langpair,$benchmark,'internal',$metric, 'scores');
        return;
    }

    
    $avg_score1 = 0;
    $avg_score2 = 0;
    $count_scores1 = 0;
    $count_scores2 = 0;
    
    echo('<div id="scores"><div class="query"><table>');
    echo("<tr><th>ID</th><th>Benchmark ($metric)</th><th>Output</th><th>OPUS-MT</th><th>$metric</th><th>external</th><th>$metric</th><th>Diff</th></tr>");
    $id = 0;

    foreach($scores1 as $key => $score1) {
        if (array_key_exists($key,$scores2)){
            $score2 = $scores2[$key];

            $diff = $score1 - $score2;
            $diff_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff) : sprintf('%5.3f',$diff);

            if ($benchmark == 'all' || $benchmark == $key){
                $avg_score1 += $score1;
                $count_scores1++;
                $avg_score2 += $score2;
                $count_scores2++;

                $model1short = short_model_name($model1[$key]);
                $model2short = short_model_name($model2[$key]);
                
                $url_param = make_query(['model' => $model1[$key], 'pkg' => $pkg1[$key]]);
                $model1link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model1short</a>";

                $url_param = make_query(['model' => $model2[$key], 'pkg' => $pkg2[$key]]);
                $model2link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model2short</a>";


                $query = make_query(['test' => $key, 'model1' => "$pkg1[$key]/$model1[$key]", 'model2' => "$pkg2[$key]/$model2[$key]", 'start' => 0, 'end' => 9]);
                $translink = "<a rel=\"nofollow\" href=\"compare-translations.php?".SID.'&'.$query."\">compare</a>";
                $url_param = make_query(['test' => $key]);
                $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$key</a>";

                    
                echo('<tr><td>');
                echo(implode('</td><td>',[$id, $testlink, $translink, $model1link, $score1, $model2link, $score2, $diff_pretty]));
                // echo(implode('</td><td>',[$id, $key, $translink, $model1link, $score1, $model2link, $score2, $diff_pretty]));
                echo('</td></tr>');
                $id++;
            }
        }
        else{
            $diff = $score1;
            $diff_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff) : sprintf('%5.3f',$diff);

            if ($benchmark == 'all' || $benchmark == $key){
                $avg_score1 += $score1;
                $count_scores1++;
                $model1short = short_model_name($model1[$key]);
                
                $url_param = make_query(['model' => $model1[$key], 'pkg' => $pkg1[$key]]);
                $model1link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model1short</a>";
                $query = make_query(['test' => $key, 'model' => "$pkg1[$key]/$model1[$key]", 'start' => 0, 'end' => 9]);
                $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$query."\">show</a>";

                $url_param = make_query(['test' => $key]);
                $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$key</a>";

                echo('<tr><td>');
                // echo(implode('</td><td>',[$id, $key, $translink, $model1link, $score1, '', '', $diff_pretty]));
                echo(implode('</td><td>',[$id, $testlink, $translink, $model1link, $score1, '', '', $diff_pretty]));
                echo('</td></tr>');
                $id++;
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

    echo("<tr><th></th><th></th><th>average</th><th></th><th>$avg1</th><th></th><th>$avg2</th><th>$diff</th></tr>");
    echo('</table></div></div>');
}




?>
</body>
</html>
