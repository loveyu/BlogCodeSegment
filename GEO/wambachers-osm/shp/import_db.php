<?php
/**
 * User: loveyu
 * Date: 2018/2/18
 * Time: 23:13
 */
require_once __DIR__."/../../../init.php";
$shp = new \loveyu\Geo\Wbosm\ShpImport();
$shp->import();