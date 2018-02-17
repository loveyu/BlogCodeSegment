<?php
/**
 * User: loveyu
 * Date: 2018/2/17
 * Time: 20:14
 */
require_once __DIR__."/../../../init.php";
$import = new \loveyu\Geo\Wbosm\ImportDB();
$import->import();