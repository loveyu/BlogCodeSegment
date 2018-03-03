<?php
/**
 * User: loveyu
 * Date: 2018/3/3
 * Time: 14:13
 */
require_once __DIR__."/../vendor/autoload.php";

date_default_timezone_set("Asia/Shanghai");

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
	$insert_sql = "INSERT INTO \"public\".\"item_tbl\" (\"line_id\",\"id\", \"type\", \"labels\", \"descriptions\", \"aliases\", \"claims\", \"sitelinks\", \"other\") VALUES ";
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
			$param[] = $n > 3 ? ":{$k}::json" : ":{$k}";
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

$start_time = microtime(true);

echo "Start: ".date("Y-m-d H:i:s"), "\n";

$i = 0;
$insert = 0;
$agv = 0;
$left_time = 0;
$start_p = 0;
do {
	if($i++ % 100 == 0) {
		$tell = $dumpReader->getPosition();
		$p = $tell / $filesize * 100;
		if($i % 1000 === 1) {
			$ut = microtime(true) - $start_time;
			$agv = $insert / $ut;
			$left_time = @(($ut / (($p - $start_p) / 100)) * (1 - $start_p / 100) - $ut) / 3600;
		}
		printf("\rProcess: %0.5f, Num: %d, Ignore: %d, Insert: %d, Agv:%0.2fk, LF:%0.2fh", $p, $num, $ignore, $insert, $agv / 1000, $left_time);
	}

	$line = $dumpReader->nextJsonLine();
	if(empty($line)) {
		break;
	}

//	if($i <= 1226445) {
//		continue;
//	} else {
//		if($start_p === 0) {
//			$start_p = $dumpReader->getPosition() / $filesize * 100;
//		}
//	}

	$obj = json_decode($line, true);

	if(empty($obj) || !isset($obj['type']) || !isset($obj['id']) || $obj['type'] !== "item") {
		$ignore++;
	}
	$num++;
	$queue_obj = [
		$i,
		$obj['id'],
		$obj['type'],
		json_encode(isset($obj['labels']) ? $obj['labels'] : null),
		json_encode(isset($obj['descriptions']) ? $obj['descriptions'] : null),
		json_encode(isset($obj['aliases']) ? $obj['aliases'] : null),
		json_encode(isset($obj['claims']) ? $obj['claims'] : null),
		json_encode(isset($obj['sitelinks']) ? $obj['sitelinks'] : null),
	];

	$other = [];
	foreach($obj as $k => $v) {
		if(!in_array($k, ["id", "type", "labels", "descriptions", "aliases", "claims", "sitelinks"])) {
			$other[$k] = $v;
		}
	}
	$queue_obj[] = json_encode($other);
	$queue[] = $queue_obj;

	if($queue_num++ > 1000) {
		$insert += $insert_func();
	}
} while(true);

echo PHP_EOL;

$insert_func();