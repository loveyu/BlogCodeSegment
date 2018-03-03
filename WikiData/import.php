<?php
/**
 * User: loveyu
 * Date: 2018/3/3
 * Time: 14:13
 */
require_once __DIR__."/../vendor/autoload.php";

use Wikibase\JsonDumpReader\JsonDumpFactory;

$filesize = filesize($argv[1]);
if($filesize < 1) {
	die("error file");
}

$factory = new JsonDumpFactory();
$dumpReader = $factory->newExtractedDumpReader($argv[1]);

$num = 0;
$ignore = 0;


$medoo = new \Medoo\Medoo([
	'database_type' => 'pgsql',
	'database_name' => 'wiki_data',
	'server'        => '127.0.0.1',
	'port'          => '5433',
	'username'      => 'postgres',
	'password'      => '123456'
]);

$queue = [];
$queue_num = 0;

$insert_func = function() use (&$queue, &$queue_num, &$medoo) {
	if(empty($queue)) {
		$queue_num = 0;
		return 0;
	}
	$insert_sql = "INSERT INTO \"public\".\"item_tbl\" (\"id\", \"type\", \"labels\", \"descriptions\", \"aliases\", \"claims\", \"sitelinks\") VALUES ";
	$values = [];
	$i = 0;
	$bind_map = [];
	foreach($queue as $item) {
		$param = [];
		$n = 0;
		foreach($item as $value) {
			$i++;
			$n++;
			$k = "_{$i}_";
			$bind_map[$k] = [$value, PDO::PARAM_STR];
			$param[] = $n > 2 ? ":{$k}::json" : ":{$k}";
		}
		$values[] = implode(",", $param);
	}
	$insert_sql .= "(".implode("),(", $values).")";
	$stmt = $medoo->exec($insert_sql, $bind_map);
	$res = $stmt->rowCount();
	$stmt->closeCursor();

	//empty
	$queue = [];
	$queue_num = 0;

	return $res;
};


$i = 0;
$insert = 0;
do {
	if($i++ % 50 == 0) {
		$tell = $dumpReader->getPosition();
		printf("\rProcess: %0.5f, Num: %d, Ignore: %d, Insert: %d", $tell / $filesize * 100, $num, $ignore, $insert);
	}

	$line = $dumpReader->nextJsonLine();
	if(empty($line)) {
		break;
	}
	$obj = json_decode($line, true);

	if(empty($obj) || !isset($obj['type']) || !isset($obj['id']) || $obj['type'] !== "item") {
		$ignore++;
	}
	$num++;
	$queue[] = [
		$obj['id'],
		$obj['type'],
		json_encode($obj['labels']),
		json_encode($obj['descriptions']),
		json_encode($obj['aliases']),
		json_encode($obj['claims']),
		json_encode($obj['sitelinks']),
	];

	if($queue_num++ > 1000) {
		$insert += $insert_func();
	}
} while(!empty($line));

echo PHP_EOL;

$insert_func();