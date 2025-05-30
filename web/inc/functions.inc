<?php //-*-php-*-


// check whether the given model is a multilingual one
// TODO: this is a very simple check now whether the path is different from the current langpair
// --> should do something smarter than this

function is_multilingual_model($model){
    global $langpair;
    if ($model != 'all' && $model != 'top'){
        $parts = explode('/',$model);
        $modelfile = array_pop($parts);
        $modellang = array_pop($parts);

        if ($modellang != $langpair){
            return true;
        }
    }
    return false;
}


// return file location (either local or online)

function get_file_location($file, $pkg='opusmt'){
    global $leaderboard_urls, $leaderboard_url;
    global $leaderboard_dirs, $leaderboard_dir;

    $base_dir = array_key_exists($pkg, $leaderboard_dirs) ? $leaderboard_dirs[$pkg] : '';
    $localfile = implode('/',[$base_dir,$file]);
    if (file_exists($localfile)){
        return $localfile;
    }

    $base_url = array_key_exists($pkg, $leaderboard_urls) ? $leaderboard_urls[$pkg] : '';
    $url  = implode('/',[$base_url,$file]);
    return $url;
}

function local_scorefile_exists($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged'){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);
    if (file_exists($file)){
        return true;
    }
    return false;
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
        // $url  = implode('/',[$base_url,'scores',$langpair,'avg-'.$metric.'-scores.txt']);
        // $file = implode('/',[$base_dir,'scores',$langpair,'avg-'.$metric.'-scores.txt']);
        $url  = implode('/',[$base_url,'scores',$metric.'_scores.db']);
        $file = implode('/',[$base_dir,'scores',$metric.'_scores.db']);
    }
    elseif ($benchmark != 'all'){
        // $url  = implode('/',[$base_url,'scores',$langpair,$benchmark,$metric.'-scores.txt']);
        // $file = implode('/',[$base_dir,'scores',$langpair,$benchmark,$metric.'-scores.txt']);
        $url  = implode('/',[$base_url,'scores',$metric.'_scores.db']);
        $file = implode('/',[$base_dir,'scores',$metric.'_scores.db']);
    }
    else{
        // $url  = implode('/',[$base_url,'scores',$langpair,'top-'.$metric.'-scores.txt']);
        // $file = implode('/',[$base_dir,'scores',$langpair,'top-'.$metric.'-scores.txt']);
        $url  = implode('/',[$base_url,'scores',$metric.'_scores.db']);
        $file = implode('/',[$base_dir,'scores',$metric.'_scores.db']);
    }
    if (file_exists($file)){
        return $file;
    }
    return $url;
}



// generic function to read file with session cache

