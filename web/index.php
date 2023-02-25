<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>OPUS-MT Dashboard</title>
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

echo("<h1>OPUS-MT Dashboard</h1>");
echo('<div id="chart">');


echo("<ul>");

$test = $benchmark;
if ($benchmark == 'all' || $benchmark == 'avg'){
    $test = 'all';
}

$url_param = make_query(['model' => 'all', 'test' => $test, 'modelsource' => 'scores']);
$opusmt_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">OPUS-MT</a>]";

$url_param = make_query(['model' => 'all', 'test' => $test, 'modelsource' => 'external-scores']);
$external_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">external</a>]";

if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'external', 'user-scores')){
    $url_param = make_query(['model' => 'all', 'test' => $test, 'modelsource' => 'user-scores']);
    $contributed_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">contributed</a>]";
}
else{
    $contributed_link = "";
}



if ($model == 'top'){
    $model_selection_links = "[all models] $opusmt_link $external_link $contributed_link";
}
else{
    $url_param = make_query(['model' => 'top', 'test' => $test]);
    $top_models_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">all models</a>]";
    if ($model == 'all'){
        if ($modelsource == 'external-scores'){
            $model_selection_links = "$top_models_link $opusmt_link [external] $contributed_link";
        }
        elseif ($modelsource == 'user-scores'){
            $model_selection_links = "$top_models_link $opusmt_link $external_link [contributed]";
        }
        else{
            $model_selection_links = "$top_models_link [OPUS-MT] $external_link $contributed_link";
        }
    }
    else{
        $model_selection_links = "$top_models_link $opusmt_link $external_link $contributed_link";
    }
}


$url_param = make_query(['test' => 'avg']);
$avgscores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">average score</a>";

$url_param = make_query(['test' => 'all']);
$alltests_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>";

$url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown', 'model' => 'unknown']);
$comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";


$multilingual_model = false;

if ($model != 'all' && $model != 'top'){
    $parts = explode('/',$model);
    $modelfile = array_pop($parts);
    $modellang = array_pop($parts);

    $modelbase = implode('/',[$package, $model]);

    if ($modellang == $langpair){
        echo("<li><b>Language pair:</b> $langpair</li>");
    }
    else{
        $multilingual_model = true;
        if ($showlang != 'all'){
            $url_param = make_query(['scoreslang' => 'all']);
            $alllangs_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all languages</a>";
            echo("<li><b>Language pair:</b> $langpair [$alllangs_link]</li>");
        }
        else{
            $url_param = make_query(['scoreslang' => $langpair, 'chart' => 'standard']);
            $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$langpair</a>";
            echo("<li><b>Language pair:</b> [$lang_link] all languages</li>");
        }
    }

    $url_param = make_query(['model1' => $modelbase, 'model2' => 'unknown', 'model' => 'unknown']);
    $comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    $modelhome = $storage_url.$package;
    $modelshort = short_model_name($model);
    $downloadlink = "[<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">download</a>]";
    echo("<li><b>Models:</b> $model_selection_links $comparelink</li>");
    echo("<li><b>Selected:</b> $package/$model</li>");

    $eval_file_url = $storage_url.'/models/'.$modelbase.'.eval.zip';
    $downloadlink = "[<a rel=\"nofollow\" href=\"$eval_file_url\">download</a>]";
    
    if ($benchmark != 'all'){
        echo("<li><b>Benchmark:</b> [$alltests_link] $benchmark $downloadlink</li>");
    }
    else{
        echo("<li><b>Benchmark:</b> all benchmarks $downloadlink</li>");
    }
}

// no model is selected
// but a specific benchmark average score is selected

elseif ($benchmark != 'all'){
    echo("<li><b>Language pair:</b> $langpair</li>");
    echo("<li><b>Models:</b> $model_selection_links $comparelink</li>");
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
    echo("<li><b>Models:</b> $model_selection_links $comparelink</li>");
    echo("<li><b>Benchmark:</b> all benchmarks [$avgscores_link]</li>");
}


echo("<li><b>Evaluation metric:</b> ");
print_metric_options($metric);
echo("</li>");

$heatmap_shown = false;
$barchart_script = 'barchart.php';

if ($model == 'top' && $benchmark == 'all'){
    echo('<li><b>Chart Type:</b> ');
    print_chart_type_options($chart);
    $barchart_script = $chart == 'diff' ? 'diff-barchart.php' : 'compare-barchart.php';
    echo("</li>");
}
elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
    if ($chart == 'heatmap'){
        $heatmap_shown = print_langpair_heatmap($model, $metric, $benchmark, $package);
    }
    if ($multilingual_model and ! $heatmap_shown ){
        echo('<li><b>Chart Type:</b> ');
        $query = make_query(['chart' => 'heatmap', 'scoreslang' => 'all']);
        $link = $_SERVER['PHP_SELF'].'?'.$query;
        echo("[standard] [<a rel=\"nofollow\" href=\"$link\">heatmap</a>]</li>");
    }
}



