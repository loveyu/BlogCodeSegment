<?php
/**
 * User: loveyu
 * Date: 2018/2/18
 * Time: 1:42
 */
ini_set('memory_limit', '1024M');
require_once __DIR__."/../../../init.php";
$db = new \loveyu\Geo\Wbosm\DownloadShp();
$db->download();