<?php
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
    global $diffhighlight;

    // $diffhighlight = '/opt/local/bin/diff-highlight';
    // $wdiff = '/opt/local/bin/wdiff';
    
    // TODO: how safe is this?
    // TODO: ansi2html.sh should not be in this dir, should it?

    echo '<div class="f9 b9"><pre>';
    if ($diffstyle == 'gitdiff'){
        system("git diff --color-words --no-index  $file1 $file2 | tail -n +6 | sed 's/\@\@.*\@\@//' | ./ansi2html.sh --body-only");
    }
    elseif ($diffstyle == 'diff'){
        system("diff -u $file1 $file2 | colordiff | $diffhighlight | tail -n +4 | grep -v '\@\@.*\@\@' | ./ansi2html.sh --body-only");
    }
    else{
        system("wdiff $file1 $file2 | colordiff | $diffhighlight | ./ansi2html.sh --body-only");
        // system("$wdiff $file1 $file2 | colordiff | $diffhighlight | ./ansi2html.sh --body-only");
    }
    echo '</pre></div>';
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





?>
