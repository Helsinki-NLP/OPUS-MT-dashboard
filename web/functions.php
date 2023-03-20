<?php

if (isset($_GET['session'])){
    if ($_GET['session'] == 'clear'){
        clear_session();
    }
}


$modelsource = get_param('modelsource', 'scores');


// online file locations

$leaderboard_url      = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master';
$modelscores_url      = implode('/',[$leaderboard_url,'models']);
$scores_url           = implode('/',[$leaderboard_url,$modelsource]);
$internal_scores_url  = implode('/',[$leaderboard_url,'scores']);
$external_scores_url  = implode('/',[$leaderboard_url,'external-scores']);
$storage_url          = 'https://object.pouta.csc.fi/OPUS-MT-leaderboard';


// local file locations

$leaderboard_dir      = '/media/OPUS/dev/OPUS-MT-leaderboard';
// $leaderboard_dir      = '/var/lib/OPUS-MT-leaderboard';
$modelscores_dir      = implode('/',[$leaderboard_dir,'models']);
$scores_dir           = implode('/',[$leaderboard_dir,$modelsource]);
$internal_scores_dir  = implode('/',[$leaderboard_dir,'scores']);
$external_scores_dir  = implode('/',[$leaderboard_dir,'external-scores']);
$storage_dir          = $leaderboard_dir;



// $evaluation_metrics = array('bleu', 'chrf');
// $evaluation_metrics = array('bleu', 'chrf', 'comet');
$evaluation_metrics = array('bleu', 'spbleu', 'chrf', 'chrf++', 'comet');

// $diffstyles = array('diff','wdiff','gitdiff');
$diffstyles = array('wdiff','gitdiff');


$show_max_scores = 50;
$chart_max_scores = $show_max_scores;
$table_max_scores = $show_max_scores;


function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function get_param($key, $default){

    // check the query string first and overwrite session variable
    if (isset($_GET[$key])){
        $_SESSION['params'][$key] = test_input($_GET[$key]);
        return $_SESSION['params'][$key];
    }
    
    if (array_key_exists('params', $_SESSION)){
        if (isset($_SESSION['params'][$key])){
            return $_SESSION['params'][$key];
        }
    }
    
    return $default;
}


function set_param($key, $value){
    $_SESSION['params'][$key] = $value;
}

function get_langpair(){
    if (isset($_GET['langpair'])){
        list($srclang,$trglang) = explode('-',$_GET['langpair']);
        $_SESSION['params']['src'] = $srclang;
        $_SESSION['params']['trg'] = $trglang;
    }
    else{
        $srclang   = get_param('src', 'eng');
        $trglang   = get_param('trg', 'fra');
    }
    $langpair  = implode('-',[$srclang,$trglang]);
    return [$srclang, $trglang, $langpair];
}

function set_langpair($langpair){
    list($srclang,$trglang) = explode('-',$langpair);
    $_SESSION['params']['src'] = $srclang;
    $_SESSION['params']['trg'] = $trglang;
    $_GET['src'] = $srclang;
    $_GET['trg'] = $trglang;
    $_GET['langpair'] = $langpair;
}

function make_query($data){
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        return http_build_query($data);
    }
    if (array_key_exists('params', $_SESSION)){
        $params = $_SESSION['params'];
    }
    else{
        $params = array();
    }
    foreach ($data as $key => $value){
        $params[$key] = $value;
    }
    return http_build_query($params);
}

function make_share_link(){
    if (array_key_exists('params', $_SESSION)){
        $params = $_SESSION['params'];
    }
    else{
        $params = array();
    }
    return http_build_query($params);
}


function read_logfile_list($model, $pkg='Tatoeba-MT-models'){
    
    global $modelscores_dir, $modelscores_url;
    $file = implode('/',[$modelscores_dir,$pkg,$model]).'.logfiles';
    if (! file_exists($file)){
        $file  = implode('/',[$modelscores_url,$pkg,$model]).'.logfiles';
    }
    return array_map('rtrim', read_file_with_cache($file));
}

