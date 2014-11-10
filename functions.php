<?php

/**
 * @package		XG Project
 * @copyright	Copyright (c) 2008 - 2014
 * @license		http://opensource.org/licenses/gpl-3.0.html	GPL-3.0
 * @since		Version 1.0.0
 */

// $response = json_decode('[
// 	{
// 		"url": "https://api.github.com/repos/XG-Project/XG-Project/releases/672050",
// 		"assets_url": "https://api.github.com/repos/XG-Project/XG-Project/releases/672050/assets",
// 		"upload_url": "https://uploads.github.com/repos/XG-Project/XG-Project/releases/672050/assets{?name}",
// 		"html_url": "https://github.com/XG-Project/XG-Project/releases/tag/v3.0.0-beta5",
// 		"id": 672050,
// 		"tag_name": "v3.0.0-beta5",
// 		"target_commitish": "33dac579e22fc322c41ece8c6db22910b93c0751",
// 		"name": "v3.0 Beta 5",
// 		"draft": false,
// 		"author": {
// 			"login": "Razican",
// 			"id": 597469,
// 			"avatar_url": "https://avatars.githubusercontent.com/u/597469?v=3",
// 			"gravatar_id": "",
// 			"url": "https://api.github.com/users/Razican",
// 			"html_url": "https://github.com/Razican",
// 			"followers_url": "https://api.github.com/users/Razican/followers",
// 			"following_url": "https://api.github.com/users/Razican/following{/other_user}",
// 			"gists_url": "https://api.github.com/users/Razican/gists{/gist_id}",
// 			"starred_url": "https://api.github.com/users/Razican/starred{/owner}{/repo}",
// 			"subscriptions_url": "https://api.github.com/users/Razican/subscriptions",
// 			"organizations_url": "https://api.github.com/users/Razican/orgs",
// 			"repos_url": "https://api.github.com/users/Razican/repos",
// 			"events_url": "https://api.github.com/users/Razican/events{/privacy}",
// 			"received_events_url": "https://api.github.com/users/Razican/received_events",
// 			"type": "User",
// 			"site_admin": false
// 		},
// 		"prerelease": true,
// 		"created_at": "2014-03-17T22:55:44Z",
// 		"published_at": "2014-11-02T16:56:04Z",
// 		"assets": [

// 		],
// 		"tarball_url": "https://api.github.com/repos/XG-Project/XG-Project/tarball/v3.0.0-beta5",
// 		"zipball_url": "https://api.github.com/repos/XG-Project/XG-Project/zipball/v3.0.0-beta5",
// 		"body": "XG Project v3.0 beta 5 version."
// 	}
// ]');

// echo nl2br(print_r($response[0]->url, TRUE));


update_cache();

function get_cache($force_reload = FALSE)
{
	static $cache;

	if (empty($cache))
	{
		$cache = json_decode(file_get_contents('cache.cfg'));
	}

	return $cache;
}

function update_cache()
{
	$request = get_release_info();

	$xgp2 = check_release($request['xgp2_response']);
	$xgp2_dev = check_release($request['xgp2_response'], TRUE);

	$xgp3 = check_release($request['xgp3_response']);
	$xgp3_dev = check_release($request['xgp3_response'], TRUE);

	for ($page = 2; (is_null($xgp2) OR is_null($xgp2_dev)) && has_more_pages(parse_headers($request['xgp2_header'])); $page++)
	{
		$request = get_release_info(TRUE, FALSE, $page);
		$xgp2 = is_null($xgp2) ? check_release($request['xgp2_response']) : $xgp2;
		$xgp2_dev = is_null($xgp2_dev) ? check_release($request['xgp2_response'], TRUE) : $xgp2_dev;
	}

	for ($page = 2; (is_null($xgp3) OR is_null($xgp3_dev)) && has_more_pages(parse_headers($request['xgp3_header'])); $page++)
	{
		$request = get_release_info(FALSE, TRUE, NULL, $page)
		$xgp3 = is_null($xgp3) ? check_release($request['xgp3_response']) : $xgp3;
		$xgp3_dev = is_null($xgp3_dev) ? check_release($request['xgp3_response'], TRUE) : $xgp3_dev;
	}

	$new_data = array(
		'last_update' => time(),
		'xgp2'		=> $xgp2,
		'xgp2-dev'	=> $xgp2_dev,
		'xgp3'		=> $xgp3,
		'xgp3-dev'	=> $xgp3_dev);

	$dh = fopen('version_cache.cfg', 'w');
	fwrite($dh, json_encode($new_data));
	fclose($dh);
}

