<?php



function plot_model_scores_plotly(){
    global $langpair, $benchmark, $metric, $model, $package, $showlang;
    global $show_max_scores;
    
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
    list($data,$colors) = get_model_scores_from_lines($lines, $model, $benchmark, $showlang, $show_max_scores);
    barchart_plotly($data, $colors, $metric);
}


function plot_topscores_plotly(){
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend;
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
    list($data,$type) = get_topscores_from_lines($lines);
    barchart_plotly($data, $type, $metric);
}

function plot_benchmark_scores_plotly($chart="barchart"){
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend;
    $lines = read_model_scores($langpair, $benchmark, $metric, $model, $package);
    if ($chart == 'scatterplot'){
        list($data,$size) = get_benchmark_scores_from_lines($lines, $benchmark, 'size');
        scatterplot_plotly($data, $size, $metric, $chartlegend);
    }
    else{
        list($data,$type) = get_benchmark_scores_from_lines($lines, $benchmark, $chartlegend);
        barchart_plotly($data, $type, $metric);
    }
}

function plot_score_comparison_plotly($chart="barchart"){    
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend, $showlang;
    
    $model1 = get_param('model1', 'unknown');
    $model2 = get_param('model2', 'unknown');
    $userscores = get_param('userscores', 'no');

    $data = array();
    if ($model1 != 'unknown' and $model2 != 'unknown'){
        $parts = explode('/',$model1);
        $pkg1 = array_shift($parts);
        $name1 = implode('/',$parts);
        $lines1 = read_scores($langpair, 'all', $metric, $name1, $pkg1);

        $parts = explode('/',$model2);
        $pkg2 = array_shift($parts);
        $name2 = implode('/',$parts);
        $lines2 = read_scores($langpair, 'all', $metric, $name2, $pkg2);

        $alldata = array();
        $allcolors = array();
        list($alldata[0], $allcolors[0]) = get_model_scores_from_lines($lines1, $model1, $benchmark, $showlang);
        list($alldata[1], $allcolors[1]) = get_model_scores_from_lines($lines2, $model2, $benchmark, $showlang);
        foreach ($alldata[0] as $key => $val){
            if (array_key_exists($key, $alldata[1])){
                $data[0][$key] = $alldata[0][$key];
                $data[1][$key] = $alldata[1][$key];
            }
        }

        $colors = array('blue', 'orange');
        $groups = array('model 1', 'model 2');
    }
    elseif ($model == 'top'){
        $lines1 = read_scores($langpair, 'all', $metric, 'all', 'opusmt', 'scores');
        $lines2 = read_scores($langpair, 'all', $metric, 'all', 'external', 'external-scores');

        $type = array();
        list($data[0],$type[0]) = get_topscores_from_lines($lines1);
        list($data[1],$type[1]) = get_topscores_from_lines($lines2);
        
        $colors = array('blue', 'grey');
        $groups = array('OPUS-MT', 'external');

        if ($userscores == "yes" && $chart != 'diff'){
            if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
                $lines3 = read_scores($langpair, 'all', $metric, 'all', 'contributed', 'user-scores');
                list($data[2],$type[2]) = get_topscores_from_lines($lines3);
                array_push($colors, 'purple');
                array_push($groups, 'contributed');
            }
        }
    }

    if ($chart == 'diff'){
        $color = array();
        $diff = array();
        foreach ($data[0] as $key => $value){
            if (array_key_exists($key, $data[1])){
                $diff[$key] = $data[0][$key] - $data[1][$key];
                if ($diff[$key] < 0 ){
                    $color[$key] = $colors[1];
                }
                else{
                    $color[$key] = $colors[0];
                }
            }
        }
        barchart_plotly($diff,$color, $metric);
    }
    else{
        compare_barchart_plotly($data,$colors, $groups, $metric);
    }
}




function get_topscores_from_lines(&$lines){
    $data = array();
    $type = array();
    foreach($lines as $line) {
        $array = explode("\t", rtrim($line));
        $data[$array[0]] = $array[1];
        $type[$array[0]] = model_color($array[count($array)-1], $array[2]);        
    }
    return [$data,$type];
}

function get_model_scores_from_lines(&$lines, $model, $benchmark='all', $showlang='all', $show_max_scores=0){
    
    list($modelid, $modelurl) = normalize_modelname($model);
    $color = modelid_color($modelid);

    $data = array();
    $colors = array();

    $nrscores=0;
    foreach($lines as $line) {
        if ($show_max_scores > 0 && $nrscores > $show_max_scores){
            break;
        }
        $array = explode("\t", rtrim($line));
        $key = $array[1].'/'.$array[0];
        if ($showlang != 'all'){
            if ($showlang != $array[0]){
                continue;
            }
            $key = $array[1];
        }
        if ($benchmark != 'all'){
            if ($array[1] != $benchmark){
                continue;
            }
            $key = $array[0];
        }
            
        $score = (float) $array[2];        
        $data[$key] = $score;
        $colors[$key] = $color;
        
        $nrscores++;
    }
    return [$data,$colors];
}


function get_benchmark_scores_from_lines(&$lines, $benchmark, $chartlegend='type'){
    
    // first line is a header for averaged scores
    if ($benchmark == 'avg'){
        $averaged_benchmarks = array_shift($lines);
    }

    $data = array();
    $type = array();
    foreach($lines as $line) {
        $array = explode("\t", rtrim($line));
            
        list($modelid, $modelurl) = normalize_modelname($array[1]);        
        $data[$modelid] = $array[0];
        
        if ($chartlegend == 'size'){
            $type[$modelid] = model_size($array[count($array)-1], $modelid);
        }
        else{
            $type[$modelid] = model_color($array[count($array)-1], $modelid);
        }
    }
    $data = array_reverse($data, true);
    $type = array_reverse($type, true);
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
    if (array_key_exists((String) $type, $barColors)){
        return $barColors[$type];
    }
    else{
        // $value = ceil($type);
        return size_to_rgb($type);
    }
}



