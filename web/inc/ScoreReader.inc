<?php

include('inc/ScoreReader/Files.inc');
include('inc/ScoreReader/SQLite.inc');

class ScoreReader{
    public static function new($type='file'){
        if ($type == 'db')
            return new ScoreDbReader();
        else
            return new ScoreFileReader();
    }
}

?>
