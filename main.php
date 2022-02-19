<?php
// SPDX-License-Identifier: Public domain

declare(strict_types=1);

const TARGET_WEB = "https://www.worldometers.info/coronavirus/";
const USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36";

function get_html_data(): ?string
{
	$ch = curl_init(TARGET_WEB);
	curl_setopt_array($ch,
		[
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => USER_AGENT
		]
	);

	$out = curl_exec($ch);
	$err = curl_error($ch);
	$ern = curl_errno($ch);

	if ($err) {
		printf("Curl error: (%d): %s", $ern, $err);
		return NULL;
	}

	curl_close($ch);
	return $out;
}

function parse_html_data(string $html): ?array
{
	$ex = explode("<td style=\"font-size:12px;color: grey;text-align:center;vertical-align:middle;\">", $html, 2);
	if (!isset($ex[1])) {
		printf("explode 1 fails");
		return NULL;
	}

	$ex = explode("</table>", $ex[1]);
	$ex = $ex[0];

	$pat1 = "/<td style=\"font-weight: bold; font-size:15px; text-align:left;\"><a class=\"mt_a\" href=\"country\/.+?>(.+?)<\/a><\/td>.+?<td style=\"font-weight: bold; text-align:right\">(.+?)<\/td>.*?<td style=\"font-weight: bold; text-align:right;.*?\">.*?<\/td>.*?<td style=\"font-weight: bold; text-align:right;\">(.+?)<\/td>.*?<td style=\"font-weight: bold;.*?><\/td>.*?<td style=\"font-weight: bold; text-align:right\">(.*?)</si";
	if (!preg_match_all($pat1, $html, $r)) {
		printf("Pattern 1 fails");
		return NULL;
	}
	unset($r[0]);

	$ret = [];
	foreach ($r[2] as $k => &$i) {

		$r[1][$k] = strtolower($r[1][$k]);
		$ref = &$ret[$r[1][$k]];
		if (isset($ref))
			continue;

		$ref = [
			"cmt" => (int) str_replace(",", "", trim($i)),
			"fst" => (int) str_replace(",", "", trim($r[3][$k])),
			"sdt" => (int) str_replace(",", "", trim($r[4][$k])),
		];
	}

	return $ret;
}

function main(int $argc, array $argv): int
{
	$html = get_html_data();
	if (!$html)
		return 1;

	$data = parse_html_data($html);
	if (!$data)
		return 1;

	file_put_contents(__DIR__."/covid19.json", json_encode($data, JSON_PRETTY_PRINT));
	unset($html);
	return 0;
}

exit(main($_SERVER["argc"], $_SERVER["argv"]));
