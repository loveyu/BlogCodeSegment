<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 22:20
 */

namespace loveyu\DB\PG;


class WbOSM
{
	use BaseTrait;

	/**
	 * WbOSM constructor.
	 */
	private function __construct()
	{
		$this->db_act = $this->get_medoo($this->get_config("db_pg_loc"));
	}

	/**
	 * @param $db_tbl
	 * @param $list
	 * @return int
	 * @throws \Exception
	 */
	public function insert_batch_list($db_tbl, $list)
	{
		$pst = $this->db_act->insert($db_tbl, $list);
		$info = $pst->errorInfo();
		if($info[0] !== "00000") {
			$pst->closeCursor();
			throw new \Exception("Insert Error.".print_r($info, true));
		}
		$res = $pst->rowCount();
		$pst->closeCursor();
		return $res;
	}


	public function get_exists_ids($tbl, $field, $where)
	{
		$list = $this->db_act->select($tbl, [$field], $where);
		return array_column($list, $field);
	}
}