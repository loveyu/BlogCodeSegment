<?php
/**
 * User: loveyu
 * Date: 2018/2/13
 * Time: 18:55
 */
require_once __DIR__."/../../../init.php";
if(!isset($argv[1]) || !is_dir($argv[1])) {
	die("Error or empty dir.");
}

$list = glob("{$argv[1]}/*");
$shp2pgsql_exe_file = EXE_shp2pgsql;
$psql_exe = EXE_psql;

foreach($list as $item) {
	echo $item, "\n";
	$shp_list = glob("{$item}/*.shp");
	foreach($shp_list as $value) {
		$filename = pathinfo($value, PATHINFO_FILENAME);
		$dir_name = pathinfo($value, PATHINFO_DIRNAME);
		$output = $dir_name.DIRECTORY_SEPARATOR.$filename.".sql";
		//4326 is WGS84
		echo $cmd = "\"{$shp2pgsql_exe_file}\" -I -s 4326 \"{$value}\" > \"{$output}\" ";
		system($cmd);
		echo $import_cmd = "\"{$psql_exe}\" -U postgres -d gamd_db -f \"{$output}\"";
		system($import_cmd);
	}
}