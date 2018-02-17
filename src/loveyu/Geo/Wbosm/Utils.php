<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 20:18
 */

namespace loveyu\Geo\Wbosm;


class Utils
{
	public static function mk_cache_key(string $id)
	{
		return "loveyu\Geo\Wbosm\DownloadTree::get_cache:{$id}";
	}
}