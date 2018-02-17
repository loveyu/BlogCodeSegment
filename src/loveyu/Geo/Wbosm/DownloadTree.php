<?php
/**
 * User: loveyu
 * Date: 2018/2/15
 * Time: 19:40
 */

namespace loveyu\Geo\Wbosm;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use function GuzzleHttp\Promise\settle;
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
		$error_map = [];
		$empty_map = [];
		$not_num_id = [];
		$list = [0];
		$i = 0;
		while(!empty($list)) {
			echo sprintf("%d/%d:", $i, count($list));
			$ids = [];
			for($j = 0; $j < 5; $j++) {
				$id = array_shift($list);
				$i++;
				if(!is_null($id) && is_numeric($id)) {
					$ids[] = (int)$id;
				}
			}
			$start_time = microtime(true);
			$obj_list = $this->get_cache($ids);
			foreach($obj_list as $k => $obj) {
				if(empty($obj)) {
					$error_map[] = $k;
					echo "[error]";
				} else {
					$obj = \json_decode($obj, true);
					if(is_array($obj)) {
						if(empty($obj)) {
							$empty_map[] = $k;
							echo "[empty]";
						} else {
							foreach($obj as $item) {
								if(!$item['state']['loaded']) {
									if(!is_numeric($item['id'])) {
										$not_num_id[] = $item['id'];
									}
									$new_id = (int)$item['id'];
									if($new_id > 0) {
										array_push($list, $new_id);
									} else {
										$not_num_id[] = $new_id;
									}
								}
							}
						}
					} else {
						$error_map[] = $k;
						echo "[empty error]";
					}
				}
			}
			echo sprintf("%.6f\n", microtime(true) - $start_time);
		}
//		header("Content-Type: application/json; charset=utf-8");
//		print_r($obj);
		if(!empty($error_map)) {
			echo "Error Num:".count($error_map)."\n";
		}
		file_put_contents(BCS_DATA."wb_error_map.txt", implode(",", $error_map));

		if(!empty($empty_map)) {
			echo "Empty Num:".count($empty_map)."\n";
		}
		file_put_contents(BCS_DATA."wb_empty_map.txt", implode(",", $empty_map));
		if(!empty($not_num_id)) {
			echo "Not Num id:".count($not_num_id)."\n";
		}
		file_put_contents(BCS_DATA."wb_not_num_map.txt", implode(",", $not_num_id));


		$this->save_cookie();
	}

	private function save_cookie()
	{
		$this->cache->set($this->cookie_cache_key, $this->cookie);
	}

	public function get_cache(array $ids): array
	{
		if(empty($ids)) {
			return [];
		}
		$result_map = array_fill_keys($ids, null);
		$keys = array_fill_keys($ids, null);
		foreach($ids as $id) {
			$cache_key = Utils::mk_cache_key((string)$id);
//			die($cache_key);
			$cache = $this->cache->get($cache_key);
			if(is_string($cache) && $cache !== "") {
				$result_map[$id] = $cache;
				unset($keys[$id]);
			}
		}
		if(empty($keys)) {
			return $result_map;
		}
		$promises = [];
		foreach($keys as $id => $null) {
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
			$promises[$id] = $this->client->postAsync("/boundaries/getJsTree6", [
				'form_params' => $param
			]);
		}
		if(empty($promises)) {
			return $result_map;
		}

		$results = settle($promises)->wait();

		foreach($results as $K => $result) {
			if(empty($result['value'])) {
				echo "Error KEY:{$K}\n";
				continue;
			}
			$res = $result['value'];
			if(empty($res)) {
				echo "Error KEY RES:{$K}\n";
				continue;
			}
			/**
			 * @var \GuzzleHttp\Psr7\Response $res
			 */
			$data = $res->getBody();
			$data->seek(0);
			$size = $data->getSize();
			$content = $data->read($size);
			if(!empty($content)) {
				$cache_key = __METHOD__.":".$K;
				$this->cache->set($cache_key, $content);
				$result_map[$K] = $content;
			}
		}

		return $result_map;
	}
}