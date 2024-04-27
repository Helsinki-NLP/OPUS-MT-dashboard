<?php

// more info on plotly:
// https://plotly.com/javascript/line-and-scatter/
// https://plotly.com/javascript/bar-charts


function plot_model_scores_plotly(){
    global $langpair, $benchmark, $metric, $model, $package, $showlang;
    global $show_max_scores;

    $scores = get_model_scores($model, $metric, $package, $benchmark, $showlang, $show_max_scores);
    $colors = array();
    $color = modelid_color($model);
    foreach ($scores as $test => $score){
        $colors[$test] = $color;
    }
    barchart_plotly($scores, $colors, $metric);
}


function plot_topscores_plotly(){
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend;
    list($scores,$models) = get_topscores($langpair, $metric, $package);
    $colors = array();
    foreach ($models as $test => $modelid){
        $colors[$test] = modelid_color($modelid);
    }
    barchart_plotly($scores, $colors, $metric);
}

function plot_benchmark_scores_plotly($chart="barchart"){
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend;

    $scores = get_benchmark_scores($langpair, $benchmark, $metric, $package, $model);
    $scores = array_reverse($scores, true);
    
    $data = array();
    $type = array();
    foreach ($scores as $key => $value){
        list($pkg,$modelid) = explode("\t",$key);
        $data[$modelid] = $value;
        if ($chartlegend == 'size' || $chart == 'scatterplot'){
            $type[$modelid] = model_size($pkg,$modelid);
        }
        else{
            $type[$modelid] = modelid_color($modelid,$pkg);
        }
    }
    if ($chart == 'scatterplot'){
        scatterplot_plotly($data, $type, $metric, $chartlegend);
    }
    else{
        barchart_plotly($data, $type, $metric);
    }
}

function plot_score_comparison_plotly($chart="barchart"){    
    global $langpair, $benchmark, $metric, $model, $package, $chartlegend, $showlang;
    global $show_max_scores;
    
    $model1 = get_param('model1', 'unknown');
    $model2 = get_param('model2', 'unknown');
    $userscores = get_param('userscores', 'no');

    $data = array();
    if ($model1 != 'unknown' and $model2 != 'unknown'){
        $parts = explode('/',$model1);
        $pkg1 = array_shift($parts);
        $name1 = implode('/',$parts);

        $parts = explode('/',$model2);
        $pkg2 = array_shift($parts);
        $name2 = implode('/',$parts);

        $alldata = array();
        $alldata[0] = get_model_scores($name1, $metric, $pkg1, $benchmark, $showlang);
        $alldata[1] = get_model_scores($name2, $metric, $pkg2, $benchmark, $showlang);
        
        $count = 0;
        foreach ($alldata[0] as $key => $val){
            if (array_key_exists($key, $alldata[1])){
                $data[0][$key] = $alldata[0][$key];
                $data[1][$key] = $alldata[1][$key];
                if ($count > $show_max_scores){
                    break;
                }
                $count++;
            }
        }

        $colors = array('blue', 'orange');
        $groups = array('model 1', 'model 2');
    }
    elseif ($model == 'top'){
        
        $models = array();
        list($data[0],$models[0]) = get_topscores($langpair, $metric, 'opusmt');
        list($data[1],$models[1]) = get_topscores($langpair, $metric, 'external');
        
        $colors = array('blue', 'grey');
        $groups = array('OPUS-MT', 'external');

        if ($userscores == "yes" && $chart != 'diff'){
            if (local_scorefile_exists($langpair, 'all', $metric, 'all', 'contributed', 'user-scores')){
                list($data[2],$models[2]) = get_topscores($langpair, $metric, 'contributed');
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
        barchart_plotly($diff,$color, "difference in ".$metric);
    }
    else{
        compare_barchart_plotly($data,$colors, $groups, $metric);
    }
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
            if (count($path)>1){
                array_shift($path);
                array_push($text,implode('/',$path));
            }
            else{
                array_push($text,$key);
            }
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
    echo('marker: {size: 15, color: colors, line: {color: "rgb(255,255,255)", width: 1} }');
    echo("}];\n");

    // echo("const layout = {yaxis:{title: '$metric'}, xaxis: { tickangle: -45 }};");
    // echo("const layout = {showlegend: false, showtitle: false, yaxis:{title: '$metric'}, xaxis: { type: 'log', title: 'size in millions of parameters (log scale)'}};");
    echo("const layout = {
yaxis:{title: '$metric'},
xaxis: { type: 'log', title: 'size in millions of parameters'},
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