function get_score_filename($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models', $source='unchanged'){
    
    global $leaderboard_url, $modelscores_url, $scores_url;
    global $leaderboard_dir, $modelscores_dir, $scores_dir;

    if ($source != 'unchanged'){
        $scorefile_url = implode('/',[$leaderboard_url,$source]);
        $scorefile_dir = implode('/',[$leaderboard_dir,$source]);
    }
    else{
        $scorefile_url = $scores_url;
        $scorefile_dir = $scores_dir;
    }

    if ($model != 'all' && $model != 'avg'){
        if ($metric != 'all'){
            $url  = implode('/',[$modelscores_url,$pkg,$model]).'.'.$metric.'-scores.txt';
            $file = implode('/',[$modelscores_dir,$pkg,$model]).'.'.$metric.'-scores.txt';
        }
        else{
            $url  = implode('/',[$modelscores_url,$pkg,$model]).'.scores.txt';
            $file = implode('/',[$modelscores_dir,$pkg,$model]).'.scores.txt';
        }
    }
    elseif ($benchmark == 'avg'){
        $url  = implode('/',[$scorefile_url,$langpair,'avg-'.$metric.'-scores.txt']);
        $file = implode('/',[$scorefile_dir,$langpair,'avg-'.$metric.'-scores.txt']);
    }
    elseif ($benchmark != 'all'){
        $url  = implode('/',[$scorefile_url,$langpair,$benchmark,$metric.'-scores.txt']);
        $file = implode('/',[$scorefile_dir,$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $url  = implode('/',[$scorefile_url,$langpair,'top-'.$metric.'-scores.txt']);
        $file = implode('/',[$scorefile_dir,$langpair,'top-'.$metric.'-scores.txt']);
    }
    if (file_exists($file)){
        return $file;
    }
    return $url;
}



function read_model_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models', $source='unchanged', $cache_size=10){
    global $userscores;
    
    if ($model == 'top' && $benchmark != 'all'){
        $lines1 = read_scores($langpair, $benchmark, $metric, 'all', $pkg, 'scores');
        $lines2 = read_scores($langpair, $benchmark, $metric, 'all', $pkg, 'external-scores');
        $lines3 = array();
        if ($benchmark == 'avg'){
            $head1 = array_shift($lines1);
            $head2 = array_shift($lines2);
        }
        if ($userscores == "yes"){
            $lines3 = read_scores($langpair, $benchmark, $metric, 'all', $pkg, 'user-scores');
            if ($benchmark == 'avg'){
                $head3 = array_shift($lines3);
            }
        }

        $lines = array_merge($lines1, $lines2, $lines3);
        // $lines = array_merge($lines1, $lines2);
        arsort($lines, SORT_NUMERIC);
        if ($benchmark == 'avg'){
            array_unshift($lines, $head1);
        }
    }
    else{
        $lines = read_scores($langpair, $benchmark, $metric, $model, $pkg, $source);
    }
    return $lines;
}


function local_scorefile_exists($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models', $source='unchanged'){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);
    if (file_exists($file)){
        return true;
    }
    return false;
}

// read scores from session cache or from file

function read_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='Tatoeba-MT-models', $source='unchanged', $cache_size=10){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);

    if (! array_key_exists('cached-scores', $_SESSION)){
        $_SESSION['cached-scores'] = array();
        $_SESSION['next-cache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-scores']);
    if ($key !== false){
        if (array_key_exists('scores', $_SESSION)){
            if (array_key_exists($key, $_SESSION['scores'])){
                // echo "read scores from cached file with key $key";
                if (is_array($_SESSION['scores'][$key])){
                    return $_SESSION['scores'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-cache-key'] >= $cache_size){
        $_SESSION['next-cache-key'] = 0;
    }

    $key = $_SESSION['next-cache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-scores'][$key] = $file;
    $_SESSION['scores'][$key] = @file($file);
    $_SESSION['next-cache-key']++;
    if (is_array($_SESSION['scores'][$key])){
        return $_SESSION['scores'][$key];
    }
    return array();
}



// generic function to read file with session cache

function read_file_with_cache($file, $cache_size=10){

    if (! array_key_exists('cached-files', $_SESSION)){
        $_SESSION['cached-files'] = array();
        $_SESSION['next-filecache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-files']);
    if ($key !== false){
        if (array_key_exists('files', $_SESSION)){
            if (array_key_exists($key, $_SESSION['files'])){
                // echo "read scores from cached file with key $key";
                if (is_array($_SESSION['files'][$key])){
                    return $_SESSION['files'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-filecache-key'] >= $cache_size){
        $_SESSION['next-filecache-key'] = 0;
    }

    $key = $_SESSION['next-filecache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-files'][$key] = $file;
    $_SESSION['files'][$key] = @file($file);
    $_SESSION['next-filecache-key']++;
    if (is_array($_SESSION['files'][$key])){
        return $_SESSION['files'][$key];
    }
    return array();
}




// fetch the file with all benchmark translations for a specific model

function get_translation_file($model, $pkg='Tatoeba-MT-models'){
    global $storage_url, $storage_dir;

    $url  = implode('/',[$storage_url,'models',$pkg,$model]).'.eval.zip';
    $file = implode('/',[$storage_dir,'models',$pkg,$model]).'.eval.zip';

    if (file_exists($file)){
        return $file;
    }
    $tmpfile = tempnam(sys_get_temp_dir(),'opusmteval');
    if (copy($url, $tmpfile)) {
        return $tmpfile;
    }
    unlink($tmpfile);
}


// fetch benchmark translation file and use a cache to keep a certain number
// of them without reloading them

function get_translation_file_with_cache($model, $pkg='Tatoeba-MT-models', $cache_size=10){
    global $storage_url, $storage_dir;
    
    $url  = implode('/',[$storage_url,'models',$pkg,$model]).'.eval.zip';
    $file = implode('/',[$storage_dir,'models',$pkg,$model]).'.eval.zip';

    // local file
    if (file_exists($file)){
        return $file;
    }
    
    // permanently cached files
    $tmpdir = sys_get_temp_dir();
    $filename = implode('/',[$tmpdir,$pkg,$model.'.eval.zip']);
    // echo("check for $filename");
    if (file_exists($filename)){
        // echo("found permanently cached file $filename");
        return $filename;
    }
    
    if (! array_key_exists('cached-files', $_SESSION)){
        $_SESSION['cached-files'] = array();
        $_SESSION['next-filecache-key'] = 0;
    }
    $key = array_search($url, $_SESSION['cached-files']);
    if ($key !== false){
        if (array_key_exists('files', $_SESSION)){
            if (array_key_exists($key, $_SESSION['files'])){
                if (file_exists($_SESSION['files'][$key])){
                    // echo "read translations from cache with key $key in file ".$_SESSION['files'][$key];
                    return $_SESSION['files'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-filecache-key'] >= $cache_size){
        $_SESSION['next-filecache-key'] = 0;
    }

    $key = $_SESSION['next-filecache-key'];
    // echo "save scores for $file in cache with key $key";
    $_SESSION['cached-files'][$key] = $url;
    
    $tmpfile = tempnam(sys_get_temp_dir(),'opusmteval');
    if (copy($url, $tmpfile)) {
        if (filesize($tmpfile) > 104857600){
            // echo("file size > 100MB -- put in permanent cache! ($filename)");
            $dir = dirname($filename);
            if (! file_exists($dir)){
                mkdir($dir,0777,true);
            }
            if (rename($tmpfile,$filename)){
                // echo("successfully created $filename");
                return $filename;
            }
        }
        $_SESSION['files'][$key] = $tmpfile;
        $_SESSION['next-filecache-key']++;
        return $_SESSION['files'][$key];
    }
    unlink($tmpfile);
}

function clear_session(){
    if (isset($_SESSION['files'])){
        foreach ($_SESSION['files'] as $key => $file){
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    cleanup_cache();
    $_SESSION = array();
}

function cleanup_cache(){
    system("find ".sys_get_temp_dir()." -maxdepth 1 -mtime +1 -type f -name 'opusmteval*' -delete");
}


function print_translation_logfile ($benchmark, $langpair, $model, $pkg='Tatoeba-MT-models'){
    
    $logfile = implode('.',[$benchmark, $langpair, 'log']);
    echo($logfile."\n");
    $tmpfile = get_translation_file_with_cache($model, $pkg);

    $zip = new ZipArchive;
    if ($zip->open($tmpfile) === TRUE) {
        if ($fp = $zip->getStream($logfile)){
            while (!feof($fp)) {
                echo(fread($fp, 8192));
            }
            fclose($fp);
        }
        $zip->close();
    }
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        unlink($tmpfile);
    }
}



function get_translations ($benchmark, $langpair, $model, $pkg='Tatoeba-MT-models'){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_translation_file_with_cache($model, $pkg);

    $zip = new ZipArchive;
    if ($zip->open($tmpfile) === TRUE) {
        $content = $zip->getFromName($evalfile);
        $zip->close();
    }
    
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        unlink($tmpfile);
    }    
    return $content;
}


// read only a certain slice of examples
// (assumes that the data is 4 lines per example)

function get_selected_translations ($benchmark, $langpair, $model, $pkg='Tatoeba-MT-models', $start=0, $end=99){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_translation_file_with_cache($model, $pkg);

    $examples = array();
    $count = 0;
    
    $zip = new ZipArchive;
    if ($zip->open($tmpfile) === TRUE) {
        if ($fp = $zip->getStream($evalfile)){
            $buffer = '';
            while (!feof($fp)) {
                $contents = fread($fp, 8192);
                $lines = explode("\n",$buffer.$contents);
                $buffer = array_pop($lines);
                foreach ($lines as $line){
                    array_push($examples, $line);
                }
                $count = floor(count($examples)/4);
                if ($count >= $end){
                    break;
                }
            }
            array_push($examples, $buffer);
            fclose($fp);
        }
        $zip->close();
    }
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        unlink($tmpfile);
    }
    return array_slice($examples, $start*4, ($end-$start+1)*4);
}

function show_page_links($start=0, $end=9, $nr_shown=10){

    $nr_examples = $end-$start+1;
    if ($start > 0){
        $newstart = $start-$nr_examples;
        if ($newstart < 0){
            $newstart = 0;
        }
        $newend = $newstart+$end-$start;
        $query = make_query(['start' => 0, 'end' => $nr_examples-1]);
        echo '[<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">start</a>] ';
        $query = make_query(['start' => $newstart, 'end' => $newend]);
        echo '[<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">show previous</a>] ';
    }
    echo 'show examples '.$start.' - '.$end;
    if ($nr_shown>$nr_examples){
        $newstart = $end+1;
        $newend = $end+$nr_examples;
        $query = make_query(['start' => $newstart, 'end' => $newend]);
        echo ' [<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">show next</a>]';
    }
}


function print_file_diff($file1, $file2, $diffstyle = 'wdiff'){
    
    // TODO: how safe is this?
    // TODO: ansi2html.sh should not be in this dir, should it?

    echo '<div class="f9 b9"><pre>';
    if ($diffstyle == 'gitdiff'){
        system("git diff --color-words --no-index  $file1 $file2 | tail -n +6 | sed 's/\@\@.*\@\@//' | ./ansi2html.sh --body-only");
    }
    elseif ($diffstyle == 'diff'){
        system("diff -u $file1 $file2 | colordiff | perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight | tail -n +4 | grep -v '\@\@.*\@\@' | ./ansi2html.sh --body-only");
    }
    else{
        system("wdiff $file1 $file2 | colordiff | perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight | ./ansi2html.sh --body-only");
    }
    echo '</pre></div>';
}

function print_chart_type_options($selected_type="standard"){
    $chart_types = array('standard', 'diff');    
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

function print_diffstyle_options($diffstyle='wdiff'){
    global $diffstyles;
    foreach ($diffstyles as $style){
        if ($style == $diffstyle){
            echo '['.$style.']';
        }
        else{
            $query = make_query(['diff' => $style]);
            echo '[<a rel="nofollow" href="'.$_SERVER['PHP_SELF'].'?'.$query.'">'.$style.'</a>]';
        }
    }
}

function print_style_options($style='light'){
    $styles    = array('light','dark');    
    foreach ($styles as $s){
        if ($s == $style){
            echo '['.$s.']';
        }
        else{
            $query = make_query(['style' => $s]);
            echo '[<a rel="nofollow" href="'.$_SERVER['PHP_SELF'].'?'.SID.'&'.$query.'">'.$s.'</a>]';
        }
    }
}

function short_model_name($modelname){
    if ( strlen($modelname) > 24 ){
        $prefix = substr($modelname,0,12);
        $suffix = substr($modelname,-10,10);
        return $prefix.'..'.$suffix;
        // return $prefix.' .. '.$suffix;
    }
    return $modelname;
}

function modelurl_to_model($modelurl){
    $model = explode('/',$modelurl);
    $modelzip = array_pop($model);
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
    }
    else{
        $modelbase = $modelzip;
    }
    return [$modelpkg, $modelbase];
}




function print_model_scores($model,$langpair='all',$benchmark='all', $pkg='Tatoeba-MT-models',$metric='all'){
    global $storage_url, $table_max_scores;

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


function print_scores($model='all', $langpair='all', $benchmark='all', $pkg='Tatoeba-MT-models', $metric='bleu', $source='unchanged'){
    global $storage_url, $modelsource;

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
        // echo("...$modelsource...");
        if ($model[0] == "unverified"){
            $modelpkg = array_shift($model);
            $modelzip = implode('/',$model).'/'.$modelzip;
        }
        elseif (count($model) > 4){
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





?>
