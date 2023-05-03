<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php
//

// TODO: combine with barchart.php to have only one script
//       for generating barcharts


include 'functions.php';

// get query parameters
$package    = get_param('pkg', 'opusmt');
$benchmark  = get_param('test', 'all');
$metric     = get_param('metric', 'bleu');
$model      = get_param('model', 'top');
$model1     = get_param('model1', 'unknown');
$model2     = get_param('model2', 'unknown');
$userscores = get_param('userscores', 'no');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


//-----------------------------------------
// read scores from file or cache
//-----------------------------------------

$lines = array();
if ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $lines[1] = read_scores($langpair, 'all', $metric, $name1, $pkg1);

    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    $lines[2] = read_scores($langpair, 'all', $metric, $name2, $pkg2);
    $topscores = false;
}
elseif ($model == 'verified'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[2] = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    $topscores = true;
}
elseif ($model == 'unverified'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[3] = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');
    $topscores = true;
}
elseif ($model == 'top'){
    $lines[1] = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
    $lines[2] = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    if ($userscores == "yes"){
        if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
            $lines[3] = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');
        }
    }
    $topscores = true;
}
else{
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
}


//-----------------------------------------
// extract scores from score file lines
//-----------------------------------------

$data = array();
$models = array();

$maxscore = 0;
$scores = array();

$nr_score_files = 0;
foreach (array(1, 2, 3) as $v) {
    if (count($lines[$v])){
        $scores[$v] = array();
        $nr_score_files++;
        $scores_exist[$v] = true;
        foreach($lines[$v] as $line) {
            $array = explode("\t", $line);
            if ($topscores){
                $score = (float) $array[1];
                $key = $array[0];
                $scores[$v][$key] = $score;
                if ( $maxscore < $score ){
                    $maxscore = $score;
                }
            }
            elseif ($showlang == 'all' || $showlang == $array[0]){
                if ($benchmark == 'all' || $benchmark == $array[1]){
                    $score = (float) $array[2];
                    $key = $array[0].'/'.$array[1];
                    $scores[$v][$key] = $score;
                    if ( $maxscore < $score ){
                        $maxscore = $score;
                    }
                }
            }
        }
    }
}



//-----------------------------------------
// prepare data array for the chart
//-----------------------------------------

$nrscores=0;
foreach($scores[1] as $key => $value) {
    if ($nrscores > $chart_max_scores){
        break;
    }

    if ((array_key_exists($key,$scores[2])) or ($topscores)){
        $nrscores++;
        array_push($data,$value);
        array_push($models,'model1');
    
        foreach (array(2, 3) as $v) {
            if ($scores_exist[$v]){
                if (array_key_exists($key,$scores[$v])){
                    array_push($data,$scores[$v][$key]);
                    array_push($models,'model'.$v);
                }
                else{
                    array_push($data,0);
                    array_push($models,'model'.$v);
                }
            }
        }
    }
}

if (sizeof($data) == 0){
    $data[0] = 0;
}
$nrscores = sizeof($data);



//-----------------------------------------
/*
 * Chart settings and create image
 */
//-----------------------------------------

// Image dimensions
$imageWidth = 680;
$imageHeight = 400;

// Grid dimensions and placement within image
$gridTop = 40;
$gridLeft = 50;
$gridBottom = 340;
$gridRight = 650;
$gridHeight = $gridBottom - $gridTop;
$gridWidth = $gridRight - $gridLeft;

// Bar and line width
$lineWidth = 1;
if ($nrscores > 0){
    $barWidth = floor(450/$nrscores);
}
else {
    $barWidth = 20;
}

// Font settings
$font = './OpenSans-Regular.ttf';
$fontSize = 10;

// Margin between label and axis
$labelMargin = 8;

// Max value on y-axis
$yMaxValue = $maxscore;

// Distance between grid lines on y-axis
// $yLabelSpan = 40;
if ($metric == 'bleu'){
    $yLabelSpan = ceil($maxscore/5);
}
else{
    $yLabelSpan = ceil($maxscore*20)/100;
}

// Init image
$chart = imagecreate($imageWidth, $imageHeight);

// Setup colors
$backgroundColor = imagecolorallocate($chart, 255, 255, 255);
$axisColor = imagecolorallocate($chart, 85, 85, 85);
$labelColor = $axisColor;
$gridColor = imagecolorallocate($chart, 212, 212, 212);
$barColor = imagecolorallocate($chart, 47, 133, 217);


/*
$barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                   'model2' => imagecolorallocate($chart, 217, 133, 47),
                   'model3' => imagecolorallocate($chart, 133, 217, 47));
*/

if ($topscores){
    $barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                       'model3' => imagecolorallocate($chart, 133, 133, 164),
                       'model2' => imagecolorallocate($chart, 164, 164, 164));
}
else{
    $barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                       'model2' => imagecolorallocate($chart, 217, 133, 47));
}



imagefill($chart, 0, 0, $backgroundColor);

imagesetthickness($chart, $lineWidth);

/*
 * Print grid lines bottom up
 */

if ($yMaxValue > 0 && $yLabelSpan > 0){
    for($i = 0; $i <= $yMaxValue; $i += $yLabelSpan) {
        $y = ceil($gridBottom - $i * $gridHeight / $yMaxValue);
        
        // draw the line
        imageline($chart, $gridLeft, $y, $gridRight, $y, $gridColor);

        // draw right aligned label
        $labelBox = imagettfbbox($fontSize, 0, $font, strval($i));
        $labelWidth = $labelBox[4] - $labelBox[0];

        $labelX = ceil($gridLeft - $labelWidth - $labelMargin);
        $labelY = ceil($y + $fontSize / 2);

        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, strval($i));
    }
}

$metricLabelX = ceil($gridLeft - $labelMargin);
imagettftext($chart, $fontSize, 90, $metricLabelX, $gridTop+20, $labelColor, $font, $metric);
imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, 'benchmark index (see ID in table of scores)');


/*
 * Draw x- and y-axis
 */

imageline($chart, $gridLeft, $gridTop, $gridLeft, $gridBottom, $axisColor);
imageline($chart, $gridLeft, $gridBottom, $gridRight, $gridBottom, $axisColor);

/*
 * Draw the bars with labels
 */

$barSpacing = $gridWidth / count($data);
$itemX = $gridLeft + $barSpacing / 2;

$count = 0;
foreach($data as $key => $value) {
    // Draw the bar
    $x1 = floor($itemX - $barWidth / 2);
    $y1 = $yMaxValue > 0 ? floor($gridBottom - $value / $yMaxValue * $gridHeight) : $gridBottom;
    // $y1 = $gridBottom - $value / $yMaxValue * $gridHeight;
    $x2 = floor($itemX + $barWidth / 2);
    $y2 = $gridBottom - 1;

    if ($x2 != $x1 and $y2 != $y1){
        imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColors[$models[$key]]);
    }

    // special for this comparison: only label every second bar
    // and adjust the ID to increment every second bar
    $label = floor(($key+$nr_score_files-1)/$nr_score_files)-1;
    if ($label == ceil(($key+$nr_score_files-1)/$nr_score_files)-1){
        // Draw the label
        $labelBox = imagettfbbox($fontSize, 0, $font, $key);
        $labelWidth = $labelBox[4] - $labelBox[0];

        $labelX = floor($itemX - $labelWidth / $nr_score_files);
        // $labelX = $itemX;
        $labelY = $gridBottom + $labelMargin + $fontSize;

        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $label);
    }

    $itemX += $barSpacing;
}

/*
 * Output image to browser
 */

header('Content-Type: image/png');
imagepng($chart);
