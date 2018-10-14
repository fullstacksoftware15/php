<?php
require_once __DIR__."/../config.php";

$start_time = explode(' ',microtime());
$start_time = $start_time[0] + $start_time[1];

$data = $database->query("SELECT `ip`, `guid`, `room`, MIN(`min_count`) as 'min_count', MAX(`max_count`) as 'max_count', `grow`, `stay`, `abandon`, `novote`, `formation`, `reap`, MIN(`start_time`) as 'start_time', MAX(`end_time`) as 'end_time', SUM(`beacons`) as 'beacons' FROM (SELECT *, MIN(`count`) as 'min_count', MAX(`count`) as 'max_count', MIN(`time`) as 'start_time', MAX(`time`) as 'end_time', COUNT(*) as 'beacons' FROM `track_storage` WHERE `guid`!='' GROUP BY `ip`, `guid` UNION SELECT *,  MIN(`count`) as 'min_count', MAX(`count`) as 'max_count', MIN(`time`) as 'start_time', MAX(`time`) as 'end_time', COUNT(*) as 'beacons' FROM `track` WHERE `guid`!='' GROUP BY `ip`, `guid`) AS A GROUP BY `ip`, `guid` ORDER BY `ip`, `start_time`");

$rooms = array();
$currentIP = '';
$userRooms = array();
$rejectedUsers = 0;

// Validate this user's data looks legit
//   In practice, it means that they were only
//   contributing data for one room
function validateUser($userRooms)
{
	$length = count($userRooms);
	$lastEnd = 0;
	for($i=0;$i<$length;$i++)
	{
		$start = $userRooms[$i]['start_time'];
		$end = $userRooms[$i]['end_time'];

		$beacon_max = (($end - $start)/60)+1;
		$beacon_min = intval($beacon_max*0.2);

		// Throw away entries from users who were intermittently contributing
		//   The most likely case is someone using multiple alt accounts or NAT
		$beacons = $userRooms[$i]['beacons'];
		if($beacons < $beacon_min)
		{
			return false;
		}

		// Check for overlapping times for this user (again users contributing
		//   stats from multiple accounts)
		for($j=$i;$j<$length;$j++)
		{
			$time = $userRooms[$j]['start_time'];
			if($time>$start && $time<$end)
			{
				return false;
			}
		}
	}
	return true;	
}

// Look through all the rooms for a single user
function parseUserRooms($userRooms)
{
	global $rooms, $rejectedUsers;

	if(!validateUser($userRooms))
	{
		$rejectedUsers++;
		return;
	}

	$currentTier = 1;
	$lastEndTime = 0;
	$lastCount = 0;

	foreach($userRooms as $row)
	{
		$ip = $row['ip'];
		$guid = $row['guid'];
		$room = $row['room'];
		$start_time = $row['start_time'];
		$end_time = $row['end_time'];
		$count = $row['count'];
		$max_count = $row['max_count'];
		$min_count = $row['min_count'];
		$grow = $row['grow'];
		$stay = $row['stay'];
		$abandon = $row['abandon'];
		$novote = $row['novote'];
		$ft = $row['formation'];
		$rt = $row['reap'];

		// Specifically fixes the invalid HoMi-Pcdbe to Gai_smOrWr transition
		//  My guess is that this is related to DHCP assigning an existing
		//  contributor a new IP address
		// If there was an hour break between these two rooms: reset
		if($lastEndTime!=0 && $start_time - $lastEndTime > 60*60)
		{
			$currentTier = 1;
			$lastRoom = "";
		}

		// We need to change the query to order by time before we can do this
		/*
		if($count < $lastCount)
		{
			$currentTier = 1;
			$lastRoom = "";
		}
		*/

		// User based tiering
		if($count==2)
		{
			$currentTier = 1;
		}

		// Time based tiering
		$dt = $rt-$ft;
		if($dt==120)
		{
			$currentTier = 1;
		}
		if($dt==240)
		{
			$currentTier = 2;
		}
		if($dt==480)
		{
			$currentTier = 3;
		}
		if($dt==960)
		{
			$currentTier = 4;
		}
		if($dt==1920 && $currentTier<5)
		{
			$currentTier = 5;
		}
		if($dt>1920)
		{
			$lastRoom = "";
			$currentTier = 1;
			$lastCount = 0;
			$lastEndTime = 0;
			continue;
		}

		if($currentTier==1 || $rooms[$lastRoom]['tier']>$currentTier)
		{
			$currentTier = 1;
			$lastRoom = "";
		}

		if(!array_key_exists($guid,$rooms))
		{
			$rooms[$guid] = array(
				"guid" => $guid,
				"room" => $room,
				"count" => $count,
				"max_count" => $max_count,
				"min_count" => $min_count,
				"grow" => $grow,
				"stay" => $stay,
				"abandon" => $abandon,
				"novote" => $novote,
				"formation_time" => $ft,
				"reap_time" => $rt,
				"start_time" => $start_time,
				"end_time" => $end_time,
				"tier" => $currentTier,
				"children" => [],
				"parent" => array()
			);
		}
		if(!empty($lastRoom))
		{
			//$rooms[$lastRoom]['parent'] = $guid;
			//array_push($rooms[$lastRoom]['parent'],$guid);
			//if(!in_array($lastRoom,$rooms[$guid]['children']))
			{
				array_push($rooms[$guid]['children'],$lastRoom);
			}
		}
		if($rooms[$guid]['end_time'] < $end_time)
		{
			$rooms[$guid]['room'] = $room;
			$rooms[$guid]['count'] = $count;
			$rooms[$guid]['grow'] = $grow;
			$rooms[$guid]['stay'] = $stay;
			$rooms[$guid]['abandon'] = $abandon;
			$rooms[$guid]['novote'] = $novote;
			$rooms[$guid]['formation_time'] = $ft;
			$rooms[$guid]['reap_time'] = $rt;
			$rooms[$guid]['end_time'] = $end_time;
		}
		if($rooms[$guid]['start_time'] > $start_time)
		{
			$rooms[$guid]['start_time'] = $start_time;
		}
		if($rooms[$guid]['tier'] < $currentTier)
		{
			$rooms[$guid]['tier'] = $currentTier;
		}
		if($rooms[$guid]['min_count'] > $min_count)
		{
			$rooms[$guid]['min_count'] = $min_count;
		}
		if($rooms[$guid]['max_count'] < $max_count)
		{
			$rooms[$guid]['max_count'] = $max_count;
		}

		$lastRoom = $guid;
		$lastEndTime = $end_time;
		$lastCount = $count;
		$currentTier++;
	}	
}

