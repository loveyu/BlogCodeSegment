<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 22:19
 */

namespace loveyu\DB\PG;


use Medoo\Medoo;

trait BaseTrait
{
	/**
	 * @var self
	 */
	private static $instance =null;

	/**
	 * @var null|Medoo
	 */
	private $db_act=null;

	public static function getInstance():self
	{
		if(self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param array $config
	 * @return Medoo
	 */
	private function get_medoo(array $config):Medoo
	{
		$database = new Medoo($config);
		return $database;
	}

	private function get_config(string $name)
	{
		$cfg = Config_DIR.$name.".php";
		if(is_file($cfg)) {
			return include $cfg;
		} else {
			return null;
		}
	}

	/**
	 * @return Medoo
	 */
	public function getDbAct(): ?Medoo
	{
		return $this->db_act;
	}
}