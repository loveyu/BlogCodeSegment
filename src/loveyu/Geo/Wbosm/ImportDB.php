<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 20:15
 */

namespace loveyu\Geo\Wbosm;


use loveyu\DB\PG\WbOSM;
use loveyu\FileCache\Dead;

class ImportDB
{
	private $cache;

	private $insert_list = [];
	private $insert_num = 0;

	/**
	 * DownloadTree constructor.
	 */
	public function __construct()
	{
		$this->cache = new Dead();
	}

	public function import()
	{
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
								$this->insert_to_db($item, $k);
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
		$this->update_to_db();
	}

	private function insert_to_db($item, $parent_id)
	{
//		print_r($item);
//		exit;
		$id = (int)$item['id'];
		$this->insert_list[$id] = [
			'id'          => $id,
			'title'       => (string)$item['a_attr']['title'],
			'admin_level' => (int)$item['data']['admin_level'],
			'bbox'        => json_encode($item['data']['bbox']),
			'disable'     => $item['state']['disabled'] ? 1 : 0,
			'loaded'      => $item['state']['loaded'] ? 1 : 0,
			'text'        => (string)$item['text'],
			'parent_id'   => (int)$parent_id,
			'top_id'      => 0,
			'top2_id'     => 0,
		];
		$this->insert_num++;
		if($this->insert_num % 200 == 0) {
			$this->update_to_db();
		}
	}

	private function update_to_db()
	{
		if(empty($this->insert_list)) {
			return;
		}
		$tbl = "tree_index_tbl";
		$db = WbOSM::getInstance();
		$ids = $db->get_exists_ids($tbl, "id", ["id" => array_keys($this->insert_list)]);

		foreach($ids as $id) {
			unset($this->insert_list[(int)$id]);
		}

		if(empty($this->insert_list)) {
			echo "[ignore]";
			return;
		}
		try {
			$res = $db->insert_batch_list($tbl, array_values($this->insert_list));
			echo "[IS:{$res}]";
		} catch(\Exception $ex) {
			echo $ex->getMessage();
			exit;
		}
//		print_r($res);
//
//		exit;
		$this->insert_list = [];
	}

	private function get_cache(array $ids): array
	{
		if(empty($ids)) {
			return [];
		}
		$result_map = array_fill_keys($ids, null);
		$keys = array_fill_keys($ids, null);
		foreach($ids as $id) {
			$cache_key = Utils::mk_cache_key((string)$id);
			$cache = $this->cache->get($cache_key);
			if(is_string($cache) && $cache !== "") {
				$result_map[$id] = $cache;
				unset($keys[$id]);
			}
		}
		if(empty($keys)) {
			return $result_map;
		}

		return $result_map;
	}

}