function read_file_with_cache($file, $cache_size=10){

    if (! array_key_exists('cached-files', $_SESSION)){
        $_SESSION['cached-files'] = array();
        $_SESSION['next-filecache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-files']);
    if ($key !== false){
        if (array_key_exists('file-content', $_SESSION)){
            if (array_key_exists($key, $_SESSION['file-content'])){
                // echo "read scores from cached file with key $key";
                if (is_array($_SESSION['file-content'][$key])){
                    return $_SESSION['file-content'][$key];
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
    $_SESSION['file-content'][$key] = @file($file);
    $_SESSION['next-filecache-key']++;
    if (is_array($_SESSION['file-content'][$key])){
        return $_SESSION['file-content'][$key];
    }
    return array();
}






// copy files to local tmpfiles

function get_file_with_cache($path, $pkg='opusmt', $cache_size=10){
    global $github_urls, $storage_dirs;

    if (! array_key_exists($pkg, $github_urls)) return;
    if (! array_key_exists($pkg, $storage_dirs)) return;
    // $url  = implode('/',[$github_urls[$pkg],$path]);
    
    $pathurl = implode('/',array_map('urlencode',explode('/',$path)));
    $url  = implode('/',[$github_urls[$pkg],$pathurl]);
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

function print_translation_logfile($benchmark, $langpair, $model, $pkg='opusmt'){
    
    $logfile = implode('.',[$benchmark, $langpair, 'log']);
    echo($logfile."\n");
    // $tmpfile = get_logfile_with_cache($model, $pkg);
    $tmpfile = get_logfile_with_cache($model, $pkg, '.log.zip');

    if ($tmpfile && file_exists($tmpfile)){
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
    }
    if ( ! isset( $_COOKIE['PHPSESSID'] ) ) {
        if (file_exists($tmpfile)){
            unlink($tmpfile);
        }
    }
}

function get_logfile_with_cache($model, $pkg='opusmt', $ext='.eval.zip', $cache_size=10){
    global $storage_urls, $storage_dirs;

    if (! array_key_exists($pkg, $storage_urls)) return;
    if (! array_key_exists($pkg, $storage_dirs)) return;
    
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





// an abbreviated model name for displaying in the table

function short_model_name($modelid, $maxlength=25, $prefix=12, $suffix=10){
    $model = explode('/',$modelid);
    $provider = array_shift($model);
    $modelname = implode('/',$model);
    if ( strlen($modelname) > $maxlength ){
        $prefix = substr($modelname,0,$prefix);
        $suffix = substr($modelname,0-$suffix,$suffix);
        return $prefix.'...'.$suffix;
        // return $prefix.' .. '.$suffix;
    }
    return $modelname;
}


// get the model from a model-URL
// TODO: avoid hard-coded decisions
// TOOD: deprecated because of normalize_modelname function below?

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


// map model-ID to download URL
// TODO: avoid hard-coded mapping 

function modelid_to_url($modelid, $package='opusmt'){
    $modelparts = explode('/',$modelid);
    if ($package == 'opusmt'){
        if ($modelparts[0] == 'HPLT-MT-models'){
            $modelname = array_pop($modelparts);
            return implode('/',['https://huggingface.co','HPLT',$modelname]);
        }
        return implode('/',['https://object.pouta.csc.fi',$modelid]).'.zip';
    }
    elseif ($modelparts[0] == 'huggingface'){
        array_shift($modelparts);
        return implode('/',['https://huggingface.co',implode('/',$modelparts)]);
    }
    return '';
}


// this function takes care of the mess with model names in the dashboard and model URL's
// TODO: we should store internal model names and URL's consistently in the leaderboard files!

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
    elseif ($modelparts[0] == 'HPLT-MT-models'){
        $modelurl = implode('/',['https://huggingface.co','HPLT',$modelparts[2]]);
        $model = $modelstr;
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


// retrieve information about the size of a model
// (in terms of parameters)

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


// TODO: where is this function used?

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



// rather complicated map to some color scale from model size

function size_to_rgb($size){
  
    if ($size <= 0){
        return [164, 164, 164];
    }
    if ($size > 1000){
        $size = 1000;
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


// convert size color for web-use

function size_color($size){
    list($red,$green,$blue) = size_to_rgb($size);
    return sprintf("#%x%x%x",$red,$green,$blue);
}



// return a color for a given model and model package

function model_color($package, $model){

    $type2color = array( 'contributed' => 'purple',
                         'external'    => 'grey',
                         'Tatoeba-MT-models' => 'blue',
                         'OPUS-MT-models' => 'orange',
                         'HPLT-MT-models'    => 'darkred');

    // return array_key_exists($package, $type2color) ? $type2color[$package] : 'grey';

    if ($package != 'opusmt'){
        return $type2color[$package];
    }
    elseif (strpos($model,'transformer-small') !== false){
        return 'green';
    }
    elseif (strpos($model,'transformer-tiny') !== false){
        return 'green';
    }
    else{
        list($modelid, $modelurl) = normalize_modelname($model);
        $modelparts = explode('/',$modelid);
        return array_key_exists($modelparts[0], $type2color) ? $type2color[$modelparts[0]] : 'grey';
    }
}





?>
