<?php
require_once "config.php";

$start_time = explode(' ',microtime());
$start_time = $start_time[0] + $start_time[1];

// Cache this page for 4s
$ts = gmdate('D, d M Y H:i:s ',(time()&0xfffffffc)) . 'GMT';
$etag = '"'.md5($ts).'"';

$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
if($if_none_match && $if_none_match == $etag)
{
	header('HTTP/1.1 304 Not Modified');
	exit();
}

header('Last-Modified: ' . $ts);
header("ETag: $etag");
?>
<?php
//$data = json_decode(file_get_contents("dump/pedigree.json"));
$data = unserialize(file_get_contents('dump/pedigree.bin'));

$guid = htmlspecialchars(@$_GET['guid']);
?>
<!doctype html>
<html>
<head>
    <title>RobinGrapher: <?=$data[$guid]['room']?></title>
    <link rel='shortcut icon' id='robin-icon' type='image/png' href='robin.png' />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open Sans:300italic,400italic,600italic,700italic,400,300,600,700">
    <style>
    * { margin: 0; padding: 0; }
    body {
        font-size: 15px;
        font-family: "Open Sans", sans-serif;
        background: #f4f4f4;
    }
    
    p { margin: 5px 0; }
    
    svg {
        margin: 20px;
        background: white;
        box-shadow: 0 -1px 0 #efefef,0 0 2px rgba(0,0,0,0.16),0 1.5px 4px rgba(0,0,0,0.18);
    }
    
    #info {
        display: block;
        padding: 10px 20px;
    }
    .title {
        font-weight: 600;
    }
    
    #more {
        display: flex;
        position: fixed;
        top: 10px;
        right: 20px;
        color: gray;
        z-index: 10;
    }
    
    #details #details-hover {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        font-size: 12px;
        width: 350px;
        background: white;
        padding: 10px;
        box-shadow: 0 -1px 0 #efefef,0 0 2px rgba(0,0,0,0.16),0 1.5px 4px rgba(0,0,0,0.18)
    }
    #details:hover #details-hover {
        display: block;
    }
    
    #expand-button {
        margin-right: 20px
    }
    #expand-button:hover {
        text-decoration: underline;
        cursor: pointer;
    }
    
    #vis_svg[data-expanded="yes"] {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 5;
        margin: 0;
    }
    
    @media (max-width: 480px) {
        svg {
            margin-left: 0;
            margin-right: 0;
            margin-bottom: 0;
            width: 100%;
        }
        
        #more {
            position: relative;
            top: 0;
            right: 0;
            padding: 8px 20px;
            background: rgba(0,0,0,0.055);
        }
    }
    </style>
    <script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
    <script src="vis_main.js" charset="utf-8"></script>
    <script>
        var data = [
<?php
$nodes = array($guid);
while(count($nodes))
{
    $n = $data[array_shift($nodes)];
    
    $parent = 'null';
    $name = $n['room'].' ('.$n['tier'].')';
    
    if(!empty($n['parent']))
    {
        $parent = $data[$n['parent']]['room'].' ('.$data[$n['parent']]['tier'].')';
    }
    echo "            //".$n['guid']."\n";
    echo "            {\n";
    echo "                name: '".$name."',\n";
    echo "                parent: '".$parent."',\n";
    echo "            },\n";

    if (isset($n['children'][0]))
    {
        $child0 = $n['children'][0];
        if(!empty($child0))
        {
            array_push($nodes,$child0);
        }
    }
    
    if (isset($n['children'][1]))
    {
        $child1 = $n['children'][1];
        if(!empty($child1))
        {
            array_push($nodes,$child1);
        }
    }
}
?>
        ];
    </script>
    <script>
    var vis;
    
    document.addEventListener('DOMContentLoaded', function() {
        vis = new vis_main('#vis_svg');
        vis.setData(data)
            .setZoomInit(0.6)
            .start(6);
            
        var original_dimensions = vis.getDimensions();
        
        function vis_expand_toggle() {
            var vis_el = document.getElementById('vis_svg');
            if (vis_el.getAttribute('data-expanded').toLowerCase() == 'yes') {
                vis_el.setAttribute('data-expanded', 'no');
                document.getElementById('expand-button').innerHTML = "Expand graph";
                
                d3.select('#vis_svg').attr('width',  original_dimensions[0])
                                     .attr('height', original_dimensions[1]);
                
                history.replaceState(undefined, undefined, "#");
            } else {
                vis_el.setAttribute('data-expanded', 'yes');
                document.getElementById('expand-button').innerHTML = "Contract graph";
                
                d3.select('#vis_svg').attr('width',  vis.getViewportDimensions()[0])
                                     .attr('height', vis.getViewportDimensions()[1]);
                
                history.replaceState(undefined, undefined, "#expanded");
            }
        }
        
        if (window.location.hash) {
            var hash = window.location.hash.substring(1).toLowerCase();
            if (hash == 'expanded') {
                vis_expand_toggle();
            }
        }
        
        document.getElementById('expand-button').addEventListener('click', vis_expand_toggle, false);
    });
    </script>
</head>
<body>
    <div id="more">
        <div id="expand-button">Expand graph</div>
        <div id="details">
            <span>(?)</span>
            <div id="details-hover">
                <p>A visualization of the <?php echo $data[$guid]['room'] ?> family tree</p>
                <p>Graph generation code created by /u/kwwxis</p>
                <p>Modified by /u/GuitarShirt to use RobinTracker data</p>
                <p><?php
                $end_time = explode(' ',microtime());
                $total_time = ($end_time[0] + $end_time[1]) - $start_time;
                printf("Page generation took %.3fs",$total_time);
                ?></p>
            </div>
        </div>
    </div>
    <div id="info">
        <p class="title"><?=$data[$guid]['room']?> (Tier <?=$data[$guid]['tier']?>)</p>
        <p>Click nodes to toggle. Zoom with scroll. Pan with mouse drag.</p>
        <p>Right-click nodes to collapse/expand all nodes under it.</p>
    </div>
    <svg id="vis_svg" data-expanded="no"></svg>
    <script>
<?=@$footer?>
</body>
</html>
