<?php


// TODO: cannot get average scores from DB (backoff to files)
// TODO: max scores per benchmark is quite slow (backoff to top-score files? index score column in DB?)


class ScoreDbReader extends ScoreFileReader{

    public function get_topscores($langpair, $metric='bleu', $package='opusmt'){

        $db = $this->open_scoredb($metric, $package);
        $condition = "where langpair='$langpair' and ".$this->exclude_benchmarks_clause();
        $results = $db->query("select model,testset,max(score) from scores $condition group by testset");
        // echo("select model,testset,max(score) from scores $condition group by testset");
        // echo($this->exclude_benchmarks_clause());
                
        $scores = array();
        $models = array();

        if ($results){
            while ($row = $results->fetchArray(SQLITE3_NUM)) {
                $testset = $row[1];
                /*
                if ($this->skip_benchmark($testset)){
                    continue;
                }
                */
                $models[$testset] = $row[0];
                $scores[$testset] = $row[2];
            }
        }
        $db->close();
        return [$scores,$models];
    }

    public function get_model_scores($model, $metric='bleu', $package='opusmt',
                                     $benchmark='all', $langpair='all',
                                     $show_max_scores=0){

        $db = $this->open_scoredb($metric, $package);
        
        $condition = "where model='$model'";
        if ($langpair != 'all')
            $condition .= " and langpair='$langpair'";
        if ($benchmark != 'all')
            $condition .= " and testset='$benchmark'";
        $condition .= " and ".$this->exclude_benchmarks_clause();

        $results = $db->query("select langpair,testset,score from scores $condition");
        
        $scores = array();
        $nrscores=0;

        if ($results){
            while ($row = $results->fetchArray(SQLITE3_NUM)) {
                if ($show_max_scores > 0 && $nrscores > $show_max_scores){
                    break;
                }
                $lang = $row[0];
                $testset = $row[1];
                $key = $testset.'/'.$lang;
                /*
                if ($this->skip_benchmark($testset)){
                    continue;
                }
                */
                $scores[$key] = (float) $row[2];
                $nrscores++;
            }
        }
        $db->close();
        return $scores;
    }


    public function get_package_benchmark_scores($langpair, $benchmark, $metric='bleu', $package='opusmt'){
        if ($benchmark == 'avg'){
            return $this->get_package_average_scores($langpair, $metric, $package);
            return parent::get_package_benchmark_scores($langpair, $benchmark, $metric, $package);
        }
        
        $db = $this->open_scoredb($metric, $package);
        
        $condition = "testset='$benchmark' and langpair='$langpair'";
        $results = $db->query("select model,score from scores where $condition order by score desc");
        
        $scores = array();
        if ($results){
            while ($row = $results->fetchArray(SQLITE3_NUM)) {
                $scores[$package."\t".$row[0]] = (float) $row[1];
            }
        }
        $db->close();
        return $scores;
    }

    public function get_package_average_scores($langpair, $metric='bleu', $package='opusmt'){
        
        $db = $this->open_scoredb($metric, $package);
        
        $condition = "where langpair='$langpair' and ";
        $condition .= $this->exclude_benchmarks_clause();
        $results = $db->query("select model,avg(score) from scores $condition group by model order by avg(score) desc");
        
        $scores = array();
        if ($results){
            while ($row = $results->fetchArray(SQLITE3_NUM)) {
                $scores[$package."\t".$row[0]] = (float) $row[1];
            }
        }
        $db->close();
        return $scores;
    }

    
    private function open_scoredb($metric='bleu', $package='opusmt'){    
        $dbfile = $this->get_scoredb_file($metric, $package);
        // echo("open DB file $dbfile");
        // if (not file_exists($dbfile)){
        //     echo("DB filed $dbfile does not exist");
        // }
        return new SQLite3($dbfile,SQLITE3_OPEN_READONLY);
    }
    

    // get score DB file (also copy if necessary/outdated)

    private function get_scoredb_file($metric='bleu', $package='opusmt'){
        global $leaderboard_urls, $leaderboard_dirs;

        // first check whether a local clone from the GitHub repo exists
        $db_filename = 'scores/'.$metric.'_scores.db';
        $db_file = implode('/',[$leaderboard_dirs[$package],$db_filename]);
    
        if (file_exists($db_file)){
            return $db_file;
        }
    
        // otherwise check in tmp with the date of the current release
        $date = $this->get_scoredb_date($metric, $package);
        $tmpdir = sys_get_temp_dir();
        $db_file = implode('/',[$tmpdir,$package,$db_filename.$date]);
    
        if (file_exists($db_file)){
            return $db_file;
        }

        // not found? --> fetch the file from GitHub
        $db_fileurl = implode('/',[$leaderboard_urls[$package],$db_filename]);
        $tmpfile = tempnam(sys_get_temp_dir(),'scoredb');
        if (copy($db_fileurl, $tmpfile)) {
            $dir = dirname($db_file);
            if (! file_exists($dir)){
                mkdir($dir,0777,true);
            }
            if (rename($tmpfile, $db_file)) {
                return $db_file;
            }
        }
        return false;
    }


    // get the release date of the score DB

    private function get_scoredb_date($metric='bleu', $package='opusmt'){
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

    private function exclude_benchmarks_clause(){
        $conditions = array("testset not like 'newsdev%'");
        // $conditions = array();
        foreach (self::$exclude_benchmarks as $testset){
            array_push($conditions,"testset != '$testset'");
        }
        return implode(' and ',$conditions);
    }

}

?>
