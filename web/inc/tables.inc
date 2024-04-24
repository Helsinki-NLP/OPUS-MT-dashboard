<?php

function get_langpair_scores(&$scores, &$trglangs, &$benchmarks, $model, $pkg='opusmt',
                            $metric='bleu', $benchmark='all', $source='unchanged'){

    $counts = array();
    $max = 0;
    $lines = read_model_scores('all', $benchmark, $metric, $model, $pkg, $source);
    foreach ($lines as $line){
        list($p,$b,$score) = explode("\t",rtrim(array_shift($lines)));
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


function print_model_scores($model,$langpair='all',$benchmark='all', $pkg='opusmt',$metric='all'){
    global $storage_urls, $table_max_scores;

    // echo(get_score_filename($langpair, 'all', $metric, $model, $pkg));
    $lines = read_scores($langpair, 'all', $metric, $model, $pkg);
    $logfiles = read_logfile_list($model, $pkg);

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

        $logfile = implode('.',[$parts[1],$parts[0],'log']);
        $loglink = '';
        if (in_array($logfile, $logfiles)){
            $url_param = make_query(['test' => $parts[1],'langpair' => $parts[0]]);
            $loglink = "(<a rel=\"nofollow\" href=\"logfile.php?".SID.'&'.$url_param."\">logfile</a>)";
        }

        echo("<tr><td>$id</td><td>$langlink</td><td>$testlink</td><td>$translink $loglink</td><td>$parts[2]</td></td></tr>");
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


function print_scores($model='all', $langpair='all', $benchmark='all', $pkg='opusmt', $metric='bleu', $source='unchanged'){
    global $storage_urls;

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
        echo("<th>$metric</th><th>Model</th><th>Size</th><th>Link</th></tr>");
    }
    else{
        echo("<th>$metric</th><th>Output</th><th>Model</th><th>Size</th><th>Link</th></tr>");
    }
    
    $count=0;
    foreach ($lines as $line){
        $id--;
        $parts = explode("\t",rtrim($line));
        $test = $benchmark == 'all' ? array_shift($parts) : $benchmark;
        list($model, $modelurl) = normalize_modelname($parts[1]);
        $modelpkg = $parts[2];
        $modelsize = model_size($modelpkg,$model);
        $modelsize = $modelsize > 0 ? $modelsize.'M' : '?';
        
        // remove extension .zip if it exists
        if (substr($modelurl, -4) == '.zip'){
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">zip-file</a>";
        }
        else{
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">URL</a>";
        }

        $eval_file_url = $storage_urls[$pkg].'/models/'.$model.'.eval.zip';
        $eval_download_link = "<a rel=\"nofollow\" href=\"$eval_file_url\">evaluations</a>";
                
        $url_param = make_query(['model' => $model, 'pkg' => $modelpkg, 'scoreslang' => $langpair, 'test' => 'all' ]);
        $scoreslink = "<a rel=\"nofollow\" href=\"index.php?$url_param\">scores</a>";
        $modelshort = short_model_name($model);
        $model_scores_link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$modelshort</a>";
	
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
            $url_param = make_query(['model' => $model,
                                     'pkg' => $modelpkg,
                                     'test' => $test,
                                     'langpair' => $langpair,
                                     'start' => 0, 'end' => 9 ]);
            $show_translations_link = "<a rel=\"nofollow\" href=\"translations.php?".SID.'&'.$url_param."\">show</a>";
            echo("<td>$show_translations_link</td>");
        }
        // echo("<td>$model_download_link, $eval_download_link</td><td>$model_scores_link</td></tr>");
        echo("<td>$model_scores_link</td><td>$modelsize</td><td>$model_download_link</td></tr>");
        $count++;
    }
    echo('</table>');
}


// print a table with all scores and score differences

function print_score_diffs($model1,$model2,$langpair='all',$benchmark='all', $metric='bleu'){
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
        
        if (! isset($langpairs[$array[0]])) $langpairs[$array[0]] = 0;
        if (! isset($testsets[$array[1]]))  $testsets[$array[1]] = 0;

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
            if (! isset($common_langs[$array[0]])) $common_langs[$array[0]] = 0;
            $common_langs[$array[0]]++;
        }
        if (array_key_exists($array[1],$testsets)){
            if (! isset($common_tests[$array[1]])) $common_tests[$array[1]] = 0;
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
            $test_query = array();
            
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


// print a table with all scores and score differences

function print_topscore_differences($langpair='deu-eng', $benchmark='all', $metric='bleu', $contributed='no'){
    global $chart;

    // don't print contributed model scores 
    // if the chart shows score differences between OPUS-MT and external models
    if ($chart == "diff"){
        $contributed = 'no';
    }

    $lines1 = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'opusmt');
    $lines2 = read_scores($langpair, 'all', $metric, 'all', 'external', 'external');
    $lines3 = array();

    $scores1 = array();
    $model1 = array();
    $pkg1 = array();
    $modellinks = array();
    foreach($lines1 as $line1) {
        $array = explode("\t", rtrim($line1));
        $score = (float) $array[1];
        $key = $array[0];
        $scores1[$key] = $score;
        $pkg1[$key] = 'opusmt';
        list($model1[$key], $modelurl) = normalize_modelname($array[2]);
        // $model1[$key] = modelurl_to_model($array[2]);
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
            $pkg2[$key] = 'external';
            $model2[$key] = $array[2];
        }
    }

    if ($contributed == 'yes'){
        $lines3 = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'contributed');
        $scores3 = array();
        $model3 = array();
        $pkg3 = array();
        foreach($lines3 as $line3) {
            $array = explode("\t", rtrim($line3));
            if ($benchmark == 'all' || $benchmark == $array[0]){
                $key = $array[0];
                $score = (float) $array[1];
                $scores3[$key] = $score;
                $pkg3[$key] = 'contributed';
                $model3[$key] = $array[2];
            }
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
    if (count($lines3) == 0){
        $contributed == 'no';
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

    foreach($scores1 as $key => $score1) {
        if ($chart == "diff"){
            if (! array_key_exists($key,$scores2)){
                continue;
            }
        }
        if ($contributed == 'yes'){
            if (array_key_exists($key,$scores3)){
                $score3 = $scores3[$key];
                $diff3 = $score1 - $score3;
                $diff3_pretty = $metric == 'bleu' ? sprintf('%4.1f',$diff3) : sprintf('%5.3f',$diff3);
                $avg_score3 += $score3;
                $count_scores3++;
                $model3short = short_model_name($model3[$key]);
                $url_param = make_query(['model' => $model3[$key], 'pkg' => $pkg3[$key]]);
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
                $model1short = short_model_name($model1[$key]);
                
                $url_param = make_query(['model' => $model1[$key], 'pkg' => $pkg1[$key]]);
                $model1link = "<a rel=\"nofollow\" href=\"index.php?$url_param\">$model1short</a>";
                $query = make_query(['test' => $key, 'model' => "$pkg1[$key]/$model1[$key]", 'start' => 0, 'end' => 9]);
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




?>
