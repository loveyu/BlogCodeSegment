<?php
/**
 * User: loveyu
 * Date: 2018/2/15
 * Time: 19:40
 */

namespace loveyu\Geo\Wbosm;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use loveyu\FileCache\Dead;

class DownloadTree
{
	private $client;
	private $cookie;
	private $cookie_cache_key;
	private $cache;

	/**
	 * DownloadTree constructor.
	 */
	public function __construct()
	{
		$this->cookie_cache_key = __CLASS__.":CookieCacheKey";
		$this->cache = new Dead();
		$this->cookie = $this->get_cookie();
		$this->client = new Client([
			'base_uri' => 'https://wambachers-osm.website',
			'timeout'  => 60.0,
			'cookies'  => $this->cookie,
			'verify'   => false,
			'headers'  => [
				'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
				'X-Requested-With' => 'XMLHttpRequest',
				'Referer'          => "https://wambachers-osm.website/boundaries/"
			]
		]);
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

	public function download()
	{
		$list = [0];
		$i = 0;
		while(!empty($list)) {
			echo sprintf("%d/%d:", $i++, count($list));
			$id = (int)array_shift($list);
			$start_time = microtime(true);
			$obj = $this->get_cache($id);
			if(empty($obj)) {
				echo "[error]";
			} else {
				$obj = \json_decode($obj, true);
				if(is_array($obj) && !empty($obj)) {
					foreach($obj as $item) {
						if(!$item['state']['loaded']) {
							array_push($list, (int)$item['id']);
						}
					}
				} else {
					echo "[empty]";
				}
			}
			echo sprintf("%.6f\n", microtime(true) - $start_time);
		}
//		header("Content-Type: application/json; charset=utf-8");
//		print_r($obj);
		$this->save_cookie();
	}

	private function save_cookie()
	{
		$this->cache->set($this->cookie_cache_key, $this->cookie);
	}

	public function get_cache(int $id): ?string
	{
		$cache_key = __METHOD__.":".$id;
		$cache = $this->cache->get($cache_key);
		if(is_string($cache)) {
			return $cache;
		}
		if($id < 1) {
			$param = [
				"caller"      => "boundaries-4.3.6",
				"database"    => "planet3",
				"parent"      => "0",
				"path"        => "",
				"admin_level" => "1",
			];
		} else {
			$param = [
				"caller"   => "boundaries-4.3.6",
				"database" => "planet3",
				"parent"   => $id,
			];
		}
		try {
			$res = $this->client->post("/boundaries/getJsTree6", [
				"form_params" => $param
			]);
			$data = $res->getBody();
			$data->seek(0);
			$size = $data->getSize();
			$content = $data->read($size);
			$this->cache->set($cache_key, $content);
			return $content;
		} catch(\Exception $exception) {
			echo $exception->getMessage();
			return null;
		}
	}
}