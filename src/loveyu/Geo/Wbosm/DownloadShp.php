<?php
/**
 * User: loveyu
 * Date: 2018/2/18
 * Time: 1:43
 */

namespace loveyu\Geo\Wbosm;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use loveyu\DB\PG\WbOSM;
use loveyu\FileCache\Dead;

class DownloadShp
{
	/**
	 * @var Client
	 */
	private $client;
	/**
	 * @var CookieJar
	 */
	private $cookie;
	/**
	 * @var string
	 */
	private $cookie_cache_key;
	/**
	 * @var Dead
	 */
	private $cache;

	private $cfg;

	/**
	 * DownloadShp constructor.
	 */
	public function __construct()
	{
		$this->cookie_cache_key = "loveyu\Geo\Wbosm\DownloadTree:CookieCacheKey";
		$this->cache = new Dead();
		$this->cookie = $this->get_cookie();
		$this->client = new Client([
			'base_uri' => 'https://wambachers-osm.website',
			'timeout'  => 15.0,
			'cookies'  => $this->cookie,
			'verify'   => false,
			//			'proxy'    => "http://127.0.0.1:8888",
			'headers'  => [
				'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
				'X-Requested-With' => 'XMLHttpRequest',
				'Referer'          => "https://wambachers-osm.website/boundaries/"
			]
		]);
		$this->cfg = include Config_DIR."wbosm_cfg.php";
	}

	public function download()
	{
		$db = WbOSM::getInstance()->getDbAct();
		$stmt = $db->query("select id from tree_index_tbl order by admin_level asc,next_num ASC");
		$list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		$count = count($list);
		$i = 0;
		foreach($list as $item) {
			$id = (int)$item['id'];
			echo sprintf("%d/%d:[%d]:", ++$i, $count, $id);
			$start_time = microtime(true);
			$file_name = $this->get_file_name($id);
			if(file_exists($file_name)) {
				echo "Exists\n";
				continue;
			}
			try {
				$size = $this->download_file($id, $file_name);
			} catch(\Exception $ex) {
				echo "Error.\n";
				continue;
			}
			echo sprintf("%d,T:%0.3f\n", $size, microtime(true) - $start_time);
		}
	}

	/**
	 * @return CookieJar
	 */
	private function get_cookie(): CookieJar
	{
		$obj = $this->cache->get($this->cookie_cache_key);
		if(is_object($obj) && $obj instanceof CookieJar) {
			return $obj;
		} else {
			return CookieJar::fromArray([
				"JSESSIONID"          => "	node016s3mcf1uzfi31eki63ojjoagr182.node0",
				"osm_boundaries_base" => "4|true|shp|zip|10.0|false|levels|water|4.3|true|3",
				"osm_boundaries_map"  => "1|69.60051499999925|34.910011932933514|10|B0T|open",
			], "wambachers-osm.website");
		}
	}

	public function get_file_name($id)
	{
		$path = GeoShp_DataDir."/".($id % 1000);
		static $map = [];
		if(!isset($map[$path])) {
			if(!is_dir($path)) {
				mkdir($path, 0777, true);
			}
			$map[$path] = 1;
		}
		return $path."/{$id}.zip";
	}

	private function download_file($id, $file_name): int
	{
//		print_r($this->cfg);
//		exit;
		$this->client->get("boundaries/exportBoundaries?".http_build_query([
				"cliVersion"   => "1.0",
				"cliKey"       => $this->cfg["apikey"],
				"exportFormat" => "shp",
				"exportLayout" => "levels",
				"exportAreas"  => "water",
				"union"        => "false",
				"selected"     => $id,
			]), [
			'sink' => $file_name
		]);
		return filesize($file_name);
	}
}