<?php

if (isset($_GET['session'])){
    if ($_GET['session'] == 'clear'){
        clear_session();
    }
    elseif ($_GET['session'] == 'refresh'){
        cleanup_cache();
    }
}


$package = get_param('pkg', 'opusmt');



// online file locations

$leaderboard_urls['opusmt']      = 'https://raw.githubusercontent.com/Helsinki-NLP/HPLT-MT-leaderboard/master';
$leaderboard_urls['external']    = 'https://raw.githubusercontent.com/Helsinki-NLP/External-MT-leaderboard/master';
$leaderboard_urls['contributed'] = 'https://raw.githubusercontent.com/Helsinki-NLP/Contributed-MT-leaderboard/master';

$storage_urls['opusmt']          = 'https://object.pouta.csc.fi/HPLT-MT-leaderboard';
$storage_urls['external']        = 'https://object.pouta.csc.fi/External-MT-leaderboard';
$storage_urls['contributed']     = 'https://object.pouta.csc.fi/Contributed-MT-leaderboard';

$testset_url                     = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-testsets/master';


// general locations for GitHub repos

$github_urls             = $leaderboard_urls;
$github_urls['testsets'] = $testset_url;


// for backwards compatibility

$leaderboard_urls['scores']          = $leaderboard_urls['opusmt'];
$leaderboard_urls['external-scores'] = $leaderboard_urls['external'];
$leaderboard_urls['user-scores']     = $leaderboard_urls['contributed'];

$storage_urls['scores']              = $storage_urls['opusmt'];
$storage_urls['external-scores']     = $storage_urls['external'];
$storage_urls['user-scores']         = $storage_urls['contributed'];


// local file locations

// $local_datahome       = '/media/OPUS';
// $local_datahome       = '/media/OPUS/dev';
$local_datahome       = '/media/OPUS-MT';

$leaderboard_dirs['opusmt']      = $local_datahome.'/HPLT-MT-leaderboard';
$leaderboard_dirs['external']    = $local_datahome.'/External-MT-leaderboard';
$leaderboard_dirs['contributed'] = $local_datahome.'/Contributed-MT-leaderboard';
$leaderboard_dirs['testsets']    = $local_datahome.'/OPUS-MT-testsets';

// for backwards compatibility

$leaderboard_dirs['scores']          = $leaderboard_dirs['opusmt'];
$leaderboard_dirs['external-scores'] = $leaderboard_dirs['external'];
$leaderboard_dirs['user-scores']     = $leaderboard_dirs['contributed'];

$testset_dir  = implode('/',[$leaderboard_dirs['contributed'],'OPUS-MT-testsets']);
$storage_dirs = $leaderboard_dirs;



$storage_url          = $storage_urls[$package];
$leaderboard_url      = $leaderboard_urls[$package];
$modelscores_url      = implode('/',[$leaderboard_url,'models']);
$scores_url           = implode('/',[$leaderboard_url,'scores']);


$storage_dir          = $storage_dirs[$package];
$leaderboard_dir      = $leaderboard_dirs[$package];
$modelscores_dir      = implode('/',[$leaderboard_dir,'models']);
$scores_dir           = implode('/',[$leaderboard_dir,'scores']);




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
        $trglang   = get_param('trg', 'ara');
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


function read_logfile_list($model, $pkg='opusmt'){
    
    global $leaderboard_dirs, $leaderboard_urls;
    $file = implode('/',[$leaderboard_dirs[$pkg],'models',$model]).'.logfiles';
    if (! file_exists($file)){
        $file  = implode('/',[$leaderboard_urls[$pkg],'models',$model]).'.logfiles';
    }
    return array_map('rtrim', read_file_with_cache($file));
}


// return file location (either local or online)

function get_file_location($file, $pkg='opusmt'){
    global $leaderboard_urls, $leaderboard_url;
    global $leaderboard_dirs, $leaderboard_dir;

    $base_dir = $leaderboard_dirs[$pkg];
    $localfile = implode('/',[$base_dir,$file]);
    if (file_exists($localfile)){
        return $localfile;
    }

    $base_url = $leaderboard_urls[$pkg];
    $url  = implode('/',[$base_url,$file]);
    return $url;
}



