<?php

// more info on plotly:
// https://plotly.com/javascript/line-and-scatter/
// https://plotly.com/javascript/bar-charts


class GraphicsPlotly extends Graphics{

    public function plot_model_scores(&$scores){
        global $langpair, $benchmark, $metric, $model, $package, $showlang;
        global $show_max_scores;

        $colors = array();
        $color = $this->modelid_color($model);
        foreach ($scores as $test => $score){
            $colors[$test] = $color;
        }
        $this->barchart($scores, $colors, $metric);
        echo parent::plot_model_scores($scores);
    }


    public function plot_topscores(&$scores, &$models){
        global $langpair, $benchmark, $metric, $model, $package, $chartlegend;
        $colors = array();
        foreach ($models as $test => $modelid){
            $colors[$test] = $this->modelid_color($modelid);
        }
        $this->barchart($scores, $colors, $metric);
        echo parent::plot_topscores($scores, $models);
    }

    public function plot_benchmark_scores(&$scores, $chart="barchart", $chartlegend='type'){
        global $langpair, $benchmark, $metric, $model, $package;

        $reverse = array_reverse($scores, true);
    
        $data = array();
        $type = array();
        foreach ($reverse as $key => $value){
            list($pkg,$modelid) = explode("\t",$key);
            // echo("$pkg - $modelid<br/>");
            $data[$modelid] = $value;
            if ($chartlegend == 'size' || $chart == 'scatterplot'){
                $type[$modelid] = model_size($pkg,$modelid);
            }
            else{
                $type[$modelid] = $this->modelid_color($modelid,$pkg);
            }
        }
        if ($chart == 'scatterplot'){
            $this->scatterplot($data, $type, $metric, $chartlegend);
        }
        else{
            $this->barchart($data, $type, $metric);
        }
        echo parent::plot_benchmark_scores($scores, $chart, $chartlegend);
    }


    // compare scores of top OPUS-MT with top external models (and contributed translations)

    public function plot_topscore_comparison(&$data, &$models, $metric='bleu', $chart="barchart"){

        $colors = array('blue', 'grey', 'purple');
        $groups = array('OPUS-MT', 'external', 'contributed');

        $this->plot_comparison_chart($data, $colors, $groups, $chart, $metric);
        echo parent::plot_topscore_comparison($data, $models, $metric, $chart);
    }



    // compare scores of two models

    public function plot_model_comparison(&$alldata, $metric='bleu', $chart="barchart"){
        global $show_max_scores;
    
        $data = array();        
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
        $this->plot_comparison_chart($data, $colors, $groups, $chart, $metric);
        echo parent::plot_model_comparison($chart);
    }


    // plot a chart for comparing 2 sets of scores (either score differences or bars next to each other)

    private function plot_comparison_chart(&$data, &$colors, &$groups, $chart="barchart", $metric='bleu'){
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
            $this->barchart($diff,$color, "difference in ".$metric);
        }
        else{
            $this->compare_barchart($data,$colors, $groups, $metric);
        }
    }




    // either get RGB values for a given pre-defined color
    // or convert model size into RGB values of a corresponding color

    private function modelcolor_rgb($type){
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



    private function compare_barchart(&$data, &$color, &$group, $metric){

        echo("\n");
        echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
        echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');
        
        $rgba = array();        
        foreach ($color as $key => $value){
            list($red,$green,$blue) = $this->modelcolor_rgb($value);
            array_push($rgba,"rgba($red,$green,$blue,1)");
        }
    
        echo('var data = [];');
        for ($i = 0; $i < count($data); $i++){
            if (count($data[$i]) > 0){
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

    private function barchart(&$data,&$colors,$metric){
        global $benchmark;
    
        echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
        echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

        $rgba = array();
        $labels = array();
        $text = array();

        $i=0;
        foreach ($colors as $key => $value){
            list($red,$green,$blue) = $this->modelcolor_rgb($value);
            array_push($rgba,"rgba($red,$green,$blue,1)");
            if ($benchmark != 'all'){
                $path = explode('/',$key);
                // if (count($path)>1){
                if ($path[0]=='huggingface' or $path[0]=='Tatoeba-MT-models' or $path[0]=='OPUS-MT-models' or $path[0]=='HPLT-MT-models'){
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

    private function scatterplot(&$data, &$colors, $metric, $chartlegend='type'){

        echo('<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>');
        echo('<div id="myPlot" style="width:200%;max-width:680px;max-height:400px"></div><script>');

        $score = array();
        $size = array();
        $rgba = array();
        $labels = array();

        foreach ($data as $key => $value){
            if ($colors[$key] != 'grey'){
                $color = $chartlegend == 'type' ? $this->modelid_color($key) : $colors[$key];
                list($red,$green,$blue) = $this->modelcolor_rgb($color);
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

}

?>