// Loop and collect by user
foreach($data as $row) {
	$ip = $row['ip'];

	if($currentIP != $ip)
	{
		parseUserRooms($userRooms);
		$userRooms = array();
		$currentIP = $ip;
	}

	array_push($userRooms,$row);
}

// In the mess, we missed the soKukuneli -> ccKufiPrFa transition
array_push($rooms['54b10078-fd0b-11e5-b154-0e31fc1b0d95']['children'],'95f64b68-f9e8-11e5-bf4f-0e31fc1b0d95');

// Do some post-processing to select parents/children
//   our algorithm here is just to take the most
//   common entry (simple and easy)
foreach($rooms as $r)
{
	$children = array_count_values($r['children']);
	asort($children);
	$children = array_slice(array_keys($children),count($children)-2);
	$rooms[$r['guid']]['children'] = $children;

	for($i=0;$i<count($children);$i++)
	{
		array_push($rooms[$children[$i]]['parent'],$r['guid']);
	}
}
foreach($rooms as $r)
{
	$parents = array_count_values($r['parent']);
	asort($parents);
	end($parents);
	$rooms[$r['guid']]['parent'] = key($parents);
}

// List of all GUIDs without parents
function isOrphan($var)
{
	return is_null($var['parent']);
}
$toprocess = array_keys(array_filter($rooms,isOrphan));

// Using these as our top, go through the rest of the list
//   adding children as we go
while(count($toprocess)!=0)
{
	$guid = array_shift($toprocess);
	foreach($rooms[$guid]['children'] as $child)
	{
		if($rooms[$child]['tier']<$rooms[$guid]['tier']-1)
		{
			$rooms[$child]['tier'] = $rooms[$guid]['tier']-1;
			$rooms[$child]['parent'] = $guid;
			array_push($toprocess,$child);
		}
	}
}

// MEDOO doesn't provide a way for us to do what we want here
//  $database->replace is actually an UPDATE query.

// Turn each member of the list into just whats
//   necessary for insertion
function readyForInsert($r)
{
	global $database;

	asort($r['children']);
	for($i=count($r['children']);$i<=2;$i++)
	{
		$r['children'][$i] = null;
	}

	$guid = $database->quote($r['guid']);
	$room = $database->quote($r['room']);
	$tier = $database->quote($r['tier']);
	$parent = $database->quote($r['parent']);
	$child0 = $database->quote($r['children'][0]);
	$child1 = $database->quote($r['children'][1]);

	return "($guid,$room,$tier,$parent,$child0,$child1)";
}

// This goes into the database
$data = array_map(readyForInsert,array_values($rooms));
$data = implode(",",$data);
$database->query("REPLACE INTO `rooms` (`guid`,`room`,`tier`,`parent`,`child0`,`child1`) VALUES $data;");
if(!empty($database->error()[1]))
{
	print_r($database->error());
	die();
}

// Write out the json dump
$f = fopen(__DIR__."/../dump/pedigree.json",'w') or die("Unable to open pedigree.json");
fwrite($f,json_encode($rooms));
fclose($f);

// Dump the PHP object
$f = fopen(__DIR__."/../dump/pedigree.bin",'w') or die("Unable to open pedigree.json");
fwrite($f,serialize($rooms));
fclose($f);
?>