function get_score_filename($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged'){
    
    global $leaderboard_urls, $leaderboard_url;
    global $leaderboard_dirs, $leaderboard_dir;

    $base_url = $leaderboard_urls[$pkg];
    $base_dir = $leaderboard_dirs[$pkg];

    if ($model != 'all' && $model != 'avg'){
        if ($metric != 'all'){
            $url  = implode('/',[$base_url,'models',$model]).'.'.$metric.'-scores.txt';
            $file = implode('/',[$base_dir,'models',$model]).'.'.$metric.'-scores.txt';
        }
        else{
            $url  = implode('/',[$base_url,'models',$model]).'.scores.txt';
            $file = implode('/',[$base_dir,'models',$model]).'.scores.txt';
        }
    }
    elseif ($benchmark == 'avg'){
        $url  = implode('/',[$base_url,'scores',$langpair,'avg-'.$metric.'-scores.txt']);
        $file = implode('/',[$base_dir,'scores',$langpair,'avg-'.$metric.'-scores.txt']);
    }
    elseif ($benchmark != 'all'){
        $url  = implode('/',[$base_url,'scores',$langpair,$benchmark,$metric.'-scores.txt']);
        $file = implode('/',[$base_dir,'scores',$langpair,$benchmark,$metric.'-scores.txt']);
    }
    else{
        $url  = implode('/',[$base_url,'scores',$langpair,'top-'.$metric.'-scores.txt']);
        $file = implode('/',[$base_dir,'scores',$langpair,'top-'.$metric.'-scores.txt']);
    }
    if (file_exists($file)){
        return $file;
    }
    return $url;
}