echo("</ul>");





if ( ! $heatmap_shown ){
    $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown']);
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"$barchart_script?". SID .'&'.$url_param."\" alt=\"barchart\" />");
    }
    else{
        echo("<img src=\"$barchart_script?$url_param\" alt=\"barchart\" />");
    }

    if ($model == 'top' && $benchmark == 'all'){
        $chart_types = array('standard', 'diff');
        echo('<ul><li>blue = OPUS-MT / Tatoeba-MT models, grey = external models</li></ul>');
    }
    else{
        echo('<ul><li>orange = OPUS-MT, blue = Tatoeba-MT models, green = compact models, grey = external models</li>');
        if ($benchmark == 'all' || (strpos($benchmark,'tatoeba') !== false)){
            echo('<li>Note: Tatoeba test sets are not reliable for OPUS-MT models!</li>');
        }
        echo('</ul>');
    }
}


/////////////////////////////////////////////////////
// score table
/////////////////////////////////////////////////////


if ( ! $heatmap_shown ){
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
}






function print_model_scores($model,$langpair='all',$benchmark='all', $pkg='Tatoeba-MT-models',$metric='all'){
    global $storage_url, $table_max_scores;

    // echo(get_score_filename($langpair, 'all', $metric, $model, $pkg));
    $lines = read_scores($langpair, 'all', $metric, $model, $pkg);

    echo("<h3>Model Scores (selected model)</h3>");
    // echo("<h3>Model Scores ($pkg/$model)</h3>");
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
        
        $url_param = make_query(['test' => $parts[1],'langpair' => $parts[0], 'start' => 0, 'end' => 9]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";

        $url_param = make_query(['test' => $parts[1]]);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$parts[1]</a>";

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
        echo("<h3>Model Scores (top scoring model on all available benchmarks)</h3>");
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
        echo("<th>$metric</th><th>Model</th><th>Link</th></tr>");
    }
    else{
        echo("<th>$metric</th><th>Output</th><th>Model</th><th>Link</th></tr>");
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
            $model_download_link = "<a rel=\"nofollow\" href=\"$parts[1]\">zip-file</a>";
        }
        else{
            $modelbase = $modelzip;
            $baselink = $parts[1];
            $model_download_link = "<a rel=\"nofollow\" href=\"$parts[1]\">URL</a>";
        }

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
        // echo("<td>$model_download_link, $eval_download_link</td><td>$model_scores_link</td></tr>");
        echo("<td>$model_scores_link</td><td>$model_download_link</td></tr>");
        $count++;
    }
    echo('</table>');
}



