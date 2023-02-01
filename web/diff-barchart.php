<?php
session_start();

// adapted from https://www.infscripts.com/how-to-create-a-bar-chart-in-php

include 'functions.php';

// get query parameters
$benchmark = get_param('test', 'all');
$metric    = get_param('metric', 'bleu');
$model     = get_param('model', 'top');
$model1    = get_param('model1', 'unknown');
$model2    = get_param('model2', 'unknown');

list($srclang, $trglang, $langpair) = get_langpair();

$showlang  = get_param('scoreslang', $langpair);


if ($model == 'top'){
    list($srclang, $trglang, $langpair) = get_langpair();
    $lines1 = read_scores($langpair, 'all', $metric, 'all', 'internal', 'scores');
    $lines2 = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');
    $topscores = true;
}
elseif ($model1 != 'unknown' and $model2 != 'unknown'){
    $parts = explode('/',$model1);
    $pkg1 = array_shift($parts);
    $name1 = implode('/',$parts);
    $lines1 = read_scores($langpair, 'all', $metric, $name1, $pkg1);

    $parts = explode('/',$model2);
    $pkg2 = array_shift($parts);
    $name2 = implode('/',$parts);
    $lines2 = read_scores($langpair, 'all', $metric, $name2, $pkg2);
}



$data = array();
$model = array();


if ($metric == 'bleu' || $metric == 'spbleu'){
    $maxscore = 1;
    $minscore = -1;
}
else{
    $maxscore = 0.01;
    $minscore = -0.01;
}


// read model-specific scores
$scores1 = array();
foreach($lines1 as $line1) {
    $array = explode("\t", $line1);
    if ($topscores){
        $score = (float) $array[1];
        $key = $array[0];
        $scores1[$key] = $score;
    }
    elseif ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores1[$key] = $score;
        }
    }
}

foreach($lines2 as $line2) {
    $array = explode("\t", $line2);
    if ($topscores){
        $score = (float) $array[1];
        $key = $array[0];
        $scores2[$key] = $score;
    }
    elseif ($showlang == 'all' || $showlang == $array[0]){
        if ($benchmark == 'all' || $benchmark == $array[1]){
            $score = (float) $array[2];
            $key = $array[0].'/'.$array[1];
            $scores2[$key] = $score;
        }
    }
}

foreach($scores1 as $key => $value) {
    if (array_key_exists($key,$scores2)){
        $diff = $value - $scores2[$key];
        array_push($data,$diff);
        if ($diff > 0){
            array_push($model,'model1');
            if ( $maxscore < $diff ){
                $maxscore = $diff;
            }
        }
        else{
            array_push($model,'model2');
            if ( $diff < $minscore ){
                $minscore = $diff;
            }
        }
    }
}


// $maxscore = ceil($maxscore);
// $minscore = floor($minscore);

if (sizeof($data) == 0){
    $data[0] = 0;
}
$nrscores = sizeof($data);



/*
 * Chart settings and create image
 */

// Image dimensions
$imageWidth = 680;
$imageHeight = 400;

// Grid dimensions and placement within image
$gridTop = 40;
$gridLeft = 50;
$gridBottom = 340;
$gridRight = 650;
$gridTotalHeight = $gridBottom - $gridTop;
$gridWidth = $gridRight - $gridLeft;

$gridZero = floor($gridTotalHeight*($maxscore/($maxscore-$minscore)))+$gridTop;
$gridHeight = $gridZero - $gridTop;
// $gridZero = 140;

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
$yMinValue = $minscore;

// Distance between grid lines on y-axis
// $yLabelSpan = 40;
if ($metric == 'bleu' || $metric == 'spbleu'){
    $yLabelSpan = ceil(($maxscore-$minscore)/5);
}
else{
    $yLabelSpan = ceil(($maxscore-$minscore)*20)/100;
}

// Init image
$chart = imagecreate($imageWidth, $imageHeight);

// Setup colors
$backgroundColor = imagecolorallocate($chart, 255, 255, 255);
$axisColor = imagecolorallocate($chart, 85, 85, 85);
$labelColor = $axisColor;
$gridColor = imagecolorallocate($chart, 212, 212, 212);
$barColor = imagecolorallocate($chart, 47, 133, 217);

if ($topscores){
    $barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                       'model2' => imagecolorallocate($chart, 164, 164, 164));
}
else{
    $barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                       'model2' => imagecolorallocate($chart, 217, 133, 47));

}

/*
$barColors = array('model1' => imagecolorallocate($chart, 47, 133, 217),
                   'model2' => imagecolorallocate($chart, 217, 133, 47));
*/

imagefill($chart, 0, 0, $backgroundColor);

imagesetthickness($chart, $lineWidth);

/*
 * Print grid lines bottom up
 */


if ($yMaxValue > 0 && $yLabelSpan > 0){
    for($i = $yMinValue; $i <= $yMaxValue; $i += $yLabelSpan) {
        $y = ceil($gridZero - $i * $gridHeight / $yMaxValue );
        
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
imagettftext($chart, $fontSize, 90, $metricLabelX-25, $gridTop+120, $labelColor, $font, 'difference in '.$metric);
imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, 'benchmark index (see ID in table of scores)');


/*
 * Draw x- and y-axis
 */

imageline($chart, $gridLeft, $gridTop, $gridLeft, $gridBottom, $axisColor);
imageline($chart, $gridLeft, $gridZero, $gridRight, $gridZero, $axisColor);

/*
 * Draw the bars with labels
 */

$barSpacing = $gridWidth / count($data);
$itemX = $gridLeft + $barSpacing / 2;

foreach($data as $key => $value) {
    // Draw the bar
    $x1 = floor($itemX - $barWidth / 2);
    $y1 = $yMaxValue > 0 ? floor($gridZero - $value / $yMaxValue * $gridHeight) : $gridZero;
    $x2 = floor($itemX + $barWidth / 2);
    $y2 = floor($gridZero - 1);

    if ($x2 != $x1 and $y2 != $y1){
        imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColors[$model[$key]]);
    }

    // Draw the label
    $labelBox = imagettfbbox($fontSize, 0, $font, $key);
    $labelWidth = $labelBox[4] - $labelBox[0];

    $labelX = floor($itemX - $labelWidth / 2);
    $labelY = $gridZero + $labelMargin + $fontSize;

    imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $key);

    $itemX += $barSpacing;
}

/*
 * Output image to browser
 */

header('Content-Type: image/png');
imagepng($chart);
