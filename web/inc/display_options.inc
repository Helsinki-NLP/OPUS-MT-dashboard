<?php



$multilingual_model = is_multilingual_model($model);
$userscores_exists = local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');



// Create the link list with different display options

function print_display_options(){
    echo("<ul>");
    print_langpair_link();
    print_model_selection_links();
    print_benchmark_link();
    print_metric_links();
    print_chart_type_links();
    echo("</ul>");
}


// langpair link

function langpair_link(){
    global $model, $langpair, $multilingual_model, $showlang;
    
    $langpair_link = $langpair;

    if ($model != 'all' && $model != 'top'){
        if ($multilingual_model){
            if ($showlang != 'all'){
                $url_param = make_query(['scoreslang' => 'all']);
                $alllangs_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all languages</a>";
                $langpair_link = "$langpair [$alllangs_link]";
            }
            else{
                $url_param = make_query(['scoreslang' => $langpair, 'chart' => 'standard']);
                $lang_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$langpair</a>";
                $langpair_link = "[$lang_link] all languages";
            }
        }
    }
    return $langpair_link;
}

function print_langpair_link(){
    $langpair_link = langpair_link();
    echo("<li><b>Language pair:</b> $langpair_link</li>");
}


// links for selecting the model package

function model_selection_links(){
    global $model, $package, $benchmark, $userscores_exists;

    $test = $benchmark;
    if ($benchmark == 'all' || $benchmark == 'avg'){
        $test = 'all';
    }

    $url_param = make_query(['model' => 'all', 'test' => $test, 'pkg' => 'opusmt']);
    $opusmt_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">OPUS-MT</a>]";

    $url_param = make_query(['model' => 'all', 'test' => $test, 'pkg' => 'external']);
    $external_link = "[<a rel=\"nofollow\" href=\"index.php?$url_param\">external</a>]";

    $contributed_link = "";
    if ($userscores_exists){
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
    return $model_selection_links;
}

function print_model_selection_links(){
    global $model;
    $model_selection_links = model_selection_links();
    $compare_link = compare_link();
    echo("<li><b>Models:</b> $model_selection_links $compare_link</li>");
    if ($model != 'all' && $model != 'top'){
        echo("<li><b>Selected:</b> $model</li>");
    }
}



// compare model link

function compare_link(){
    global $model, $package;
    $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown', 'model' => 'unknown']);
    $compare_link = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";

    if ($model != 'all' && $model != 'top'){
        $url_param = make_query(['model1' => implode('/',[$package, $model]),
                                 'model2' => 'unknown', 'model' => 'unknown']);
        $compare_link = "[<a rel=\"nofollow\" href=\"compare.php?". SID . '&'.$url_param."\">compare</a>]";
    }
    return $compare_link;
}



// benchmark selection links

function benchmark_link(){
    global $model, $package, $benchmark, $storage_urls;

    $url_param = make_query(['test' => 'avg']);
    $avgscores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">average score</a>";

    $url_param = make_query(['test' => 'all']);
    $alltests_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">all benchmarks</a>";

    if ($model != 'all' && $model != 'top'){
        $eval_file_url = $storage_urls[$package].'/models/'.$model.'.eval.zip';
        $download_link = "[<a rel=\"nofollow\" href=\"$eval_file_url\">download</a>]";
    
        if ($benchmark != 'all'){
            $benchmark_link = "[$alltests_link] $benchmark $download_link";
        }
        else{
            $benchmark_link = "all benchmarks $download_link";
        }
    }
    elseif ($benchmark != 'all'){
        if ($benchmark != 'avg'){
            $benchmark_link = "[$alltests_link] [$avgscores_link] $benchmark";
        }
        else{
            $benchmark_link = "[$alltests_link] average score";
        }
    }
    else{
        $benchmark_link = "all benchmarks [$avgscores_link]";
    }
    return $benchmark_link;
}

function print_benchmark_link(){
    $benchmark_link = benchmark_link();
    echo("<li><b>Benchmark:</b> $benchmark_link</li>");
}

// display options
// --> move to display_options.inc

function print_chart_type_options($selected_type="standard", $heatmap=false){
    $chart_types = $heatmap ? array('standard', 'diff', 'heatmap'): array('standard', 'diff');
    foreach ($chart_types as $c){
        if ($c == $selected_type){
            echo("[$c]");
        }
        else{
            $query = make_query(['chart' => $c]);
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo("[<a rel=\"nofollow\" href=\"$link\">$c</a>]");
        }
    }
}


function print_chart_type_links(){
    global $model, $benchmark, $chart, $multilingual_model, $chartlegend;

    $show_size_link = false;
    if ($model == 'top' && $benchmark == 'all'){
        echo('<li><b>Chart Type:</b> ');
        print_chart_type_options($chart);
        echo("</li>");
    }
    elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
        if ($multilingual_model){
            echo('<li><b>Chart Type:</b> ');
            if ($chart == 'heatmap'){
                $query = make_query(['chart' => 'standard']);
                $link = $_SERVER['PHP_SELF'].'?'.$query;
                echo("[<a rel=\"nofollow\" href=\"$link\">standard</a>] [heatmap]</li>");
            }
            else{
                $query = make_query(['chart' => 'heatmap', 'scoreslang' => 'all']);
                $link = $_SERVER['PHP_SELF'].'?'.$query;
                echo("[standard] [<a rel=\"nofollow\" href=\"$link\">heatmap</a>]");
                // $show_size_link = true;
            }
        }
    }
    elseif ($benchmark != 'all'){
        echo('<li><b>Chart Type:</b> ');
        print_plot_type_options($chart);
        $show_size_link = true;
    }
    if ($show_size_link){
        if ($chartlegend == 'size'){
            $query = make_query(['legend' => 'type']);
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo(" [size] [<a rel=\"nofollow\" href=\"$link\">type</a>]");
        }
        else{
            $query = make_query(['legend' => 'size']);
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo(" [<a rel=\"nofollow\" href=\"$link\">size</a>] [type]");
        }
    }
}

