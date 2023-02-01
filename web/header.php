<?php 

$leaderboard_url = 'https://raw.githubusercontent.com/Helsinki-NLP/OPUS-MT-leaderboard/master';

// form for selecting benchmarks and language pairs

echo '<div class="header">';
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="get">';
// echo '<input type="hidden" id="session" name="session" value="clear">';
echo '<input type="hidden" id="model" name="model" value="all">';
echo '<input type="hidden" id="model1" name="model1" value="unknown">';
echo '<input type="hidden" id="model2" name="model2" value="unknown">';
echo '<input type="hidden" id="test" name="test" value="all">';
echo '<input type="hidden" id="scoreslang" name="scoreslang" value="all">';


$langpairs = array_map('rtrim', file(implode('/',[$leaderboard_url,'scores','langpairs.txt'])));
echo '  select language pair: <select name="langpair" id="langpair" onchange="this.form.submit()">';
foreach ($langpairs as $l){
    if ($l == $langpair){
        echo "<option value=\"$l\" selected>$l</option>";
        $selected = $l;
    }
    else{
        echo "<option value=\"$l\">$l</option>";
    }
}
echo '</select>';
echo '  [<a href="index.php?session=clear">restart</a>]';
$query = make_query(['model' => 'all', 'test' => 'all', 'scoreslang' => 'all']);
echo '  [<a href="index.php?'.SID.'&'.$query.'">compare scores</a>]';
$query = make_query(['model1' => 'unknown', 'model2' => 'unknown', 'test' => 'all', 'scoreslang' => 'all']);
echo '  [<a href="compare.php?'.SID.'&'.$query.'">compare models</a>]';
echo '  [<a href="releases.php">show release history</a>]';
/*
$query = make_query(['modelsource' => 'external-scores']);
echo '  [<a href="index.php?'.SID.'&'.$query.'">external models</a>]';
$query = make_query(['modelsource' => 'scores']);
echo '  [<a href="index.php?'.SID.'&'.$query.'">internal models</a>]';
*/
echo '</form>';
echo '<hr/>';
echo '</div>';


?>
