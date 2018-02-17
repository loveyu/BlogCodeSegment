<?php
/**
 * User: loveyu
 * Date: 2018/2/12
 * Time: 19:40
 */
require_once __DIR__."/vendor/autoload.php";
define('BCS_DATA', __DIR__.DIRECTORY_SEPARATOR."Data".DIRECTORY_SEPARATOR);
define('HTTP_PROXY_URL', "http://127.0.0.1:2081");
define('EXE_7zip', "C:/Program Files/7-Zip/7z.exe");
define('EXE_shp2pgsql', "E:/BigDataDB/bigsql/pg10/bin/shp2pgsql.exe");
define('EXE_psql', "E:/BigDataDB/bigsql/pg10/bin/psql.exe");
define('GeoShp_DataDir', "E:/Data/Geo-Shp/");
define('Config_DIR', __DIR__.DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR);
define('FileCache_DIR', __DIR__.DIRECTORY_SEPARATOR."Cache".DIRECTORY_SEPARATOR."Dead".DIRECTORY_SEPARATOR);