<?php
require_once __DIR__."/../../../init.php";
$client = new GuzzleHttp\Client();

$res = $client->get("http://gadm.org/country", [
	'proxy' => HTTP_PROXY_URL
]);
$body = $res->getBody();

if(empty($body)) {
	echo "Empty Response.";
	return;
}

preg_match("/<select name=\"cnt\">([\s\S]+?)<\\/select>/", $body, $match);
if(empty($match) || empty($match[1])) {
	echo "No Country Match.";
	return;
}

preg_match_all("/<option value=\"(.*?)\">(.*?)<\\/option>/", $match[1], $matches, PREG_SET_ORDER);

if(empty($matches)) {
	echo "Empty list.";
	return;
}
$county_list = [];
foreach($matches as $match) {
	$county_list[] = [
		'key'  => $match[1],
		'name' => $match[2],
	];
}

file_put_contents(BCS_DATA."geo_gadm_country_list.json", json_encode($county_list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Output:".count($county_list);