<?php
require_once __DIR__."/../config.php";
$data = $database->query("SELECT max(`id`) as 'id' FROM `track` WHERE `time`<(UNIX_TIMESTAMP()-120)")->fetchAll();
$id = $data[0]['id'];
$database->query("INSERT INTO `track_storage` SELECT * FROM `track` WHERE `id`<='$id'");
if(!empty($database->error()[1]))
{
	echo "Table move failed!\n";
	print_r($database->error());
}
else
{
	$database->query("DELETE FROM `track` WHERE `id`<='$id'");
}
?>
