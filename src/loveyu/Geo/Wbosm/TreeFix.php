<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 23:58
 */

namespace loveyu\Geo\Wbosm;


use loveyu\DB\PG\WbOSM;

class TreeFix
{
	public function __construct()
	{

	}

	public function start()
	{
		$this->generate_next_num();
	}

	private function generate_next_num()
	{
		echo __METHOD__, "\n";
		$db = WbOSM::getInstance()->getDbAct();
		$stmt = $db->query("SELECT parent_id,count(*) as total FROM tree_index_tbl GROUP BY parent_id;");
		$list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		$map2 = $db->query("select id,next_num from tree_index_tbl where next_num>0");
		$map_list = $map2->fetchAll(\PDO::FETCH_ASSOC);
		$map2->closeCursor();
		$map_list = array_column($map_list, "next_num", "id");

		$n = count($list);
		$i = 0;
		foreach($list as $item) {
			if($item['parent_id'] <= 0) {
				continue;
			}
			if(isset($map_list[$item['parent_id']]) && $map_list[$item['parent_id']] == $item['total']) {
				continue;
			}
			echo sprintf("%d/%d\n", ++$i, $n);
			if($item['parent_id'] < 1) {
				continue;
			}
			$db->update("tree_index_tbl", [
				'next_num' => $item['total']
			], ['id' => (int)$item['parent_id']]);
		}
	}
}