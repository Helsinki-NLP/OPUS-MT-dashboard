<?php


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


function print_chart_type_links(){
    global $model, $benchmark, $chart, $multilingual_model, $chartlegend;

    $show_size_link = false;
    if ($model == 'top' && $benchmark == 'all'){
        echo('<li><b>Chart Type:</b> ');
        print_chart_type_options($chart);
        echo("</li>");
    }
    elseif ($model != 'top' && $model != 'all' && $model != 'verified' && $model != 'unverified'){
        if ($multilingual_model and $chart != 'heatmap' ){
            echo('<li><b>Chart Type:</b> ');
            $query = make_query(['chart' => 'heatmap', 'scoreslang' => 'all']);
            $link = $_SERVER['PHP_SELF'].'?'.$query;
            echo("[standard] [<a rel=\"nofollow\" href=\"$link\">heatmap</a>]");
            $show_size_link = true;
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

function print_contributed_link(){
    global $userscores_exists, $chart;
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



?>