function read_model_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged', $cache_size=10){
    global $userscores;
    
    if ($model == 'top' && $benchmark != 'all'){
        $lines1 = read_scores($langpair, $benchmark, $metric, 'all', 'opusmt', 'scores');
        $lines2 = read_scores($langpair, $benchmark, $metric, 'all', 'external', 'external-scores');
        $lines3 = array();
        if ($benchmark == 'avg'){
            $head1 = array_shift($lines1);
            $head2 = array_shift($lines2);
        }
        if ($userscores == "yes"){
            $lines3 = read_scores($langpair, $benchmark, $metric, 'all', 'contributed', 'user-scores');
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


function local_scorefile_exists($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged'){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);
    if (file_exists($file)){
        return true;
    }
    return false;
}

// read scores from session cache or from file

function read_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged', $cache_size=10){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);
    // echo(".... $file ....");

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
    $_SESSION['cached-scores'][$key] = $file;

    // read scores from the file
    // filter out some unwanted dev sets (TODO: can we skip that now?)
    // add the package as a last TAB-separated value (see substr_replace command)
    $_SESSION['scores'][$key] = substr_replace(filter_testsets(@file($file)),"\t".$pkg,-1,-1);
    
    $_SESSION['next-cache-key']++;
    if (is_array($_SESSION['scores'][$key])){
        return $_SESSION['scores'][$key];
    }
    return array();
}



// remove some test sets that we do not want to display
// - all newsdev sets
// - flores dev sets
// - other dev sets

function is_testset_line(string $line): bool {
    if (strpos($line, 'newsdev') !== false){ return false; }
    $arr = explode("\t",$line);
    if (in_array('flores101-dev',$arr)){ return false; }
    if (in_array('flores200-dev',$arr)){ return false; }
    if (in_array('wikipedia.dev',$arr)){ return false; }
    if (in_array('news2008',$arr)){ return false; }
    if (in_array('news-test2008',$arr)){ return false; }
    if (in_array('newsdiscussdev2015',$arr)){ return false; }
    return true;
};

function filter_testsets($array){
    if (is_array($array)){
        return array_filter($array,"is_testset_line");
    }
    return $array;
}

function get_testset_filename($testset, $langpair){
    if (array_key_exists('testset-files', $_SESSION)){
        if (array_key_exists($testset, $_SESSION['testset-files'])){
            if (array_key_exists($langpair, $_SESSION['testset-files'][$testset])){
                return $_SESSION['testset-files'][$testset][$langpair];
            }
        }
    }
    cache_testset_filenames($testset, $langpair);
    return $_SESSION['testset-files'][$testset][$langpair];
}

function get_reference_filenames($testset, $langpair){
    if (array_key_exists('reference-files', $_SESSION)){
        if (array_key_exists($testset, $_SESSION['reference-files'])){
            if (array_key_exists($langpair, $_SESSION['reference-files'][$testset])){
                return $_SESSION['reference-files'][$testset][$langpair];
            }
        }
    }
    cache_testset_filenames($testset, $langpair);
    return $_SESSION['reference-files'][$testset][$langpair];
}


// get testset filenames and put them into the session cache

function cache_testset_filenames($testset, $langpair){
    global $testset_url;
    
    $fp = @fopen(implode('/',[$testset_url,'testsets.tsv']), "r");
    if ($fp) {
        while (($line = fgets($fp, 4096)) !== false) {
            $fields = explode("\t",rtrim($line));
            if ($fields[2] == $testset){
                $lp=implode('-',[$fields[0],$fields[1]]);
                if ($lp == $langpair){
                    $_SESSION['testset-files'][$fields[2]][$lp] = $fields[6];
                    $_SESSION['reference-files'][$fields[2]][$lp] = array_slice($fields, 7);
                    break;
                }
            }
        }
        fclose($fp);
    }
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

function get_translation_file($model, $pkg='opusmt'){
    global $storage_urls, $storage_dirs;

    $url  = implode('/',[$storage_urls[$pkg],'models',$model]).'.eval.zip';
    $file = implode('/',[$storage_dirs[$pkg],'models',$model]).'.eval.zip';

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

function get_logfile_with_cache($model, $pkg='opusmt', $ext='.eval.zip', $cache_size=10){
    global $storage_urls, $storage_dirs;
    
    $url  = implode('/',[$storage_urls[$pkg],'models',$model]).$ext;
    $file = implode('/',[$storage_dirs[$pkg],'models',$model]).$ext;

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


// copy files to local tmpfiles

function get_file_with_cache($path, $pkg='opusmt', $cache_size=10){
    global $github_urls, $storage_dirs;
    
    $url  = implode('/',[$github_urls[$pkg],$path]);
    $file = implode('/',[$storage_dirs[$pkg],$path]);

    // local file exists? --> return it
    if (file_exists($file)){
        return $file;
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
                    // echo(".... found file $url as $key</br>");
                    return $_SESSION['files'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-filecache-key'] >= $cache_size){
        $_SESSION['next-filecache-key'] = 0;
    }

    $key = $_SESSION['next-filecache-key'];
    $_SESSION['cached-files'][$key] = $url;

    // delete old cached file
    if (array_key_exists('files', $_SESSION)){
        if (array_key_exists($key, $_SESSION['files'])){
            if (file_exists($_SESSION['files'][$key])){
                unlink($_SESSION['files'][$key]);
                // echo(".... remove ".$_SESSION['files'][$key]);
            }
        }
    }

    // create new cached file
    $tmpfile = tempnam(sys_get_temp_dir(),'opusmteval');
    if (copy($url, $tmpfile)) {
        // echo(".... pushed file $url as $key</br>");
        $_SESSION['files'][$key] = $tmpfile;
        $_SESSION['next-filecache-key']++;
        return $_SESSION['files'][$key];
    }
    unlink($tmpfile);
}



function clear_session(){
    cleanup_cache();
    $_SESSION = array();
}

function cleanup_cache(){
    if (isset($_SESSION['files'])){
        foreach ($_SESSION['files'] as $key => $file){
            if (is_string($file)){
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        $_SESSION['files'] = array();
    }
    system("find ".sys_get_temp_dir()." -maxdepth 1 -mtime +1 -type f -name 'opusmteval*' -delete");
}


function print_translation_logfile($benchmark, $langpair, $model, $pkg='opusmt'){
    
    $logfile = implode('.',[$benchmark, $langpair, 'log']);
    echo($logfile."\n");
    // $tmpfile = get_logfile_with_cache($model, $pkg);
    $tmpfile = get_logfile_with_cache($model, $pkg, '.log.zip');

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




/////// NEW: read selected lines from a file
/////// TODO: still reads sequentially through files
/////// --> should remember file positions

function selected_lines_from_file($file, $start=0, $end=-1){
    $output = array();
    $fp = @fopen($file, "r");
    if ($fp) {
        $count = 0;
        while (($line = fgets($fp, 4096)) !== false) {
            if ($count < $end){
                if ($count >= $start){
                    array_push($output,$line);
                }
            }
            else{
                break;
            }
            $count++;
        }
        fclose($fp);
    }
    return $output;
}


function get_system_translations($benchmark, $langpair, $model, $pkg='opusmt', $start=0, $end=-1){    
    $filename = implode('.',[$benchmark, $langpair, 'output']);
    $filepath = implode('/',['models',$model,$filename]);
    $file = get_file_with_cache($filepath,$pkg);
    return selected_lines_from_file($file, $start, $end);    
}

function get_testset_input($benchmark, $langpair, $start=0, $end=-1){
    $filepath = get_testset_filename($benchmark, $langpair);
    $file = get_file_with_cache($filepath,'testsets');
    return selected_lines_from_file($file, $start, $end);    
}

function get_testset_reference($benchmark, $langpair, $start=0, $end=-1){
    $files = get_reference_filenames($benchmark, $langpair);
    if (count($files) > 0){
        $file = get_file_with_cache($files[0],'testsets');
        return selected_lines_from_file($file, $start, $end);    
    }
}



/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////
///// TODO: the functions below put too much into the session variable!
/////       (read_file_with_cache fills up memory!)

function get_system_translations_from_cache($benchmark, $langpair, $model, $pkg='opusmt', $start=0, $end=-1){
    $filename = implode('.',[$benchmark, $langpair, 'output']);
    $file     = implode('/',['models',$model,$filename]);
    $filepath = get_file_location($file, $pkg);
    $output   = read_file_with_cache($filepath);
    if ($end > $start){
        $end = $end <= count($output) ? $end : count($output);
        return array_slice($output, $start, $end-$start+1);
    }
    return $output;
}

function get_testset_input_from_cache($benchmark, $langpair, $start=0, $end=-1){
    global $testset_url, $testset_dir;
    $file = get_testset_filename($benchmark, $langpair);
    $localfile = implode('/',[$testset_dir,$file]);
    if (file_exists($localfile)){
        $testset = read_file_with_cache($localfile);
    }
    else{
        $testset = read_file_with_cache(implode('/',[$testset_url,$file]));
    }
    if ($end > $start){
        $end = $end <= count($testset) ? $end : count($testset);
        return array_slice($testset, $start, $end-$start+1);
    }
    return $testset;
}

// returns only the first reference (in case multiple files exist)
function get_testset_reference_from_cache($benchmark, $langpair, $start=0, $end=-1){
    global $testset_url, $testset_dir;
    $files = get_reference_filenames($benchmark, $langpair);
    if (count($files) > 0){
        $localfile = implode('/',[$testset_dir,$files[0]]);
        if (file_exists($localfile)){
            $reference = read_file_with_cache($localfile);
        }
        else{
            $reference = read_file_with_cache(implode('/',[$testset_url,$files[0]]));
        }
        if ($end > $start){
            $end = $end <= count($reference) ? $end : count($reference);
            return array_slice($reference, $start, $end-$start+1);
        }
        return $reference;
    }
    return array();
}

/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////





function get_translations ($benchmark, $langpair, $model, $pkg='opusmt'){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_logfile_with_cache($model, $pkg);

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

function get_selected_translations ($benchmark, $langpair, $model, $pkg='opusmt', $start=0, $end=99){

    // read from compare-files in large zip archives
    // --> this is surprisingly fast and does not require a lot of memory as we don't cache anything
    // return get_examples_from_zip($benchmark, $langpair, $model, $pkg, $start, $end);

    // below would be the alternative of putting input, reference and system output together on the fly
    // --> this would use files from the repo and does not need to unpack from zip files
    // --> but it seems to be slower and requires much more memory at the moment
    //     because of all kinds of caching of files in session variables (we need to improve this!)
    return get_examples_from_cached_files($benchmark, $langpair, $model, $pkg, $start, $end);
}


// combine testsets and system output and return selected examples
// (range from start to end)

function get_examples_from_cached_files($benchmark, $langpair, $model, $pkg='opusmt', $start=0, $end=99){
    $system    = get_system_translations($benchmark, $langpair, $model, $pkg, $start, $end);
    $input     = get_testset_input($benchmark, $langpair, $start, $end);
    $reference = get_testset_reference($benchmark, $langpair, $start, $end);

    // make it compatible with content in compare files
    $examples = array();
    for ($i = 1; $i < count($input); $i++) {
        array_push($examples, rtrim($input[$i]), rtrim($reference[$i]), rtrim($system[$i]), '');        
    }
    return $examples;
}


// read only a certain slice of examples from compare-files in zip archives
// (assumes that the data is 4 lines per example)

function get_examples_from_zip($benchmark, $langpair, $model, $pkg='opusmt', $start=0, $end=99){
    
    $evalfile = implode('.',[$benchmark, $langpair, 'compare']);
    $tmpfile = get_logfile_with_cache($model, $pkg);

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

function short_model_name($modelid){
    $model = explode('/',$modelid);
    $provider = array_shift($model);
    $modelname = implode('/',$model);
    if ( strlen($modelname) > 24 ){
        $prefix = substr($modelname,0,12);
        $suffix = substr($modelname,-10,10);
        return $prefix.'..'.$suffix;
        // return $prefix.' .. '.$suffix;
    }
    return $modelname;
}


function modelurl_to_model($modelurl){
    if (substr($modelurl, -4) == '.zip'){
        $model = explode('/',$modelurl);
        $modelzip = array_pop($model);
        $modellang = array_pop($model);
        $modelpkg = array_pop($model);
        $modelzip = implode('/',[$modelpkg,$modellang,$modelzip]);
        $modelbase = substr($modelzip, 0, -4);
        return $modelbase;
    }
    if (substr($modelurl,0,27) == 'https://huggingface.co/HPLT'){
        $model = explode('/',$modelurl);
        $modelname = array_pop($model);
        $modelparts = explode('-',$modelname);
        $modellang = implode('-',[$modelparts[1],$modelparts[2]]);
        $modelbase = implode('/',['HPLT-MT-models',$modellang,$modelname]);
        return $modelbase;
    }   
    return $modelurl;
}


## this function takes care of the mess with model names in the dashboard and model URL's
## TODO: we should store internal model names and URL's consistently in the leaderboard files!

function normalize_modelname($modelstr){
    $modelparts = explode('/',$modelstr);
    if ($modelparts[0] == 'https:'){
        $modelurl = $modelstr;
        if ($modelparts[3] == 'HPLT'){
            $modelname = explode('-',$modelparts[4]);
            $modellang = implode('-',[$modelname[1],$modelname[2]]);
            $model = implode('/',['HPLT-MT-models',$modellang,$modelparts[4]]);
        }
        else{
            $model = implode('/',array_slice($modelparts, 3));
        }
    }
    elseif (substr($modelstr, -4) == '.zip'){
        $modelurl = implode('/',['https://object.pouta.csc.fi',$modelstr]);
        $model = $modelstr;
    }
    elseif ($modelparts[0] == 'huggingface'){
        array_shift($modelparts);
        $modelurl = implode('/',['https://huggingface.co',implode('/',$modelparts)]);
        $model = $modelstr;
    }
    elseif ($modelparts[0] == 'HPLT'){
        $modelname = explode('-',$modelparts[1]);
        $modellang = implode('-',[$modelname[1],$modelname[2]]);
        $model = implode('/',['HPLT-MT-models',$modellang,$modelparts[1]]);
        $modelurl = implode('/',['https://huggingface.co',implode('/',$modelparts)]);
    }
    else{
        $modelurl = implode('/',['https://huggingface.co',$modelstr]);
        $model = $modelstr;
    }
    
    if (substr($model, -4) == '.zip'){
        $model = substr($model, 0, -4);
    }
    return array($model,$modelurl);
}


function model_size($package, $model){
  if (array_key_exists('models', $_SESSION)){
      if (array_key_exists($package, $_SESSION['models'])){
	if (array_key_exists($model, $_SESSION['models'][$package])){
	  if (array_key_exists('size', $_SESSION['models'][$package][$model])){
	    return $_SESSION['models'][$package][$model]['size'];
	  }
	}
      }
  }
  
  // 0 means unknown
  $_SESSION['models'][$package][$model]['size'] = 0;

  $file = get_file_location('models/modelsize.txt', $package);
  if ($lines = @file($file)){
    foreach ($lines as $line){
      $val = explode("\t",$line);
      if ($val[1] == 'parameters'){
	$size = floatval($val[2]);
	$_SESSION['models'][$package][$val[0]]['size'] = $size;
      }
    }
  }
  return $_SESSION['models'][$package][$model]['size'];
}



function model_size_individual($package, $model){
  if (array_key_exists('models', $_SESSION)){
      if (array_key_exists($package, $_SESSION['models'])){
	if (array_key_exists($model, $_SESSION['models'][$package])){
	  if (array_key_exists('size', $_SESSION['models'][$package][$model])){
	    return $_SESSION['models'][$package][$model]['size'];
	  }
	}
      }
  }
  $file = get_file_location(implode('/',['models',$model.".info"]), $package);
  if ($lines = @file($file)){
    foreach ($lines as $line){
      $val = explode(': ',$line);
      if ($val[0] == 'parameters'){
	$size = floatval($val[1]);
	$_SESSION['models'][$package][$model]['size'] = $size;
	return $size;
      }
    }
  }
  // 0 means unknown
  $_SESSION['models'][$package][$model]['size'] = 0;
  return 0;
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
        // $modelurl = $parts[1];
        // $model = modelurl_to_model($modelurl);
        $modelpkg = $parts[2];
        $modelsize = model_size($modelpkg,$model);
        $modelsize = $modelsize > 0 ? $modelsize.'M' : '?';
        
        // remove extension .zip if it exists
        if (substr($modelurl, -4) == '.zip'){
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">zip-file</a>";
        }
        else{
            $model_download_link = "<a rel=\"nofollow\" href=\"$modelurl\">URL</a>";
            
            // TODO: we need a better solution to link to model data cards
            //       this is just a hack to link to huggingface models
            //   --> need to store model info and links somewhere!
            /*
            $array = explode('/', $modelurl);
            if ($array[0] == 'huggingface'){
                $array[0] = 'https://huggingface.co';
                $url = implode('/',$array);
                $model_download_link = "<a rel=\"nofollow\" href=\"$url\">URL</a>";
            }
            else{
                $model_download_link = '';
            }
            */
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


function print_langpair_heatmap($model, $metric='bleu', $benchmark='all', $pkg='opusmt', $source='unchanged'){
    $file = get_score_filename('all', $benchmark, $metric, $model, $pkg, $source);
    $lines = read_model_scores('all', $benchmark, $metric, $model, $pkg, $source);

    $scores = array();
    $trglangs = array();
    $counts = array();
    $benchmarks = array();
    
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


function size_to_rgb($size){
  
  if ($size <= 0){
    return [164, 164, 164];
  }

  $max = log(100);
  $size = $size < 24 ? 1 : $size-24;
  $logsize = log($size);

  $avg = 60;
  $good = 30;
  $norm = $logsize/$max*100;
  $norm = $norm > 100 ? 100 : $norm;
  $diff = $avg-$logsize/$max*100;

  $red=196;
  $green=196;
  $blue=196;

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
  $red < 0 ? $red = 0: $red;
  $green < 0 ? $green = 0: $green;
  $blue < 0 ? $blue = 0: $blue;

  return [floor($red),floor($green),floor($blue)];
}

function size_color($size){
  list($red,$green,$blue) = size_to_rgb($size);
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
