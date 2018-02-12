<?php
/**
 * Generate Country shape file download url list
 * User: loveyu
 * Date: 2018/2/12
 * Time: 19:45
 */

use function GuzzleHttp\Promise\settle;

require_once __DIR__."/../../../init.php";
$raw_list = json_decode(file_get_contents(BCS_DATA."geo_gadm_country_list.json"), true);

$list = $raw_list;
$map_url = [];
while(!empty($list)) {
	$i = 0;
	$gc_list = array_chunk($list, 20);
	$c = count($gc_list);
	$error_list = [];
	foreach($gc_list as $items) {
		echo sprintf("%d/%d\n", ++$i, $c);
		$client = new \GuzzleHttp\Client();
		$promises = [];
		foreach($items as $item) {
			$promises[] = $client->postAsync("http://gadm.org/download", [
				'form_params' => [
					'cnt'           => $item['key'],
					'thm'           => 'shp#shapefile',
					'OK'            => 'OK',
					'_submit_check' => '1'
				],
				"proxy"       => HTTP_PROXY_URL
			]);
		}
		$results = settle($promises)->wait();

		foreach($results as $K => $result) {
			if(empty($result['value'])) {
				echo "Error KEY:{$K}\n";
				$error_list[] = $items[$K];
				continue;
			}
			$res = $result['value'];
			if(empty($res)) {
				$error_list[] = $items[$K];
				continue;
			}
			/**
			 * @var \GuzzleHttp\Psr7\Response $res
			 */
			$body = $res->getBody()->getContents();
			preg_match("/href=(http:\\/\\/.*?\\.zip)><h3><b>download/", $body, $matches);

			if(!empty($matches) && isset($matches[1])) {
				$map_url[$items[$K]['key']] = $matches[1];
			} else {
				echo "{$K} => None\n";
			}
		}
		//	print_r($results);
		//	break;
	}
	$list = $error_list;
}

//print_r($map_url);
file_put_contents(BCS_DATA."geo_gadm_country_download_url_list.txt", implode("\n", $map_url));

foreach($raw_list as &$item) {
	$item['url'] = isset($map_url[$item['key']]) ? $map_url[$item['key']] : "None";
}
unset($item);

file_put_contents(BCS_DATA."geo_gadm_country_download_url_map.json", json_encode($raw_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));