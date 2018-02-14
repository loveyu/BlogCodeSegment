<?php
/**
 * User: loveyu
 * Date: 2018/2/14
 * Time: 23:03
 */

use GuzzleHttp\Cookie\CookieJar;

require_once __DIR__."/../../../init.php";
$jar = CookieJar::fromArray([
	"JSESSIONID"          => "	node016s3mcf1uzfi31eki63ojjoagr182.node0",
	"osm_boundaries_base" => "4|true|shp|zip|10.0|false|levels|water|4.3|true|3",
	"osm_boundaries_map"  => "1|69.60051499999925|34.910011932933514|10|B0T|open",
], 'wambachers-osm.website');
$client = new GuzzleHttp\Client([
	'base_uri' => 'https://wambachers-osm.website',
	'timeout'  => 15.0,
	'cookies'  => $jar,
	//	'proxy'    => "http://127.0.0.1:8888",
	'verify'   => false,
	'headers'  => [
		'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36',
		'X-Requested-With' => 'XMLHttpRequest',
		'Referer'          => "https://wambachers-osm.website/boundaries/"
	]
]);
$response = $client->get("/boundaries/");
try {
	$post = $client->post("/boundaries/getJsTree6", [
		"form_params" => [
			"caller"      => "boundaries-4.3.6",
			"database"    => "planet3",
			"parent"      => "0",
			"path"        => "",
			"admin_level" => "1",
		],
		'cookies'     => $jar
	]);
} catch(Exception $exception) {
	print_r($exception->getMessage());
}
echo $post->getBody()->getContents();

//print_r($jar->toArray());