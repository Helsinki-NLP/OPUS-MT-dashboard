<?php



// read benchmark scores from OPUS-MT, external and contributed models
// mostly for getting the scores for a specific benchmark (or a benchmark average)
// (back off to read-scores for other modes)

function read_model_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged', $cache_size=10){
    global $userscores;
    
    if ($model == 'top' && $benchmark != 'all'){
        $lines1 = read_scores($langpair, $benchmark, $metric, 'all', 'opusmt', 'scores');
        $lines2 = read_scores($langpair, $benchmark, $metric, 'all', 'external', 'external-scores');
        $lines3 = array();
        if ($benchmark == 'avg'){
            $head1 = array_shift($lines1);
            $head2 = array_shift($lines2);
        }
        if ($userscores == "yes"){
            $lines3 = read_scores($langpair, $benchmark, $metric, 'all', 'contributed', 'user-scores');
            if ($benchmark == 'avg'){
                $head3 = array_shift($lines3);
            }
        }

        $lines = array_merge($lines1, $lines2, $lines3);
        // $lines = array_merge($lines1, $lines2);
        arsort($lines, SORT_NUMERIC);
        if ($benchmark == 'avg'){
            array_unshift($lines, $head1);
        }
    }
    else{
        $lines = read_scores($langpair, $benchmark, $metric, $model, $pkg, $source);
    }
    return $lines;
}



// read scores from session cache or from file (local or online)

function read_scores($langpair, $benchmark, $metric='bleu', $model='all', $pkg='opusmt', $source='unchanged', $cache_size=10){
    $file = get_score_filename($langpair, $benchmark, $metric, $model, $pkg, $source);

    if (! array_key_exists('cached-scores', $_SESSION)){
        $_SESSION['cached-scores'] = array();
        $_SESSION['next-cache-key'] = 0;
    }
    
    $key = array_search($file, $_SESSION['cached-scores']);
    if ($key !== false){
        if (array_key_exists('scores', $_SESSION)){
            if (array_key_exists($key, $_SESSION['scores'])){
                // echo "read scores from cached file with key $key";
                if (is_array($_SESSION['scores'][$key])){
                    return $_SESSION['scores'][$key];
                }
            }
        }
    }

    if ($_SESSION['next-cache-key'] >= $cache_size){
        $_SESSION['next-cache-key'] = 0;
    }

    $key = $_SESSION['next-cache-key'];
    $_SESSION['cached-scores'][$key] = $file;

    // read scores from the file
    // filter out some unwanted dev sets (TODO: can we skip that now?)
    // add the package as a last TAB-separated value (see substr_replace command)
    $_SESSION['scores'][$key] = substr_replace(filter_testsets(@file($file)),"\t".$pkg,-1,-1);
    
    $_SESSION['next-cache-key']++;
    if (is_array($_SESSION['scores'][$key])){
        return $_SESSION['scores'][$key];
    }
    return array();
}



// remove some test sets that we do not want to display
// - all newsdev sets
// - flores dev sets
// - other dev sets

function is_testset_line(string $line): bool {
    if (strpos($line, 'newsdev') !== false){ return false; }
    $arr = explode("\t",$line);
    if (in_array('flores101-dev',$arr)){ return false; }
    if (in_array('flores200-dev',$arr)){ return false; }
    if (in_array('wikipedia.dev',$arr)){ return false; }
    if (in_array('news2008',$arr)){ return false; }
    if (in_array('news-test2008',$arr)){ return false; }
    if (in_array('newsdiscussdev2015',$arr)){ return false; }
    return true;
};

function filter_testsets($array){
    if (is_array($array)){
        return array_filter($array,"is_testset_line");
    }
    return $array;
}


//////////////////////////////////////////////////////////////////
// use GD to plot charts (separate scripts that produce an image)
//////////////////////////////////////////////////////////////////

function plot_score_comparison_gd($chart){
    $barchart_script = $chart == 'diff' ? 'diff-barchart.php' : 'compare-barchart.php';
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"$barchart_script?". SID ."\" alt=\"barchart\" />");
    }
    else{
        $url_param = make_query([]);
        echo("<img src=\"$barchart_script?$url_param\" alt=\"barchart\" />");
    }
}

