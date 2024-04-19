<?php

function read_barchart_scores(&$lines, $model, $benchmark, $chartlegend='type', $showlang='all'){
    global $chart_max_scores;

    // first line is a header for averaged scores
    if ($benchmark == 'avg'){
        $averaged_benchmarks = array_shift($lines);
    }

    $data = array();
    $type = array();
    $nrscores = 0;

    // get model-specific scores
    if ($model != 'all' && $model != 'top'){
        foreach($lines as $line) {
            if ($nrscores > $chart_max_scores){
                break;
            }
            $array = explode("\t", rtrim($line));
            if ($showlang != 'all'){
                if ($showlang != $array[0]){
                    continue;
                }
            }
            if ($benchmark != 'all'){
                if ($array[1] != $benchmark){
                    continue;
                }
            }
            
            $score = (float) $array[2];        
            $data[$array[1]] = $score;
            $type[$array[1]] = model_color($array[count($array)-1], $model);
        
            $nrscores++;
        }
    }
    // get scores from benchmark-specific leaderboard
    elseif ($benchmark != 'all'){
        foreach($lines as $line) {
            $array = explode("\t", rtrim($line));
            
            list($modelid, $modelurl) = normalize_modelname($array[1]);        
            $path = explode('/',$modelid);
            array_shift($path);
            $model_label = implode('/',$path);
            $data[$model_label] = $array[0];
        
            if ($chartlegend == 'size'){
                $size = ceil(model_size($array[count($array)-1], $modelid));
                array_push($type,$size);
            }
            else{
                $type[$model_label] = model_color($array[count($array)-1], $modelid);
            }
            $nrscores++;
        }
        $data = array_reverse($data, true);
        $type = array_reverse($type, true);
    }
    // get top-scores
    else{
        foreach($lines as $line) {
            $array = explode("\t", rtrim($line));
            $data[$array[0]] = $array[1];
            $type[$array[0]] = model_color($array[count($array)-1], $array[2]);        
            $nrscores++;
        }
    }
    return [$data,$type];
}


// either get RGB values for a given pre-defined color
// or convert model size into RGB values of a corresponding color

function modelcolor_rgb($type){
    $barColors = array(
        'black' => [0, 0, 0],
        'white' => [255, 255, 255],
        'blue' => [47, 133, 217],
        'orange' => [217, 133, 47],
        'grey' => [164, 164, 164],
        'purple' => [133, 133, 164],
        'green' => [47, 196, 47],
        'darkred' => [196, 28, 42],
        'red' => [217, 47, 47]);
    if (array_key_exists($type, $barColors)){
        return $barColors[$type];
    }
    else{
        return size_to_rgb($type);
    }
}

// plot a barchart with plotly

function barchart_plotly(&$data,&$colors,$metric){

    echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
    echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

    $rgba = array();
    foreach ($colors as $key => $value){
        list($red,$green,$blue) = modelcolor_rgb($value);
        array_push($rgba,"rgba($red,$green,$blue,1)");
    }

    echo('const xArray = ["');
    echo(implode('","',array_keys($data)));
    echo('"];');

    echo('const yArray = ["');
    echo(implode('","',array_values($data)));
    echo('"];');

    echo('const colors = ["');
    echo(implode('","',array_values($rgba)));
    echo('"];');
    
    echo("const data = [{");
    echo("x:xArray,");
    echo("y:yArray,");
    echo('type:"bar",');
    echo('orientation:"v",');
    echo('marker: {color: colors}');
    echo("}];\n");

    echo("const layout = {yaxis:{title: '$metric'}};");
    echo('Plotly.newPlot("myPlot", data, layout);');

    echo('</script>');
}


?>
