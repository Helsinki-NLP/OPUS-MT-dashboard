<?php

session_start();

if (isset($_GET['session'])){
    if ($_GET['session'] == 'clear'){
        clear_session();
    }
    elseif ($_GET['session'] == 'refresh'){
        cleanup_cache();
    }
}

// selected MT package and base leaderboard we will compare with

$package = get_param('pkg', 'opusmt');
$base_leaderboard = 'OPUS-MT-leaderboard';
$default_srclang = 'eng';
$default_trglang = 'fra';


// comment out below if you want to use a dashbard for HPLT models

/*
$base_leaderboard = 'HPLT-MT-leaderboard';
$default_srclang = 'eng';
$default_trglang = 'ara';
*/



// online file locations

$leaderboard_urls['opusmt']      = 'https://raw.githubusercontent.com/Helsinki-NLP/'.$base_leaderboard.'/master';
$leaderboard_urls['external']    = 'https://raw.githubusercontent.com/Helsinki-NLP/External-MT-leaderboard/master';
$leaderboard_urls['contributed'] = 'https://raw.githubusercontent.com/Helsinki-NLP/Contributed-MT-leaderboard/master';

$storage_urls['opusmt']          = 'https://object.pouta.csc.fi/'.$base_leaderboard;
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

$leaderboard_dirs['opusmt']      = $local_datahome.'/'.$base_leaderboard;
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

function get_langpair(){
    global $default_srclang, $default_trglang;
    if (isset($_GET['langpair'])){
        list($srclang,$trglang) = explode('-',$_GET['langpair']);
        $_SESSION['params']['src'] = $srclang;
        $_SESSION['params']['trg'] = $trglang;
    }
    else{
        $srclang   = get_param('src', $default_srclang);
        $trglang   = get_param('trg', $default_trglang);
    }
    $langpair  = implode('-',[$srclang,$trglang]);
    return [$srclang, $trglang, $langpair];
}

function set_param($key, $value){
    $_SESSION['params'][$key] = $value;
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

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
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


?>