function print_metric_links(){
    global $metric;
    echo("<li><b>Evaluation metric:</b> ");
    print_metric_options($metric);
    echo("</li>");
}

function print_metric_options($selected_metric='bleu'){
    global $evaluation_metrics;
    foreach ($evaluation_metrics as $m){
        if ($m == $selected_metric){
            echo(" $m ");
        }
        else{
            $query = make_query(array('metric' => $m));
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo("[<a rel=\"nofollow\" href=\"$link\">$m</a>]");
        }
    }
}


function print_plot_type_options($selected_type="barchart"){
    $chart_types = array('barchart', 'scatterplot');    
    foreach ($chart_types as $c){
        if ($c == $selected_type){
            echo("[$c]");
        }
        else{
            $query = make_query(['chart' => $c]);
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo("[<a rel=\"nofollow\" href=\"$link\">$c</a>]");
        }
    }
}

function print_contributed_link(){
    global $userscores, $userscores_exists, $chart;
    if ($userscores_exists  and $chart == "standard"){
        if ($userscores == "yes"){
            $url_param = make_query(['userscores' => 'no']);
            echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">exclude scores of user-contributed translations</a></li>');
        }
        else{
            $url_param = make_query(['userscores' => 'yes']);
            echo('<li><a rel="nofollow" href="index.php?'. SID . '&'.$url_param.'">include scores of user-contributed translations</a></li>');
        }
    }
}


function renderlib_link(){
    global $renderlib;
    if ($renderlib == 'plotly'){
        $url_param = make_query(['renderlib' => 'gd']);
        $renderlib_link = "[<a rel=\"nofollow\" href=\"".$_SERVER['PHP_SELF']."?". SID . '&'.$url_param."\">gd</a>] [plotly]";
    }
    else{
        $url_param = make_query(['renderlib' => 'plotly']);
        $renderlib_link = "[gd] [<a rel=\"nofollow\" href=\"".$_SERVER['PHP_SELF']."?". SID . '&'.$url_param."\">plotly</a>]";
    }
    return $renderlib_link;
}


function print_renderlib_link(){
    echo('<li>render chart with ');
    echo renderlib_link();
    echo('</li>');
}


?>
