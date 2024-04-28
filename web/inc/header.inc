<?php 


// form for selecting benchmarks and language pairs

echo '<div class="header">';

echo '<form action="index.php" method="get">';
echo '  [<a href="index.php?session=clear">restart</a>]';
$query = make_share_link();
echo '  [<a href="'.$_SERVER['PHP_SELF'].'?'.$query.'">share link</a>]';

// echo '<form action="'.$_SERVER['PHP_SELF'].'" method="get">';
// echo '<input type="hidden" id="session" name="session" value="clear">';
echo '<input type="hidden" id="model" name="model" value="top">';
echo '<input type="hidden" id="model1" name="model1" value="unknown">';
echo '<input type="hidden" id="model2" name="model2" value="unknown">';
echo '<input type="hidden" id="test" name="test" value="all">';
echo '<input type="hidden" id="scoreslang" name="scoreslang" value="all">';


// get all language pairs
$langpairs = array_map('rtrim', file(implode('/',[$leaderboard_url,'scores','langpairs.txt'])));

// extract source languages from language pairs
$srclang_func = function(string $langpair): string {
    list($src,$trg) = explode('-',$langpair);
    return $src;
};

// extract all target languages from language pairs
$trglang_func = function(string $langpair): string {
    list($src,$trg) = explode('-',$langpair);
    return $trg;
};

// filter out all language pairs that do not match the current source language
$greplang_func = function(string $langpair): bool {
    global $srclang;
    list($src,$trg) = explode('-',$langpair);
    return $src == $srclang;
};



// show language selection form
// - separate pull down boxes for source and target language
// - show only target languages from language pairs with the current source language

$srclangs = array_unique(array_map($srclang_func,$langpairs));
$trglangs = array_unique(array_map($trglang_func,array_filter($langpairs,$greplang_func)));

echo '  select language: <select name="src" id="src" onchange="this.form.submit()">';
foreach ($srclangs as $l){
    if ($l == $srclang){
        echo "<option value=\"$l\" selected>$l</option>";
        $selected = $l;
    }
    else{
        echo "<option value=\"$l\">$l</option>";
    }
}
echo '</select>';

echo '<select name="trg" id="trg" onchange="this.form.submit()">';
foreach ($trglangs as $l){
    if ($l == $trglang){
        echo "<option value=\"$l\" selected>$l</option>";
        $selected = $l;
    }
    else{
        echo "<option value=\"$l\">$l</option>";
    }
}
echo '</select>';


// alternatively: pull-down menu with all language pairs
// - problem: this is super long
// - advantage: no processing of language pairs is necessary


/*
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
*/

if (isset($showlang)){
    $query = make_query(['src' => $trglang, 'trg' => $srclang,
                         'langpair' => implode('-',[$trglang,$srclang]),
                         'scoreslang' => implode('-',array_reverse(explode('-',$showlang)))  ]);
}
else{
    $query = make_query(['src' => $trglang, 'trg' => $srclang,
                         'langpair' => implode('-',[$trglang,$srclang]) ]);
}
echo '  [<a href="'.$_SERVER['PHP_SELF'].'?'.SID.'&'.$query.'">swap</a>]';

$query = make_query(['model' => 'top', 'test' => 'all', 'chart' => 'standard', 'scoreslang' => 'all']);
echo '  [<a href="index.php?'.SID.'&'.$query.'">compare scores</a>]';
$query = make_query(['model1' => 'unknown', 'model2' => 'unknown',
                     'test' => 'all', 'chart' => 'standard', 'scoreslang' => 'all']);
echo '  [<a href="compare.php?'.SID.'&'.$query.'">compare models</a>]';
echo '  [<a href="https://opus.nlpl.eu/NMT-map/Tatoeba-all/src2trg/index.html">map</a>]';
echo '  [<a href="releases.php">release history</a>]';
echo '  [<a href="upload/">uploads</a>]';
echo '</form>';
echo '<hr/>';
echo '</div>';


?>