function plot_scores_gd($chart){
    $barchart_script = $chart == 'scatterplot' ? 'scatterplot.php' : 'barchart.php';
    $url_param = make_query(['model1' => 'unknown', 'model2' => 'unknown']);
    if ( isset( $_COOKIE['PHPSESSID'] ) ) {
        echo("<img src=\"$barchart_script?". SID .'&'.$url_param."\" alt=\"barchart\" />");
    }
    else{
        echo("<img src=\"$barchart_script?$url_param\" alt=\"barchart\" />");
    }
}


// barchart function that creates a plot using the GD library

function barchart(&$data, $maxscore, &$colors, $index_label, $value_label,
                  $scale=100, $minscore=0, $bars_per_index=1){

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

  if ($scale == 100){
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

  $barColors = array(
      'black' => imagecolorallocate($chart, 0, 0, 0),
      'white' => imagecolorallocate($chart, 255, 255, 255),
      'blue' => imagecolorallocate($chart, 47, 133, 217),
      'orange' => imagecolorallocate($chart, 217, 133, 47),
      'grey' => imagecolorallocate($chart, 164, 164, 164),
      'purple' => imagecolorallocate($chart, 133, 133, 164),
      'green' => imagecolorallocate($chart, 47, 196, 47),
      'darkred' => imagecolorallocate($chart, 196, 28, 42),
      'red' => imagecolorallocate($chart, 217, 47, 47));

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
  imagettftext($chart, $fontSize, 90, $metricLabelX-25, $gridTop+120, $labelColor, $font, $value_label);
  imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, $index_label);

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
      $color = array_key_exists($key, $colors) ? $colors[$key] : 'blue';
      $barColor = array_key_exists($color, $barColors) ? $barColors[$color] : model_size_color($colors[$key], $chart);
      imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $barColor);
    }

    // Draw the label
    $labelBox = imagettfbbox($fontSize, 0, $font, $key);
    $labelWidth = $labelBox[4] - $labelBox[0];

    /*
      ID labels on X-axis
    */

    if ($bars_per_index > 1){
        $label = floor(($key+$bars_per_index-1)/$bars_per_index)-1;
        if ($label == ceil(($key+$bars_per_index-1)/$bars_per_index)-1){
            $labelX = floor($itemX - $labelWidth / $bars_per_index);
            $labelY = $gridBottom + $labelMargin + $fontSize;
            imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $label);
        }
    }
    else{
        $labelX = floor($itemX - $labelWidth / 2);
        $labelY = $gridZero + $labelMargin + $fontSize;
        imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, $key);
    }

    $itemX += $barSpacing;
  }
  return $chart;

}



// scatterplot function that creates a plot using the GD library