function has_more_pages($headers)
{
	if (isset($headers['Link']))
	{
		// TODO check
		return TRUE;
	}
	return FALSE;
}

function parse_headers($headers)
{
	$headers = explode("\n", $headers);

	$new_headers = array();
	foreach ($headers as $header)
	{
		$header = explode(":", $header);
		$header[0] = trim($header[0]);
		$header[1] = trim($header[1]);

		$new_headers[$header[0]] = $header[1];
	}

	return $new_headers;
}

function get_release_info($get_xgp2 = TRUE, $get_xgp3 = TRUE, $xgp2_page = 1, $xgp3_page = 1)
{
	$mh = curl_multi_init();

	if ($get_xgp2)
	{
		// XG Project v2
		$xgp2 = curl_init();
		curl_setopt($xgp2, CURLOPT_URL, 'https://api.github.com/repos/XG-Project/XG-Project-v2/releases?page='.$xgp2_page);
		curl_setopt($xgp2, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($xgp2, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($xgp2, CURLOPT_HEADER, 1);
		curl_setopt($xgp2, CURLOPT_HTTPHEADER, array(
			'Accept: application/vnd.github.v3+json',
			'User-Agent: XG-Project'));

		curl_multi_add_handle($mh, $xgp2);
	}

	if ($get_xgp3)
	{
		// XG Project v3
		$xgp3 = curl_init();
		curl_setopt($xgp3, CURLOPT_URL, 'https://api.github.com/repos/XG-Project/XG-Project/releases');
		curl_setopt($xgp3, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($xgp3, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($xgp3, CURLOPT_HEADER, 1);
		curl_setopt($xgp3, CURLOPT_HTTPHEADER, array(
			'Accept: application/vnd.github.v3+json',
			'User-Agent: XG-Project'));

		curl_multi_add_handle($mh, $xgp3);
	}

	$active = 2;
	do
	{
		curl_multi_exec($mh, $active);

		if (curl_multi_select($mh))
		{
			usleep(100);
		}
	}
	while ($active > 0);

	if ($get_xgp2)
	{
		$xgp2_response = curl_multi_getcontent($xgp2);
		$xgp2_head_size = curl_getinfo($xgp2, CURLINFO_HEADER_SIZE);
		$xgp2_header = substr($xgp2_response, 0, $xgp2_head_size);
		$xgp2_response = substr($xgp2_response, $xgp2_head_size);

		curl_multi_remove_handle($mh, $xgp2);
	}

	if ($get_xgp3)
	{
		$xgp3_response = curl_multi_getcontent($xgp3);
		$xgp3_head_size = curl_getinfo($xgp3, CURLINFO_HEADER_SIZE);
		$xgp3_header = substr($xgp3_response, 0, $xgp3_head_size);
		$xgp3_response = substr($xgp3_response, $xgp3_head_size);

		curl_multi_remove_handle($mh, $xgp3);
	}

	curl_multi_close($mh);

	$return_array = array();
	if ($get_xgp2)
	{
		$return_array['xgp2_response']	= $xgp2_response;
		$return_array['xgp2_header']	= $xgp2_header;
	}

	if ($get_xgp3)
	{
		$return_array['xgp3_response']	= $xgp3_response;
		$return_array['xgp3_header']	= $xgp3_header;
	}

	return $return_array;
}

function check_release($response, $check_dev = FALSE)
{
	$decoded = json_decode($response);

	foreach ($decoded)
}

/* End of file functions.php */
/* Location: ./functions.php */