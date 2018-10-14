<?php
// Generatea final page to display
require_once __DIR__."/../config.php";

$data = unserialize(file_get_contents(__DIR__.'/../dump/pedigree.bin'));
uasort($data,function($a,$b){return $a['tier']<$b['tier'];});
?>
<html>
<head>
<title>RobinTracker</title>
<link rel='shortcut icon' id='robin-icon' type='image/png' href='robin.png' />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
</head>
<body style="margin:16px;">
<h1>Robin Tracker</h1>
<?=$header?>
<table class='table table-striped'>
<thead><tr>
<td><b>Room</b></td>
<td><b>Tier</b></td>
<td><b>Constituent Rooms</b></td>
<td><b>Starting Users</b></td>
<td><b>End Users</b></td>
<td><b>Founded</b></td>
<td><b>Last Update</b></td>
</tr></thead>
<tbody>
<?foreach($data as $row):?>
<?php
// Count the number of updates
if(!empty(@$row['parent']))
{
	continue;
}
$roomCount++;

// Retrieve Tier and Room information
$tier = '?';
$child0 = '??';
$child1 = '??';
$tier = $row['tier'];
$child0 = $row['children'][0];
$child1 = $row['children'][1];
$child0 = empty($child0)?"??":$data[$child0]['room'];
$child1 = empty($child1)?"??":$data[$child1]['room'];

if($roomCount>10)
{
	break;
}

?>
<tr>
<td><b><a href='graph.php?guid=<?=htmlspecialchars($row['guid'])?>'><?=htmlspecialchars($row['room'])?></a></b></td>
<td><?=$tier?></td>
<td><?=htmlspecialchars($child0)?>, <?=htmlspecialchars($child1)?></td>
<td><?=$row['max_count']?></td>
<td><?=$row['min_count']?></td>
<td><?=date("Y-m-d H:i T",$row['formation_time']);?></td>
<td><?=date("Y-m-d H:i T",$row['end_time']);?></td>
</tr>
<?endforeach;?>
</tbody>
</table>
Thank you to everyone who contribute data using the <a href='https://raw.githubusercontent.com/jhon/robintracker/master/robintracker.user.js'>Standalone Userscript</a> or by enabling contribution in a compatible script like <a href='https://github.com/5a1t/parrot'>Parrot</a>, <a href='https://github.com/vartan/robin-grow'>Robin-Grow</a>, <a href='https://github.com/joefarebrother/leavebot'>Leavebot</a>, and <a href='https://github.com/keythkatz/Robin-Autovoter'>Robin-Autovoter</a>.<br />
Thanks to all the folks who shared my dream of making robin data accessible including, but not limited to,the <a href='https://www.reddit.com/r/robintracking/comments/4czzo2/robin_chatter_leader_board_official/'>Official Leader Board</a>, <a href='http://robintree-apr3.s3-website-us-east-1.amazonaws.com/'>RobinTree</a>, and <a href='http://justinhart.net/robintable/'>Robin Table</a>.<br />
Thank you to everyone who contributed to this project. You can find more information about the contributors (and the source for the leaderboard) on <a href='https://github.com/jhon/robintracker'>GitHub</a>!<br />
Thank you to <a href='http://www.dreamhost.com/'>DreamHost</a> for putting up with these shenanigans.<br />
<br />
<?=@$footer?>
</body>
 </html>
