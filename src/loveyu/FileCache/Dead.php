<?php
/**
 * User: loveyu
 * Date: 2018/2/15
 * Time: 14:58
 */

namespace loveyu\FileCache;


class Dead
{
	private $path_dir = "";

	public function __construct()
	{
		if(!defined('FileCache_DIR') || !is_dir(FileCache_DIR)) {
			trigger_error("Error file cache directory.", E_USER_ERROR);
			return;
		}
		$this->path_dir = FileCache_DIR;
	}

	public function get($key)
	{
		$file = $this->file($key);
		if(file_exists($file) && is_readable($file)) {
			return @unserialize(file_get_contents($file));
		}
		return null;
	}

	public function set($key, $cache)
	{
		file_put_contents($this->file($key, true), serialize($cache));
	}

	private function file(string $key, bool $make_dir = false)
	{
		$hash = hash("sha256", $key);
		$path = $this->path_dir.substr($hash, 0, 2);
		if($make_dir && !is_dir($path)) {
			mkdir($path, 0777, true);
		}
		return $path.DIRECTORY_SEPARATOR.$hash.".cache";
	}
}