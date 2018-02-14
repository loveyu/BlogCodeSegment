<?php
/**
 * User: loveyu
 * Date: 2018/2/13
 * Time: 23:26
 */
require_once __DIR__."/../../../init.php";

$obj = file_get_contents(__DIR__."/demo.json");
$obj = json_decode($obj, true);
$apikey = isset($argv[1]) ? $argv[1] : "{API_KEY}";
foreach($obj as $value) {
	$id = $value['id'];
	echo "curl -f -o {$id}.zip --proxy \"".HTTP_PROXY_URL."\" --url \"http://wambachers-osm.website/boundaries/exportBoundaries?cliVersion=1.0&cliKey={$apikey}&exportFormat=shp&exportLayout=levels&exportAreas=water&union=false&selected={$id}\"\n";
}