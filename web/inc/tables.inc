<?php


// print a table with all scores and score differences

function print_topscore_comparison_table(&$scores, &$models,
                                         $langpair='deu-eng', $benchmark='all', $metric='bleu',
                                         $contributed='no', $chart='standard'){

    if (count($scores) < 3){
        $scores[2] = array();
        $models[2] = array();
    }

    // don't print contributed model scores 
    // if the chart shows score differences between OPUS-MT and external models
    if ($chart == "diff" || count($scores[2]) == 0){
        $contributed = 'no';
    }

    if (count($scores[1]) == 0 && count($scores[2]) == 0){
        return print_topscore_table($scores[0], $models[0], $langpair, $metric, $package);
    }
    elseif (count($scores[0]) == 0 && count($scores[2]) == 0){
        return print_topscore_table($scores[1], $models[1], $langpair, $metric, $package);
    }
    elseif (count($scores[0]) == 0 && count($scores[1]) == 0){
        return print_topscore_table($scores[2], $models[2], $langpair, $metric, $package);
    }
    
    $avg_score1 = 0;
    $avg_score2 = 0;
    $avg_score3 = 0;
    
    $count_scores1 = 0;
    $count_scores2 = 0;
    $count_scores3 = 0;
    
    echo('<div id="scores"><div class="query">');
    echo("<h3>Model Scores (comparing between OPUS-MT and external models)</h3>");
    echo("<table><tr><th>ID</th><th>Benchmark ($metric)</th><th>Output</th><th>OPUS-MT</th><th>$metric</th><th>external</th><th>$metric</th><th>Diff</th>");
    if ($contributed == 'yes'){
        echo("<th>contributed</th><th>$metric</th><th>Diff</th>");
    }
    echo('</tr>');
    $id = 0;

    foreach($scores[0] as $key => $score1) {
        if ($chart == "diff"){
            if (! array_key_exists($key,$scores[1])){
                continue;
            }
        }
        if ($contributed == 'yes'){
            if (array_key_exists($key,$scores[2])){
                $score3 = $scores[2][$key];
                $diff3 = $score1 - $score3;
                $diff3_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff3) : sprintf('%5.3f',$diff3);
                $avg_score3 += $score3;
                $count_scores3++;
                $model3short = short_model_name($models[2][$key]);
                $url_param = make_query(['model' => $models[2][$key], 'pkg' => 'contributed']);
                $model3link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model3short</a>";
            }
            else{
                $score3 = '';
                $diff3 = 0;
                $diff3_pretty = '';
                $model3short = '';
                $model3link = '';
            }
        }
            
        if (array_key_exists($key,$scores[1])){
            $score2 = $scores[1][$key];

            $diff = $score1 - $score2;
            $diff_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff) : sprintf('%5.3f',$diff);

            if ($benchmark == 'all' || $benchmark == $key){
                $avg_score1 += $score1;
                $count_scores1++;
                $avg_score2 += $score2;
                $count_scores2++;

                $model1short = short_model_name($models[0][$key]);
                $model2short = short_model_name($models[1][$key]);
                
                $url_param = make_query(['model' => $models[0][$key], 'scoreslang' => $langpair, 'pkg' => 'opusmt']);
                $model1link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model1short</a>";

                $url_param = make_query(['model' => $models[1][$key], 'scoreslang' => $langpair, 'pkg' => 'external']);
                $model2link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model2short</a>";


                $query = make_query(['test' => $key,
                                     'model1' => 'opusmt/'.$models[0][$key],
                                     'model2' => 'external/'.$models[1][$key],
                                     'start' => 0, 'end' => 9]);
                $translink = "<a rel=\"nofollow\" href=\"compare-translations.php?".SID.'&'.$query."\">compare</a>";
                $url_param = make_query(['test' => $key, 'scoreslang' => $langpair]);
                $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$key</a>";

                    
                echo('<tr><td>');
                echo(implode('</td><td>',[$id, $testlink, $translink, $model1link, $score1, $model2link, $score2, $diff_pretty]));
                if ($contributed == 'yes'){
                    echo('</td><td>');
                    echo(implode('</td><td>',[$model3link, $score3, $diff3_pretty]));
                }
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
                $model1short = short_model_name($models[0][$key]);
                
                $url_param = make_query(['model' => $models[0][$key], 'pkg' => 'opusmt', 'scoreslang' => $langpair]);
                $model1link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model1short</a>";
                $query = make_query(['test' => $key, 'model' => 'opusmt/'.$models[0][$key], 'start' => 0, 'end' => 9]);
                $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$query."\">show</a>";

                $url_param = make_query(['test' => $key]);
                $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$key</a>";

                echo('<tr><td>');
                echo(implode('</td><td>',[$id, $testlink, $translink, $model1link, $score1, '', '', $diff_pretty]));
                if ($contributed == 'yes'){
                    echo('</td><td>');
                    echo(implode('</td><td>',[$model3link, $score3, $diff3_pretty]));
                }
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
    
    if ($contributed == 'yes'){
        if ($count_scores3 > 1){
            $avg_score3 /= $count_scores3;
        }
        $diff3 = $avg_score1 - $avg_score3;
        if ($metric == 'bleu'){
            $avg3 = sprintf('%4.1f',$avg_score3);
            $diff3 = sprintf('%4.1f',$diff3);
        }
        else{
            $avg3 = sprintf('%5.3f',$avg_score3);
            $diff3 = sprintf('%5.3f',$diff3);
        }
    }

    echo("<tr><th></th><th></th><th>average</th><th></th><th>$avg1</th><th></th><th>$avg2</th><th>$diff</th>");
    if ($contributed == 'yes'){
        echo("<th></th><th>$avg3</th><th>$diff3</th>");
    }
    echo('</tr></table></div></div>');
}




function print_topscore_table(&$scores, &$models, $langpair, $metric='bleu', $pkg='opusmt'){
    global $storage_urls;
    
    echo("<h3>Model Scores (top scoring model on all available benchmarks)</h3>");
    echo("<table><tr><th>ID</th><th>Benchmark</th><th>$metric</th><th>Output</th><th>Model</th><th>Size</th><th>Link</th></tr>");

    $count = 0;
    foreach ($scores as $benchmark => $score){
        
        $modelid = $models[$benchmark];
        $modelurl = modelid_to_url($modelid, $pkg);
        $modelsize = model_size($pkg,$modelid).'M';
        
        if (substr($modelurl, -4) == '.zip'){
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">zip-file</a>";
        }
        else{
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">URL</a>";
        }
        $url_param = make_query(['model' => $modelid, 'pkg' => $pkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $modelshort = short_model_name($modelid);
        $model_scores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$modelshort</a>";

        $url_param = make_query(['test' => $benchmark, 'scoreslang' => $langpair ]);
        echo("<tr><td>$count</td><td><a rel=\"nofollow\" href=\"index.php?$url_param\">$benchmark</a></td>");
        $pretty_score = $metric == 'bleu' ? sprintf('%4.1f',$score) : sprintf('%5.3f',$score);
        echo("<td>$pretty_score</td>");
        $url_param = make_query(['model' => $modelid,
                                 'pkg' => $pkg,
                                 'test' => $benchmark,
                                 'langpair' => $langpair,
                                 'start' => 0, 'end' => 9 ]);
        $show_translations_link = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";
        echo("<td>$show_translations_link</td>");
        echo("<td>$model_scores_link</td><td>$modelsize</td><td>$model_download_link</td></tr>");
        $count++;
    }
    echo('</table>');
}


function print_modelscore_table(&$scores, $model,$langpair='all',$benchmark='all', $pkg='opusmt',$metric='all'){
    global $table_max_scores, $multilingual_model;

    $logfiles = read_logfile_list($model, $pkg);

    echo("<h3>Model Scores (selected model)</h3>");
    if (count($scores) > $table_max_scores){
        echo "<p>There are ".count($scores)." $metric scores for this model. Show max $table_max_scores!</p>";
    }

    echo('<table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark</th><th>Output</th><th>$metric</th></tr>");
    $id = 0;
    $langlinks = array();
    $avg = 0;

    foreach ($scores as $key => $score){
        if ($id > $table_max_scores){
            break;
        }
        list($test,$lang) = explode('/',$key);
        if (array_key_exists($lang,$langlinks)){
            $langlink = $langlinks[$lang];
        }
        else{
            $query = make_query(['scoreslang' => $lang]);
            $langlink = "<a rel=\"nofollow\" href=\"index.php?$query\">$lang</a>";
            $langlinks[$lang] = $langlink;
        }
        
        $url_param = make_query(['test' => $test,'langpair' => $lang, 'start' => 0, 'end' => 9]);
        $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";

        $url_param = make_query(['test' => $test]);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$test</a>";

        $logfile = implode('.',[$test,$lang,'log']);
        $loglink = '';
        if (in_array($logfile, $logfiles)){
            $url_param = make_query(['test' => $test,'langpair' => $lang]);
            $loglink = "(<a rel=\"nofollow\" href=\"logfile.php?".SID.'&'.$url_param."\">logfile</a>)";
        }

        echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink $loglink</td><td>$score</td></td></tr>");
        $avg += $score;
        $id++;
    }

    if ($id > 0){
        $avg /= $id;
        $avg = sprintf('%5.3f',$avg);
    }

    $langlink = '';
    $testlink = '';
    if ($multilingual_model && $langpair != 'all'){
        $url_param = make_query(['scoreslang' => 'all']);
        $langlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    if ($benchmark != 'all'){
        $url_param = make_query(['test' => 'all']);
        $testlink = "<a rel=\"nofollow\" href=\"index.php?".$url_param."\">show all</a>";
    }
    echo("<tr><th></th><th>$langlink</th><th>$testlink</th><th>average</th><th>$avg</th></th></tr>");
    echo('</table>');
}


function print_testscores_table(&$scores, $langpair, $benchmark='avg', $metric='bleu', $pkg='opusmt', $model='top'){
    global $storage_urls, $userscores, $averaged_benchmarks;;

    // $scores = get_benchmark_scores($langpair, $benchmark, $metric, $pkg, $model);
    
    if (count($scores) == 0){
        echo("<h3>No model scores found</h3>");
        return;
    }
    if ($benchmark == 'avg'){
        echo("<h3>Model Scores (averaged over $averaged_benchmarks testsets)</h3>");
    }
    else{
        echo("<h3>Model Scores ($metric scores on the \"$benchmark\" testset)</h3>");
    }
    
    echo('<table><tr><th>ID</th>');
    if ( $benchmark == 'avg'){
        echo("<th>$metric</th><th>Model</th><th>Size</th><th>Link</th></tr>");
    }
    else{
        echo("<th>$metric</th><th>Output</th><th>Model</th><th>Size</th><th>Link</th></tr>");
    }
    
    $count=0;
    $id = sizeof($scores);

    // $modelpkg = $pkg;
    foreach ($scores as $modelid => $score){
        $id--;
        list($modelpkg,$modelid) = explode("\t",$modelid);
        $modelurl = modelid_to_url($modelid,$modelpkg);
        $modelsize = model_size($modelpkg,$modelid);
        $modelsize = $modelsize > 0 ? $modelsize.'M' : '?';
        
        // remove extension .zip if it exists
        if (substr($modelurl, -4) == '.zip'){
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">zip-file</a>";
        }
        else{
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">URL</a>";
        }                
        $url_param = make_query(['model' => $modelid, 'pkg' => $modelpkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">scores</a>";
        $modelshort = short_model_name($modelid);
        $model_scores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$modelshort</a>";
	
        echo("<tr><td>$id</td>");
        $pretty_score = $metric == 'bleu' ? sprintf('%4.1f',$score) : sprintf('%5.3f',$score);
        echo("<td>$pretty_score</td>");
        if ( $benchmark != 'avg'){
            $url_param = make_query(['model' => $modelid,
                                     'pkg' => $modelpkg,
                                     'test' => $benchmark,
                                     'langpair' => $langpair,
                                     'start' => 0, 'end' => 9 ]);
            $show_translations_link = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";
            echo("<td>$show_translations_link</td>");
        }
        echo("<td>$model_scores_link</td><td>$modelsize</td><td>$model_download_link</td></tr>");
        $count++;
    }
    echo('</table>');

}




// print a table with all scores and score differences

function print_modelscore_differences_table(&$scores, $langpair='all',$benchmark='all', $metric='bleu'){
    global $table_max_scores;
    
    // get all common lang-pairs and testsets
    // (that's for showing a link to expand the list)
    
    foreach($scores[0] as $key => $score1) {
        list($test, $lang) = explode('/',$key);
        if (array_key_exists($key,$scores[1])){
            if (! isset($common_langs[$lang])) $common_langs[$lang] = 0;
            if (! isset($common_tests[$test])) $common_tests[$test] = 0;

            $common_tests[$test]++;
            $common_langs[$lang]++;
        }
    }

    
    $avg_score1 = 0;
    $avg_score2 = 0;
    $count_scores1 = 0;
    $count_scores2 = 0;

    $lang_query = array();
    $test_query = array();
    
    echo('<div id="scores"><div class="query"><table>');
    echo("<tr><th>ID</th><th>Language</th><th>Benchmark ($metric)</th><th>Output</th><th>Model 1</th><th>Model 2</th><th>Diff</th></tr>");

    $id = 0;
    foreach($scores[0] as $key => $score1) {
        if ($id > $table_max_scores){
            echo('<tr><td>');
            echo(implode('</td><td>',['...', '...', '...', '...', '...', '...', '...']));
            echo('</td></tr>');
            break;
        }
        if (array_key_exists($key,$scores[1])){
            list($test, $lang) = explode('/',$key);
            
            if ($langpair!='all' && $lang!=$langpair){
                continue;
            }
            if ($benchmark!='all' && $test!=$benchmark){
                continue;
            }
            
            $score2 = $scores[1][$key];
            $diff = $score1 - $score2;
            $diff_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff) : sprintf('%5.3f',$diff);
            
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
        if (sizeof($common_tests) > 1){
            $url_param = make_query(['test' => 'all']);
            $testlink = "<a rel=\"nofollow\" href=\"compare.php?".$url_param."\">show all</a>";
        }
    }
    echo("<tr><th></th><th>$langlink</th><th>$testlink</th><th>average</th><th>$avg1</th><th>$avg2</th><th>$diff</th></tr>");

    echo('</table></div></div>');
    return $common_langs;
}







////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////


function get_langpair_scores(&$scores, &$trglangs, &$benchmarks, $model, $pkg='opusmt',
                            $metric='bleu', $benchmark='all', $source='unchanged'){
    global $opusmt;

    $counts = array();
    $max = 0;

    $data = $opusmt->get_model_scores($model, $metric, $pkg);
    foreach ($data as $key => $score){
        list($b,$p) = explode('/',$key);
        if (! isset($benchmarks[$b])) $benchmarks[$b] = 0;
        $benchmarks[$b]++;
        if (($benchmark == 'all') or ($b == $benchmark)){
            list($s,$t) = explode('-',$p);
            
            if (! array_key_exists($s,$scores)) $scores[$s] = array( $t => 0 );
            if (! array_key_exists($s,$counts)) $counts[$s] = array( $t => 0 );
            if (! isset($scores[$s][$t])) $scores[$s][$t] = 0;
            if (! isset($counts[$s][$t])) $counts[$s][$t] = 0;
            if (! isset($trglangs[$t])) $trglangs[$t] = 0;
                        
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

    foreach ($scores as $s => $targets){
        foreach ($targets as $t => $score){
            if ($counts[$s][$t] > 1){
                $scores[$s][$t] /= $counts[$s][$t];
            }
            if ($scores[$s][$t] > $max){
                $max = $scores[$s][$t];
            }
        }
    }
    return $max;
}

function read_logfile_list($model, $pkg='opusmt'){
    
    global $leaderboard_dirs, $leaderboard_urls;
    $file = implode('/',[$leaderboard_dirs[$pkg],'models',$model]).'.logfiles';
    if (! file_exists($file)){
        $file  = implode('/',[$leaderboard_urls[$pkg],'models',$model]).'.logfiles';
    }
    return array_map('rtrim', read_file_with_cache($file));
}




function print_langpair_diffmap($model1, $model2, $metric='bleu', $benchmark='all',
                                $pkg1='opusmt', $pkg2='opusmt', $source='unchanged'){

    $scores1 = array();
    $scores2 = array();
    $trglangs1 = array();
    $trglangs2 = array();
    $benchmarks = array();

    get_langpair_scores($scores1, $trglangs1, $benchmarks, $model1, $pkg1, $metric, $benchmark, $source);
    get_langpair_scores($scores2, $trglangs2, $benchmarks, $model2, $pkg2, $metric, $benchmark, $source);

    echo('<ul><li><b>Selected Benchmark:</b> ');
    if ( $benchmark == 'all' ){
        echo("[avg] ");
    }
    else{
        $url_param = make_query(['test' => 'all']);
        echo("[<a rel=\"nofollow\" href=\"compare.php?$url_param\">avg</a>] ");
    }
    foreach ($benchmarks as $b => $count){
        if ($count > 3){
            if ( $b == $benchmark ){
                echo("[$b] ");
            }
            else{
                $url_param = make_query(['test' => $b]);
                echo("[<a rel=\"nofollow\" href=\"compare.php?$url_param\">$b</a>] ");
            }
        }
    }
    echo("<li>Scores = difference in $metric scores between model 1 and model 2</li>");
    echo('</li></ul>');
        

    // get score difference
    
    $score_diff = array();
    $trglangs = array();
    $max = 0;
    $min = 0;
    foreach ($scores1 as $s => $targets){
        foreach ($targets as $t => $score){
            // $score_diff[$s][$t] = $score;
            if (array_key_exists($s,$scores2)){
                if (array_key_exists($t,$scores2[$s])){
                    $score_diff[$s][$t] = $score - $scores2[$s][$t];
                    $trglangs[$t]=1;
                    if ($score_diff[$s][$t] > $max){
                        $max = $score_diff[$s][$t];
                    }
                    if ($score_diff[$s][$t] < $min){
                        $min = $score_diff[$s][$t];
                    }
                }
            }
        }
    }

    $show_trglangs = true;
    $show_scores = true;

    ksort($trglangs);
    echo('<br/><div class="heatmap"><table><tr><th></th>');
    if ($show_trglangs){
        foreach ($trglangs as $t => $count){
            echo('<th>'.$t.'</th>');
        }
    }
    echo('</tr>');


    ksort($score_diff);
    foreach ($score_diff as $s => $tab){
        echo('<th>'.$s.'</th>');
        foreach ($trglangs as $t => $count){
            if (array_key_exists($t,$tab)){
                if ($show_scores){
                    $score = sprintf('%4.1f',$tab[$t]);
                    if ($benchmark != 'all'){
                        $query = make_query(['test' => $benchmark,
                                             'langpair' => "$s-$t",
                                             'start' => 0, 'end' => 9]);
                        $translink = "<a rel=\"nofollow\" href=\"compare-translations.php?".SID.'&'.$query."\">";
                        echo('<td bgcolor="'.scorediff_color($score, $max, $min).'">'.$translink.$score.'</a></td>');
                    }
                    else{
                        echo('<td bgcolor="'.scorediff_color($score, $max, $min).'">'.$score.'</td>');
                    }
                }
                else{
                    echo('<td bgcolor="'.scorediff_color($tab[$t], $max, $min).'"></td>');
                }
            }
            elseif ($s == $t){
                if ($show_trglangs){
                    echo('<th>'.$s.'</th>');
                }
                else{
                    echo('<td></td>');
                }
            }
            else{
                echo('<td></td>');
            }
        }
        echo('</tr>');
    }
    echo('</tr></table></div><br/>');
    // print_legend();
    return true;
}


function print_langpair_heatmap($model, $metric='bleu', $benchmark='all', $pkg='opusmt', $source='unchanged'){
    
    $scores = array();
    $trglangs = array();
    $benchmarks = array();

    get_langpair_scores($scores, $trglangs, $benchmarks, $model, $pkg, $metric, $benchmark, $source);

    echo('<ul><li>');
    if (count($benchmarks) > 1){
        echo('<b>Selected Benchmark:</b> ');
        if ( $benchmark == 'all' ){
            echo("[avg] ");
        }
        else{
            $url_param = make_query(['test' => 'all']);
            echo("[<a rel=\"nofollow\" href=\"index.php?$url_param\">avg</a>] ");
        }
        foreach ($benchmarks as $b => $count){
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
    }
    echo('<li>Scores shown in percentage points</li>');
    echo('</li></ul>');    
        
    ksort($trglangs);
    echo('<div class="heatmap"><table><tr><th></th>');
    foreach ($trglangs as $t => $count){
        echo('<th>'.$t.'</th>');
    }
    echo('</tr>');
    
    ksort($scores);
    foreach ($scores as $s => $tab){
        echo('<th>'.$s.'</th>');
        foreach ($trglangs as $t => $count){
            if (array_key_exists($t,$tab)){
                $score = sprintf('%4.1f',$tab[$t]);
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
            elseif ($s == $t){
                echo('<th>'.$s.'</th>');
            }
            else{
                echo('<td></td>');
            }
        }
        echo('</tr>');
    }
    echo('</tr></table></div>');
    // echo('<br/>');
    print_legend();
    return true;
}




function print_benchmark_heatmap($model, $metric='bleu', $benchmark='flores200-devtest', $package='opusmt', $chart='score'){
    global $opusmt;
    
    $scores = array();
    $trglangs = array();
    $benchmarks = array();

    $scores = array();
    $models = array();
    
    if ($chart == 'diff'){
        list($opus_scores,$opus_models) = $opusmt->get_benchmark_topscores($model, $benchmark, $metric, 'opusmt');
        list($external_scores,$external_models) = $opusmt->get_benchmark_topscores($model, $benchmark, $metric, 'external');
        $minscore=0;
        $maxscore=0;
        foreach ($opus_scores as $langpair => $score){
            $diff = array_key_exists($langpair, $external_scores) ? $score - $external_scores[$langpair] : $score;
            if ($diff < 0){
                $scores['external'][$langpair] = $diff;
                $models['external'][$langpair] = $external_models[$langpair];
                if ($diff < $minscore) $minscore = $diff;
            }
            else{
                $scores['opusmt'][$langpair] = $diff;
                $models['opusmt'][$langpair] = $opus_models[$langpair];
                if ($diff > $maxscore) $maxscore = $diff;
            }
        }
        foreach ($external_scores as $langpair => $score){
            if ( ! array_key_exists($langpair, $scores['opusmt']) ){
                $diff = 0 - $score;
                $scores['external'][$langpair] = $diff;
                $models['external'][$langpair] = $external_models[$langpair];
                if ($diff < $minscore) $minscore = $diff;
            }
        }
    }
    elseif ($package == 'all' || $model == 'top'){
        list($scores['opusmt'],$models['opusmt']) = $opusmt->get_benchmark_topscores($model, $benchmark, $metric, 'opusmt');
        list($scores['external'],$models['external']) = $opusmt->get_benchmark_topscores($model, $benchmark, $metric, 'external');
    }
    else{
        list($scores[$package],$models[$package]) = $opusmt->get_benchmark_topscores($model, $benchmark, $metric, $package);
    }

    $score_table = array();
    $model_table = array();
    $pkg_table = array();
    $trglangs = array();
    $model_size = array();
    
    foreach ($scores as $pkg => $pkg_scores){
        foreach ($pkg_scores as $langpair => $score){
 
            list($src,$trg) = explode('-',$langpair);
            $value = ($metric == 'bleu' or $metric == 'spbleu') ? $score : 100*$score;
            $trglangs[$trg] = 1;
            
            if (array_key_exists($src,$score_table)){
                if (array_key_exists($trg,$score_table[$src])){
                    if ($value > $score_table[$src][$trg]){
                        $score_table[$src][$trg] = $value;
                        $pkg_table[$src][$trg] = $pkg;
                        $model_table[$src][$trg] = $models[$pkg][$langpair];
                        $model_size[$pkg][$models[$pkg][$langpair]] = 1;
                    }
                    continue;
                }
            }
            $score_table[$src][$trg] = $value;
            $pkg_table[$src][$trg] = $pkg;
            $model_table[$src][$trg] = $models[$pkg][$langpair];
            $model_size[$pkg][$models[$pkg][$langpair]] = 1;
        }
    }


    if ($chart == 'size'){
        foreach ($model_size as $pkg => $list){
            foreach ($list as $model => $size){
                $model_size[$pkg][$model] =  model_size($pkg,$model);
                // echo "$model -- ".$model_size[$pkg][$model]."</br>";
            }
        }
    }
    
    ksort($trglangs);
    echo('<div class="heatmap"><table><tr><th></th>');
    foreach ($trglangs as $t => $count){
        echo('<th>'.$t.'</th>');
    }
    echo('</tr>');
    
    ksort($score_table);
    foreach ($score_table as $s => $tab){
        echo('<th>'.$s.'</th>');
        foreach ($trglangs as $t => $count){
            if (array_key_exists($t,$tab)){
                $pkg = $pkg_table[$s][$t];
                $model = $model_table[$s][$t];
                $score = sprintf('%4.1f',$tab[$t]);
                $query = make_query(['test' => $benchmark,
                                     'pkg' => $pkg,
                                     'model' => $model,
                                     'langpair' => "$s-$t",
                                     'start' => 0, 'end' => 9]);
                $translink = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$query."\">";
                if ($chart == 'size'){
                    $size = $model_size[$pkg][$model];
                    $color = size_color($size);
                }
                elseif ($chart == 'type'){
                    $color = collection_color($pkg, $model);
                }
                elseif ($chart == 'diff'){
                    // $color = scorediff_color($score, 100, -100);
                    $color = scorediff_color($score, $maxscore, $minscore);
                }
                else{
                    $color = score_color($score);
                }
                echo('<td bgcolor="'.$color.'">'.$translink.$score.'</a></td>');
            }
            elseif ($s == $t){
                echo('<th>'.$s.'</th>');
            }
            else{
                echo('<td></td>');
            }
        }
        echo('</tr>');
    }
    echo('</tr></table></div>');
    if ($chart == 'size'){
        print_size_legend();
    }
    elseif ($chart == 'score'){
        print_legend();
    }
    return true;
}




function scorediff_color($score, $max, $min){

    $red = 255;
    $green = 255;
    $blue = 255;

    // have the same scale in both, negative and positive scores
    if (abs($min) > $max){$max = abs($min);}
    if (abs($min) < $max){$min = 0-abs($max);}

    // score < 0 --> red
    if ($score < 0){
        if ($min < 0){
            $x = 255 - floor(255*$score/$min);
            $green = $x;
            $blue = $x;
        }
    }
    // score > 0 --> blue
    else{
        if ($max > 0){
            $x = 255 - floor(255*$score/$max);
            $green = $x;
            $red = $x;
        }
    }

    return sprintf("#%02x%02x%02x",$red,$green,$blue);
}


function collection_color($pkg, $model){
    /*
    $collection_colors = array(
        'opusmt' => [47, 133, 217],
        'external' => [164, 164, 164],
        'students' => [47, 196, 47],
        'Tatoeba-MT-models' => [47, 133, 217],
        'OPUS-MT-models' => [217, 133, 47],
        'contributed' => [133, 133, 164],
        'HPLT-MT-models' => [196, 28, 42]);
    */

    $collection_colors = array(
        'opusmt' => [77, 163, 247],
        'external' => [194, 194, 194],
        'students' => [77, 226, 77],
        'Tatoeba-MT-models' => [77, 163, 247],
        'OPUS-MT-models' => [247, 163, 77],
        'contributed' => [163, 163, 194],
        'HPLT-MT-models' => [246, 78, 92]);

    
    if ($pkg == 'opusmt'){
        $parts = explode('/',$model);
        if (array_key_exists($parts[0],$collection_colors)){
            list($red, $green, $blue) = $collection_colors[$parts[0]];
            return sprintf("#%02x%02x%02x",$red,$green,$blue);    
        }
    }

    list($red, $green, $blue) = $collection_colors[$pkg];
    return sprintf("#%02x%02x%02x",$red,$green,$blue);    
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
    return sprintf("#%02x%02x%02x",$red,$green,$blue);
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

function print_size_legend(){
  echo '<br/><div class="heatmap">';
  echo '<br/>';
  echo '<table><tr><td>color: </td>';
  for ($size=24; $size<1200; $size+=ceil($size*0.5)){
    echo '<td bgcolor="'.size_color($size).'">&nbsp;&nbsp;&nbsp;</td>';
  }
  echo '<td bgcolor="'.size_color(0).'">&nbsp;&nbsp;&nbsp;</td>';  
  echo '</tr><tr><td>#params: </td>';
  for ($size=24; $size<1200; $size+=ceil($size*0.5)){
    echo '<td>'.$size.'M</td>';
  }
  echo '<td>unknown</td>';
  echo '</tr></table>';
  echo '</div>';
}


?>
