<?php
/**
 * Get all js tree
 * User: loveyu
 * Date: 2018/2/15
 * Time: 15:12
 */
require_once __DIR__."/../../../init.php";
$dt = new \loveyu\Geo\Wbosm\DownloadTree();
$dt->download();