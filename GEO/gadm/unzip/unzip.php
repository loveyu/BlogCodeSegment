<?php
/**
 * User: loveyu
 * Date: 2018/2/13
 * Time: 11:55
 */
require_once __DIR__."/../../../init.php";
if(!isset($argv[2]) || empty($argv[2])) {
	die("Error argv.");
}
$data_dir = $argv[1];
$output_dir = $argv[2];

if(!is_dir($data_dir) || empty($output_dir) || !is_dir($output_dir)) {
	die("Error dir.");
}

$list = glob("{$data_dir}/*.zip");
foreach($list as $file) {
	$out = $output_dir.DIRECTORY_SEPARATOR.pathinfo(basename($file), PATHINFO_FILENAME);
	$exe_file = EXE_7zip;
	$unzip_cmd = "\"{$exe_file}\" x \"{$file}\" -y -o\"{$out}\"";
	system($unzip_cmd);
}