function scatter_plot(&$data, &$colors, $xLabel, $yLabel, $xMaxValue=100, $yMaxValue=100, $xMinValue=1, $yMinValue=0){

    $logscaleX = true;
    $logscaleY = false;

    $xScaleMargin = 1 + ceil(0.01 * ($xMaxValue - $xMinValue));
    $yScaleMargin = 0.1 + floor(0.1 * ($yMaxValue - $yMinValue))/10;

    $xMinValue -= $xScaleMargin;
    $xMaxValue += $xScaleMargin;
    $yMinValue -= $yScaleMargin;
    $yMaxValue += $yScaleMargin;

    if ($logscaleY){
        $yMinValue = $yMinValue <= 0 ? 1 : $yMinValue;
    }
    if ($logscaleX){
        $xMinValue = $xMinValue <= 0 ? 1 : $xMinValue;
    }

    $scaleX = $logscaleX ? log($xMaxValue,2) - log($xMinValue,2) : $xMaxValue - $xMinValue;
    $scaleY = $logscaleY ? log($yMaxValue,2) - log($yMinValue,2) : $yMaxValue - $yMinValue;
    
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
    $gridHeight = $gridBottom - $gridTop;

    // Bar and line width
    $lineWidth = 1;

    // Font settings
    $font = './OpenSans-Regular.ttf';
    $fontSize = 10;

    // Margin between label and axis
    $labelMargin = 8;

    $yLabelSpan = ceil(($yMaxValue-$yMinValue)*20)/100;
    $xLabelSpan = ceil(($xMaxValue-$xMinValue)*20)/100;

    // Init image
    $chart = imagecreate($imageWidth, $imageHeight);

    // Setup colors
    $backgroundColor = imagecolorallocate($chart, 255, 255, 255);
    $axisColor = imagecolorallocate($chart, 85, 85, 85);
    // $labelColor = $axisColor;
    $labelColor = imagecolorallocate($chart, 64, 64, 64);
    $gridColor = imagecolorallocate($chart, 212, 212, 212);
    $barColor = imagecolorallocate($chart, 47, 133, 217);

    $barColors = array(
        'black' => imagecolorallocate($chart, 0, 0, 0),
        'white' => imagecolorallocate($chart, 255, 255, 255),
        'blue' => imagecolorallocate($chart, 47, 133, 217),
        'orange' => imagecolorallocate($chart, 217, 133, 47),
        'grey' => imagecolorallocate($chart, 164, 164, 164),
        'purple' => imagecolorallocate($chart, 133, 133, 164),
        'green' => imagecolorallocate($chart, 47, 196, 47),
        'darkred' => imagecolorallocate($chart, 196, 28, 42),
        'red' => imagecolorallocate($chart, 217, 47, 47));

    imagefill($chart, 0, 0, $backgroundColor);
    imagesetthickness($chart, $lineWidth);

    /*
     * Print grid lines bottom up
     */

    if ($yMaxValue > 0 && $yLabelSpan > 0){
        for($i = $yMinValue; $i <= $yMaxValue; $i += $yLabelSpan) {
            if ($logscaleY){
                $y = (int) ceil($gridBottom - ( (log($i,2) - log($yMinValue,2) ) * $gridHeight / $scaleY  ));
            }
            else{
                $y = ceil($gridBottom - ($i-$yMinValue) * $gridHeight / $scaleY );
            }
                
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

    if ($xMaxValue > 0 && $xLabelSpan > 0){
        for($i = $xMinValue; $i <= $xMaxValue; $i += $xLabelSpan) {

            if ($logscaleX){
                $x = (int) ceil($gridLeft + ( (log($i,2) - log($xMinValue,2) ) * $gridWidth / $scaleX  ));
            }
            else{
                $x = ceil($gridLeft + ($i-$xMinValue) * $gridWidth / $scaleX );
            }
      
            // draw the line
            imageline($chart, $x, $gridBottom, $x, $gridTop, $gridColor);

            // draw right aligned label
            $labelBox = imagettfbbox($fontSize, 0, $font, strval($i));
            $labelWidth = $labelBox[4] - $labelBox[0];

            $labelX = ceil($x - $labelWidth / 2);
            $labelY = ceil($gridBottom + $fontSize + $labelMargin);

            imagettftext($chart, $fontSize, 0, $labelX, $labelY, $labelColor, $font, strval($i));
        }
    }



    $metricLabelX = ceil($gridLeft - $labelMargin);
    imagettftext($chart, $fontSize, 90, $metricLabelX-32, $gridTop+120, $labelColor, $font, $yLabel);
    imagettftext($chart, $fontSize, 0, 200, $imageHeight-20, $labelColor, $font, strval($xLabel));

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

    foreach($data as $label => $coordinates) {
        $normX = $logscaleX ? (log($coordinates[0],2) - log($xMinValue,2))/$scaleX : ($coordinates[0]-$xMinValue)/$scaleX;
        $normY = $logscaleY ? (log($coordinates[1],2) - log($yMinValue,2))/$scaleY : ($coordinates[1]-$yMinValue)/$scaleY;
        
        $midX = ceil($gridLeft + $normX*$gridWidth);
        $midY = ceil($gridBottom - $normY*$gridHeight);
        $color = array_key_exists($label, $colors) ? $colors[$label] : 'blue';
        $barColor = array_key_exists($color, $barColors) ? $barColors[$color] : model_size_color($colors[$label], $chart);
        imagefilledellipse($chart, $midX, $midY, 15, 15, $barColor);
        imageellipse($chart, $midX, $midY, 15, 15, $barColors['white']);

        // $labelBox = imagettfbbox($fontSize, 0, $font, strval($label));
        imagettftext($chart, $fontSize, 0, $midX + 10, $midY, $labelColor, $font, strval($label));
        
    }
    return $chart;

}

?>
