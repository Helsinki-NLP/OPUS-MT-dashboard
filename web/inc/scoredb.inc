<?php


function get_topscores($langpair, $metric='bleu', $package='opusmt'){

    $db = open_scoredb($metric, $package);
    $results = $db->query("select model,testset,max(score) from scores where langpair='$langpair' group by testset");

    $scores = array();
    $models = array();

    while ($row = $results->fetchArray(SQLITE3_NUM)) {
        $testset = $row[1];
        if (skip_benchmark($testset)){
            continue;
        }
        $models[$testset] = $row[0];
        $scores[$testset] = $row[2];
    }
    $db->close();
    return [$scores,$models];
}



function open_scoredb($metric='bleu', $package='opusmt'){    
    $dbfile = get_scoredb_file($metric, $package);
    return new SQLite3($dbfile,SQLITE3_OPEN_READONLY);
}
    

// get score DB file (also copy if necessary/outdated)

function get_scoredb_file($metric='bleu', $package='opusmt'){
    global $leaderboard_urls;

    $db_filename = 'scores/'.$metric.'_scores.db';
    $date = get_scoredb_date($metric, $package);
    $tmpdir = sys_get_temp_dir();
    $db_file = implode('/',[$tmpdir,$package,$db_filename.$date]);
    
    if (file_exists($db_file)){
        return $db_file;
    }
    
    $db_fileurl = implode('/',[$leaderboard_urls[$package],$db_filename]);
    $dir = dirname($db_file);
    if (! file_exists($dir)){
        mkdir($dir,0777,true);
    }
    if (copy($db_fileurl, $db_file)) {
        return $db_file;
    }
    return false;
}


// get the release date of the score DB

function get_scoredb_date($metric='bleu', $package='opusmt'){
    global $leaderboard_urls;
    if (array_key_exists('scoredb-dates', $_SESSION)){
        if (array_key_exists("$package/$metric", $_SESSION['scoredb-dates'])){
            return $_SESSION['scoredb-dates']["$package/$metric"];
        }
    }
    $date_filename = 'scores/'.$metric.'_scores.date';
    $date_fileurl = implode('/',[$leaderboard_urls[$package],$date_filename]);
    if ($lines = @file($date_fileurl)){
        $date = rtrim($lines[0]);
        $_SESSION['scoredb-dates']["$package/$metric"] = $date;
        return $date;
    }
    return 'unknown';
}

?>
