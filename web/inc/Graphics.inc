<?php

include('inc/display_options.inc');
include('inc/Graphics/GD.inc');
include('inc/Graphics/Plotly.inc');


// other methods and info for plotting:
// https://blog.ckportfolio.com/tutorial/canvas-visualization-with-php-and-javascript-library
// https://www.w3schools.com/graphics/tryit.asp?filename=trychartjs_bars_colors_more
// https://www.w3schools.com/graphics/tryit.asp?filename=tryplotly_bars
// https://www.w3schools.com/graphics/default.asp


class Graphics{
    public static function new($type='plotly'){
        if ($type == 'gd')
            return new GraphicsGD();
        else
            return new GraphicsPlotly();
    }
    
    public function plot_model_scores(&$scores){
        $this->print_chart_legend();
    }
    
    public function plot_topscores(&$scores, &$models){
        echo('<ul>');
        echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
        print_renderlib_link();
        print_contributed_link();
        echo('</ul>');
    }
    public function plot_benchmark_scores(&$scores, $chart="barchart", $chartlegend='type'){
        $this->print_chart_legend($chartlegend);
    }
    
    public function plot_topscore_comparison(&$data, &$models, $metric='bleu', $chart="barchart"){
        echo('<ul>');
        echo('<li>blue = OPUS-MT / Tatoeba-MT models, grey = external models, purple = user-contributed</li>');
        print_renderlib_link();
        print_contributed_link();
        echo('</ul>');
    }
    
    public function plot_model_comparison(&$data, $metric='bleu', $chart="barchart"){
        echo('<ul>');
        echo('<li>blue = model 1, orange = model 2</li>');
        print_renderlib_link();
        echo('</ul>');
    }

    function print_chart_legend($chartlegend='type'){
        if ($chartlegend == 'size'){ print_size_legend(); }
        echo('<ul>');
        echo('<li>orange = OPUS-MT, blue = Tatoeba-MT models, red = HPLT-MT models</li>');
        echo('<li>green = student models, grey = external models, purple = user-contributed</li>');
        print_renderlib_link();
        print_contributed_link();
        echo('</ul>');
    }

    function modelid_color($modelid){
        $type2color = array( 'contributed' => 'purple',
                             'external'    => 'grey',
                             'Tatoeba-MT-models' => 'blue',
                             'OPUS-MT-models' => 'orange',
                             'HPLT-MT-models'    => 'darkred');
        if (strpos($modelid,'transformer-small') !== false){
            return 'green';
        }
        elseif (strpos($modelid,'transformer-tiny') !== false){
            return 'green';
        }
        $modelparts = explode('/',$modelid);
        return array_key_exists($modelparts[0], $type2color) ? $type2color[$modelparts[0]] : 'grey';
    }

    
}

?>