function compare_barchart_plotly(&$data, &$color, &$group, $metric){

    echo("\n");
    echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
    echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

    $rgba = array();        
    foreach ($color as $key => $value){
        list($red,$green,$blue) = modelcolor_rgb($value);
        array_push($rgba,"rgba($red,$green,$blue,1)");
    }
    
    echo('var data = [];');
    for ($i = 0; $i < count($data); $i++){
        echo("data[$i] = { x: ['");
        echo(implode("','",array_keys($data[$i])));
        echo("'], y: ['");
        echo(implode("','",array_values($data[$i])));
        echo("'],");
        echo("text: ['");
        echo(implode("','",array_keys($data[$i])));
        echo("'],");
        echo('textposition: "auto",');
        echo("type: 'bar', name: '".$group[$i]."',");
        echo("marker: { color: '".$rgba[$i]."' } };");
    }

    echo("var layout = {
	yaxis: { title: '$metric' },
    xaxis: { visible: false },
    margin: {
         l: 50,
         r: 50,
         b: 50,
         t: 50,
         pad: 4 },
	barmode: 'group' };");

    echo("Plotly.newPlot('myPlot', data, layout);");
    echo('</script>');
}


// plot a barchart with plotly

function barchart_plotly(&$data,&$colors,$metric){
    global $benchmark;
    
    echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
    echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

    $rgba = array();
    $labels = array();
    $text = array();

    $i=0;
    foreach ($colors as $key => $value){
        list($red,$green,$blue) = modelcolor_rgb($value);
        array_push($rgba,"rgba($red,$green,$blue,1)");
        if ($benchmark != 'all'){
            $path = explode('/',$key);
            array_shift($path);
            array_push($text,implode('/',$path));
        }
        else{
            array_push($text,$key);
        }
        array_push($labels,"&nbsp;$i");
        $i++;
    }

    echo("\n".'const xArray = ["');
    // echo(implode('","',array_keys($data)));
    echo(implode('","',$labels));
    echo('"];');

    echo('const yArray = ["');
    echo(implode('","',array_values($data)));
    echo('"];');

    echo("\n".'const text = ["');
    echo(implode('","',$text));
    // echo(implode('","',array_keys($data)));
    echo('"];');

    echo('const colors = ["');
    echo(implode('","',array_values($rgba)));
    echo('"];');
    
    echo("const data = [{");
    echo("x:xArray,");
    echo("y:yArray,");
    echo("text:text,");
    echo('type:"bar",');
    echo('textposition: "auto",');
    // echo('orientation:"v",');
    echo('marker: {color: colors}');
    echo("}];\n");

    // echo("const layout = {yaxis:{title: '$metric'}, xaxis: { tickangle: -45 }};");
    echo("const layout = {
yaxis:{title: '$metric'},
margin: {
    l: 50,
    r: 50,
    b: 50,
    t: 50,
    pad: 4 }
};");
    echo('Plotly.newPlot("myPlot", data, layout);');
    //xaxis: { tickangle: -45 },
    //xaxis: { nticks: 50, tickmode: 'auto' },
    echo('</script>');
}


// plot a barchart with plotly

function scatterplot_plotly(&$data, &$colors, $metric, $chartlegend='type'){

    echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
    echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

    $score = array();
    $size = array();
    $rgba = array();
    $labels = array();

    foreach ($data as $key => $value){
        if ($colors[$key] != 'grey'){
            $color = $chartlegend == 'type' ? modelid_color($key) : $colors[$key];
            list($red,$green,$blue) = modelcolor_rgb($color);
            array_push($rgba,"rgba($red,$green,$blue,1)");
            array_push($labels,$key);
            array_push($score,$value);
            array_push($size,$colors[$key]);
        }
    }
   
    echo('const xArray = ["');
    echo(implode('","',$size));
    echo('"];');

    echo('const yArray = ["');
    echo(implode('","',$score));
    echo('"];');

    echo('const colors = ["');
    echo(implode('","',array_values($rgba)));
    echo('"];');

    echo('const labels = ["');
    echo(implode('","',$labels));
    echo('"];');

    
    echo("const data = [{");
    echo("x: xArray,");
    echo("y: yArray,");
    echo("text: labels,");
    // echo("mode: 'markers+text',textposition: 'bottom center',");
    echo("mode: 'markers',");
    echo('type: "scatter",');
    // echo('orientation: "v",');
    echo('marker: {size: 12, color: colors, line: {color: "rgb(255,255,255)", width: 1} }');
    echo("}];\n");

    // echo("const layout = {yaxis:{title: '$metric'}, xaxis: { tickangle: -45 }};");
    // echo("const layout = {showlegend: false, showtitle: false, yaxis:{title: '$metric'}, xaxis: { type: 'log', title: 'size in millions of parameters (log scale)'}};");
    echo("const layout = {
yaxis:{title: '$metric'},
xaxis: { type: 'log', title: 'size in millions of parameters (log scale)'},
margin: {
    l: 50,
    r: 50,
    b: 50,
    t: 50,
    pad: 4 }
};");
    echo('Plotly.newPlot("myPlot", data, layout);');

    echo('</script>');
}


?>
