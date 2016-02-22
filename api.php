<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('STDENV_PATH', './stdenv/');
define('PRE_MODULE', 
	"/\s*(?:definition\s*|system\s*|implementation\s*)module\s+(\S+)\s*[\n;]/");
define('PRE_FUNC', 
	'/^\s*(\S+)\s*::.*$/mi');

function search_doc(&$r, $name){
	$files = glob(STDENV_PATH . "*.dcl", GLOB_NOSORT | GLOB_MARK);
	foreach($files as $filepath) {
		if(mb_substr($filepath, -1) !== DIRECTORY_SEPARATOR){
			$filename = explode(DIRECTORY_SEPARATOR, $filepath);
			$filename = end($filename);
			$contents = file_get_contents($filepath);
			$module = preg_match(PRE_MODULE, $contents, $modules) == 1 ?
				$modules[1] : NULL;
			$pattern = sprintf(PRE_FUNC);
			$namelen = strlen($name);
			if(preg_match_all($pattern, $contents, $funcs) !== false){
				for($i=0; $i<count($funcs[1]); $i++){
					$funcname = trim($funcs[1][$i]);
					$funcsig = trim($funcs[0][$i]);
					$score = levenshtein(strtolower($name), $funcname);
					if($score < 3){
						array_push($r, array(
							"filename" => $filename,
							"func" => $funcsig,
							"module" => $module,
							"distance" => $score));
					}
				}
			}
		}
	}
	return "Success";
}

function sort_results(&$r, $by='distance'){
	usort($r, function($a, $b) use ($by) { return $a[$by] > $b[$by]; });
}

if($_SERVER['REQUEST_METHOD'] !== 'GET'){
	echo json_encode(array(
		"return" => 1,
		"data" => array(),
		"msg" => "Can only be accessed by GET request"));
} else if(!isset($_GET['str'])){
	echo json_encode(array(
		"return" => 2,
		"data" => array(),
		"msg" => "GET variable 'str' should be set"));
} else {
	$res = array();
	$msg = search_doc($res, $_GET['str']);
	sort_results($res);
	if(!$res){
		echo json_encode(array(
			"return" => 127,
			"data" => array(),
			"msg" => "Nothing found..."));
	} else {
		echo json_encode(array(
			"return" => 0,
			"data" => $res,
			"msg" => $msg));
	}
}
?>