function print_langpair_heatmap($model, $metric='bleu', $benchmark='all', $pkg='Tatoeba-MT-models', $source='unchanged'){
    $file = get_score_filename('all', $benchmark, $metric, $model, $pkg, $source);
    $lines = read_model_scores('all', $benchmark, $metric, $model, $pkg, $source);

    $scores = array();
    $trglangs = array();
    $counts = array();
    $benchmarks = array();
    
    foreach ($lines as $line){
        list($p,$b,$score) = explode("\t",rtrim(array_shift($lines)));
        $benchmarks[$b]++;
        if (($benchmark == 'all') or ($b == $benchmark)){
        // if (($benchmark == 'all') or (strpos($b,$benchmark) === 0)){
            list($s,$t) = explode('-',$p);
            if ($metric == 'bleu' or $metric == 'spbleu'){
                $scores[$s][$t] += $score;
            }
            else{
                $scores[$s][$t] += 100*$score;
            }
            $counts[$s][$t]++;
            $trglangs[$t]++;
        }
    }

    /*
    if (count($scores) < 3){
        return false;
    }
    if (count($trglangs) < 3){
        return false;
    }
    */

    // shortnames that combine several test sets into one category
    // (newstest of several years, different versions of tatoeba, flores, ...)
    // --> a bit too ad-hoc and also problematic as test-parameter
    /*
    $shortnames = array();
    foreach ($benchmarks as $b => $count){
        list($b) = explode('-',$b);
        list($b) = explode('_',$b);
        $b = preg_replace('/[0-9]*$/', '', $b);
        $shortnames[$b]++;
    }
    */

    echo('<li><b>Chart Type:</b> ');
    $query = make_query(['chart' => 'standard']);
    $link = $_SERVER['PHP_SELF'].'?'.$query;
    echo("[<a rel=\"nofollow\" href=\"$link\">standard</a>] [heatmap]</li>");
    echo('<li><b>Selected Benchmark:</b> ');
    if ( $benchmark == 'all' ){
        echo("[avg] ");
    }
    else{
        $url_param = make_query(['test' => 'all']);
        echo("[<a rel=\"nofollow\" href=\"index.php?$url_param\">avg</a>] ");
    }
    foreach ($benchmarks as $b => $count){
        //        if ($count >= count($trglangs)){
        if ($count > 3){
            if ( $b == $benchmark ){
                echo("[$b] ");
            }
            else{
                $url_param = make_query(['test' => $b]);
                echo("[<a rel=\"nofollow\" href=\"index.php?$url_param\">$b</a>] ");
            }
        }
    }
    /*
    foreach ($shortnames as $b => $count){
        $url_param = make_query(['test' => $b]);
        echo("[<a rel=\"nofollow\" href=\"index.php?$url_param\">$b</a>] ");
    }
    */
    echo('</li>');

    
        
    ksort($trglangs);
    echo('<br/><div class="heatmap"><table><tr><th></th>');
    foreach ($trglangs as $t => $count){
        echo('<th>'.$t.'</th>');
    }
    echo('</tr>');
    
    ksort($scores);
    foreach ($scores as $s => $tab){
        echo('<th>'.$s.'</th>');
        foreach ($trglangs as $t => $count){
            if (array_key_exists($t,$tab)){
                $score = sprintf('%4.1f',$tab[$t] / $counts[$s][$t]);
                if ($benchmark != 'all'){
                    $query = make_query(['test' => $benchmark,
                                         'langpair' => "$s-$t",
                                         'start' => 0, 'end' => 9]);
                    $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$query."\">";
                    echo('<td bgcolor="'.score_color($score).'">'.$translink.$score.'</a></td>');
                }
                else{
                    echo('<td bgcolor="'.score_color($score).'">'.$score.'</td>');
                }
            }
            else{
                echo('<td></td>');
            }
        }
        echo('</tr>');
    }
    echo('</tr></table></div>');
    echo('<br/><li>Scores shown in percentage points</li>');
    print_legend();
    // echo('<br/><li>Scores shown in percentage points</li>');
    return true;
}

function score_color($nr){
    $avg = 30;
    $good = 100;

    $diff = $nr-$avg;

    $red=255;
    $green=255;
    $blue=255;

    if ($diff<0){
        $change1 = abs(pow((0-$diff/$avg),2)*64);
        $change2 = abs(($diff/$avg+1)*32);
        $green-=$change1;
        $blue-=$change1+$change2;
    }
    else{
        $change1 = abs(pow(($diff/$good),1)*96);
        $change2 = 0;
        if ($diff<$good){
            $change2 = abs((1-$diff/$good)*32);
        }
        if ($change1>64){
            $change1 = 64;
        }
        $red-=$change1;
        $blue-=$change1+$change2;
    }
    return sprintf("#%x%x%x",$red,$green,$blue);
}



function print_legend(){
    echo '<br/><div class="heatmap">';
    echo '<br/>';
    echo '<table><tr><td>color: </td>';
    for ($x = 0; $x <= 100; $x+=10) {
        echo '<td bgcolor="'.score_color($x).'">&nbsp;&nbsp;&nbsp;</td>';
    }
    echo '</tr><tr><td>score: </td>';
    for ($x = 0; $x <= 100; $x+=10) {
        echo '<td>'.$x.'</td>';
    }
    /*
    echo '</tr><tr><td>code: </td>';
    for ($x = 0; $x <= 100; $x+=10) {
        echo '<td>'.score_color($x).'</td>';
    }
    */
    echo '</tr></table>';
    echo '</div>';
}

function pretty_number($nr,$dec=1){
    if ($nr>1000000000){
      return sprintf("%.${dec}fG",$nr/1000000000);
    }
    if ($nr>100000){
      return sprintf("%.${dec}fM",$nr/1000000);
    }
    if ($nr>100){
      return sprintf("%.${dec}fk",$nr/1000);
    }
    return $nr;
}




// print a table with all scores and score differences

function print_topscore_differences($langpair='deu-eng', $benchmark='all', $metric='bleu'){
    global $chart;

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
    
    echo('<div id="scores"><div class="query">');
    echo("<h3>Model Scores (comparing between OPUS-MT and external models)</h3>");
    echo("<table><tr><th>ID</th><th>Benchmark ($metric)</th><th>Output</th><th>OPUS-MT</th><th>$metric</th><th>external</th><th>$metric</th><th>Diff</th></tr>");
    $id = 0;

    foreach($scores1 as $key => $score1) {
        if ($chart == "diff"){
            if (! array_key_exists($key,$scores2)){
                continue;
            }
        }
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



include('footer.php');

?>
</body>
</html>
