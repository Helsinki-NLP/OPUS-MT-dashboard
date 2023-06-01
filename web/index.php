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
$package    = get_param('pkg', 'opusmt');
$benchmark  = get_param('test', 'all');
$metric     = get_param('metric', 'bleu');
$showlang   = get_param('scoreslang', 'all');
$model      = get_param('model', 'top');
$chart      = get_param('chart', 'standard');
$userscores = get_param('userscores', 'no');


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

$url_param = make_query(['model' => 'all', 'test' => $test, 'pkg' => 'opusmt']);
$opusmt_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">OPUS-MT</a>]";

$url_param = make_query(['model' => 'all', 'test' => $test, 'pkg' => 'external']);
$external_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">external</a>]";


$contributed_link = "";
$userscores_exists = false;

if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
    $userscores_exists = true;
    if ($userscores == "yes"){
        $url_param = make_query(['model' => 'all', 'test' => $test, 'pkg' => 'contributed']);
        $contributed_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">contributed</a>]";
    }
}



if ($model == 'top'){
    $model_selection_links = "[all models] $opusmt_link $external_link $contributed_link";
}
else{
    $url_param = make_query(['model' => 'top', 'test' => $test]);
    $top_models_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">all models</a>]";
    if ($model == 'all'){
        if ($package == 'external'){
            $model_selection_links = "$top_models_link $opusmt_link [external] $contributed_link";
        }
        elseif ($package == 'contributed'){
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

    $url_param = make_query(['model1' => implode('/',[$package, $model]),
                             'model2' => 'unknown', 'model' => 'unknown']);
    $comparelink = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    $modelhome = $storage_url.$package;
    $modelshort = short_model_name($model);
    $downloadlink = "[<a rel=\"nofollow\" href=\"$modelhome/$model.zip\">download</a>]";
    echo("<li><b>Models:</b> $model_selection_links $comparelink</li>");
    echo("<li><b>Selected:</b> $model</li>");

    $eval_file_url = $storage_urls[$package].'/models/'.$model.'.eval.zip';
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

    // TODO: make this less complicated to show additional info and links
    
    echo('<ul>');
    if ($model == 'top' && $benchmark == 'all'){
        $chart_types = array('standard', 'diff');
        if ($userscores_exists  and $chart == "standard"){
            if ($userscores == "yes"){
                $url_param = make_query(['userscores' => 'no']);
                echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
                echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">exclude scores of user-contributed translations</a></li>');
            }
            else{
                $url_param = make_query(['userscores' => 'yes']);
                echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models</li>');
                echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">include scores of user-contributed translations</a></li>');
            }
        }
        else{
            echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models</li>');
        }
    }
    elseif ($model == 'top' || $model == 'avg'){
        if ($userscores_exists  and $chart == "standard"){
            if ($userscores == "yes"){
                $url_param = make_query(['userscores' => 'no']);
                echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, green = compact models</li>');
                echo('<li>grey = external models, purple = user-contributed</li>');
                echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">exclude scores of user-contributed translations</a></li>');
            }
            else{
                $url_param = make_query(['userscores' => 'yes']);
                echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, green = compact models</li>');
                echo('<li>grey = external models, purple = user-contributed</li>');
                echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">include scores of user-contributed translations</a></li>');
            }
        }
        if ($benchmark == 'all' || (strpos($benchmark,'tatoeba') !== false)){
            echo('<li>Note: Tatoeba test sets are not reliable for OPUS-MT models!</li>');
        }
    }
    /*
    else{
        echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, green = compact models</li>');
        echo('<li>grey = external models, purple = user-contributed</li>');
    }
    */

    echo('</ul>');
}


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
