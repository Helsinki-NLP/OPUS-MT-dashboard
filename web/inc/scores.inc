<?php


$averaged_benchmarks = '';



// skip some benchmarks

function skip_benchmark($benchmark){
    $skipped = array('flores101-dev','flores200-dev','wikipedia.dev',
                     'news2008','news-test2008','newsdiscussdev2015');
    if (in_array($benchmark,$skipped)){ return true; }
    if (strpos($benchmark, 'newsdev') !== false){ return true; }
    return false;
}



function get_model_scores($model, $metric='bleu', $package='opusmt',
                          $benchmark='all', $langpair='all',
                          $show_max_scores=0){

    $file = get_score_filename($langpair, $benchmark, $metric, $model, $package);
    $lines = read_file_with_cache($file);
    $scores = array();
    
    $nrscores=0;
    foreach($lines as $line) {
        if ($show_max_scores > 0 && $nrscores > $show_max_scores){
            break;
        }
        $array = explode("\t", rtrim($line));
        if (skip_benchmark($array[1])){
            continue;
        }
        $key = $array[1].'/'.$array[0];
        if ($langpair != 'all'){
            if ($langpair != $array[0]){
                continue;
            }
            // $key = $array[1];
        }
        if ($benchmark != 'all'){
            if ($array[1] != $benchmark){
                continue;
            }
            // $key = $array[0];
        }
            
        $scores[$key] = (float) $array[2];
        $nrscores++;
    }
    return $scores;
}

function get_benchmark_scores($langpair, $benchmark, $metric='bleu', $package='opusmt', $model='all'){
    global $userscores;
    
    if ($model == 'top'){
        $scores1 = get_package_benchmark_scores($langpair, $benchmark, $metric, 'opusmt');
        $scores2 = get_package_benchmark_scores($langpair, $benchmark, $metric, 'external');
        $scores3 = array();
        if ($userscores == "yes"){
            $scores3 = get_package_benchmark_scores($langpair, $benchmark, $metric, 'contributed');
        }
        $scores = array_merge($scores1, $scores2, $scores3);
        arsort($scores, SORT_NUMERIC);
    }
    else{
        $scores = get_package_benchmark_scores($langpair, $benchmark, $metric, $package);
    }
    return $scores;
}


function get_package_benchmark_scores($langpair, $benchmark, $metric='bleu', $package='opusmt'){
    global $averaged_benchmarks;
    
    $file = get_score_filename($langpair, $benchmark, $metric, 'all', $package);
    $lines = read_file_with_cache($file);
    
    // first line is a header for averaged scores
    if ($benchmark == 'avg'){
        $averaged_benchmarks = array_shift($lines);
    }
    
    $scores = array();
    foreach($lines as $line) {
        $array = explode("\t", rtrim($line));            
        list($modelid, $modelurl) = normalize_modelname($array[1]);        
        $scores[$package."\t".$modelid] = $array[0];
    }
    // $scores = array_reverse($scores, true);
    return $scores;
}


?>
