<?php


// return a color for a given model and model package

function model_color($package, $model){
    
    $type2color = array( 'contributed' => 'purple',
                         'external'    => 'grey',
                         'Tatoeba-MT-models' => 'blue',
                         'OPUS-MT-models' => 'orange' );
    
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
        $modelparts = explode('/',$model);
        $type = $modelparts[count($modelparts)-3];
        return $type2color[$modelparts[count($modelparts)-3]];
    }
}


function barchart(&$data, $metric, $maxscore, &$colors){

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

    $barColors = array('blue' => imagecolorallocate($chart, 47, 133, 217),
                       'orange' => imagecolorallocate($chart, 217, 133, 47),
                       'grey' => imagecolorallocate($chart, 164, 164, 164),
                       'purple' => imagecolorallocate($chart, 133, 133, 164),
                       'green' => imagecolorallocate($chart, 47, 196, 47),
                       'red' => imagecolorallocate($chart, 217, 47, 47));

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

    // imagettftext($chart, $fontSize, 0, 10, 10, $labelColor, $font, $maxscore);
    $metricLabelX = ceil($gridLeft - $labelMargin);
    imagettftext($chart, $fontSize, 90, $metricLabelX, $gridTop+20, $labelColor, $font, $metric);
    imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, $index_label);



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

    foreach($data as $key => $value) {
        // Draw the bar
        $x1 = floor($itemX - $barWidth / 2);
        $y1 = $yMaxValue > 0 ? floor($gridBottom - $value / $yMaxValue * $gridHeight) : floor($gridBottom);
        $x2 = floor($itemX + $barWidth / 2);
        $y2 = floor($gridBottom - 1);

        if ($x2 != $x1 and $y2 != $y1){
            $color = array_key_exists($key, $colors) ? $colors[$key] : 'blue';
            $barColor = array_key_exists($color, $barColors) ? $barColors[$color] : $barColors['grey'];
            imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColor);
        }
    
        // Draw the label
        $labelBox = imagettfbbox($fontSize, 0, $font, $key);
        $labelWidth = $labelBox[4] - $labelBox[0];

        $labelX = floor($itemX - $labelWidth / 2);
        $labelY = $gridBottom + $labelMargin + $fontSize;

        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $key);

        $itemX += $barSpacing;
    }
    return $chart;
}



?>
