<?php
header('HTTP/1.0 403 Forbidden');
exit;
require_once "config.php";

header('Access-Control-Allow-Origin: *');  
header('Content-Type: application/json');

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

$data = $database->query("SELECT *, COUNT(*) AS 'beacons', MAX(`time`) as 'time' FROM `track` WHERE `time`>(UNIX_TIMESTAMP()-120) AND `guid`!='' GROUP BY `guid` ORDER BY `count` DESC;")->fetchAll();

// Someone with better SQL knowlege might be able to do all this in one query, but since
//   we're relying on a derived table above right now we'll just make this two queries
function getGuid($r) { return $r['guid']; }
$guids = array_map(getGuid, $data);
$rooms = $database->select('rooms',['guid','room','tier','parent','child0','child1'],['OR'=>['guid'=>$guids,'parent'=>$guids]]);
$guids = array_map(getGuid, $rooms);
$rooms = array_combine($guids,$rooms);

$output = array();
foreach($data as $row)
{
	// Retrieve Tier and Room information
	$tier = '?';
	$children = array();
	$child0 = '??';
	$child1 = '??';
	if(!empty(@$rooms[$row['guid']]))
	{
		$room = $rooms[$row['guid']];
		$tier = $room['tier'];
		$child0 = $rooms[$room['child0']];
		$child1 = $rooms[$room['child1']];
		if(!empty($room['child0'])) array_push($children,$room['child0']);
		if(!empty($room['child1'])) array_push($children,$room['child1']);
		$child0 = empty($child0)?"??":$child0['room'];
		$child1 = empty($child1)?"??":$child1['room'];
	}
	$d = array(
		"guid" => $row['guid'],
		"room" => $row['room'],
		"tier" => $tier,
		"children" => $children,
		"child_names" => array($child0,$child1),
		"count" => $row['count'],
		"grow" => $row['grow'],
		"stay" => $row['stay'],
		"abandon" => $row['abandon'],
		"novote" => $row['novote'],
		"formation" => $row['formation'],
		"reap" => $row['reap'],
		"time" => $row['time'],
		"beacons" => $row['beacons'],
	);
	array_push($output,$d);
}

echo json_encode($output);

?>
