#!/usr/bin/env php
<?php

if(count($_SERVER['argv']) < 3) {
	die("Usage: " . basename($_SERVER['argv'][0]) . " globpath1 globpath2\n");
}

$usecolors = true;
if (function_exists('posix_isatty')
    && !posix_isatty(STDOUT)
) {
    $usecolors = false;
}


echo "Reading the configuration from glob path \"" . $_SERVER['argv'][1] . "\" into memory\n";
$cfg1 = getRecursiveCfgArray($_SERVER['argv'][1]);
echo "Reading the configuration from glob path \"" . $_SERVER['argv'][2] . "\" into memory\n";
$cfg2 = getRecursiveCfgArray($_SERVER['argv'][2]);

echo "Calculating the diff\n";
echo "\n\n";
displayCfgDiff($cfg1, $cfg2);


function getCfgKey($definedObject, array $definedVals) {
	switch($definedObject) {
		case 'service':
			return  $definedVals['v']['host_name']['v'] . '||' . $definedVals['v']['service_description']['v'];
		case 'host':
			return $definedVals['v']['host_name']['v'];
		case 'servicedependency':
			return  $definedVals['v']['host_name']['v'] . '||' . $definedVals['v']['service_description']['v'] . '||' . $definedVals['v']['dependent_host_name']['v'] . '||' . $definedVals['v']['dependent_service_description']['v'];
		case 'timeperiod':
			return $definedVals['v']['timeperiod_name']['v'];
		case 'command':
			return $definedVals['v']['command_name']['v'];
		case 'contact':
			return $definedVals['v']['contact_name']['v'];
		case 'hostgroup':
			return $definedVals['v']['hostgroup_name']['v'];
		case 'servicegroup':
			return $definedVals['v']['servicegroup_name']['v'];
		default:
			echo "\t\tERROR: Unknown Object $definedObject - using '' as key (defined in " . $definedVals['p'] . ":" . $definedVals['l'] . ")\n";
			return '';
	}
}

function getRecursiveCfgArray($cfgpath) {
	$cfg = array();
	foreach(glob($cfgpath) as $p) {
		if(is_dir($p)) {
			echo "\tProcessing path: $p\n";
			foreach(glob("$p/*.cfg") as $p) {
				$subcfg = getRecursiveCfgArray($p);
				foreach($subcfg as $definedObject => $a) {
					if(!isset($cfg[$definedObject])) {
						$cfg[$definedObject] = $a;
						continue;
					}
					foreach($a as $k => $definedVals) {
						if(!isset($cfg[$definedObject][$k])) {
							$cfg[$definedObject][$k] = $definedVals;
							continue;
						}
						echo "\t\tERROR: $definedObject \"$k\" has been defined twice (defined in " . $a[$k]['p'] . ":" . $a[$k]['l'] . " and in " . $cfg[$definedObject][$k]['p'] . ":" . $cfg[$definedObject][$k]['l'] . ")\n";
						// $cfg[$definedObject][$k] = $definedVals; // comment the foreach loop, when using this line
						foreach($definedVals['v'] as $key => $val) { // comment the above line, when using this loop
							if(isset($cfg[$definedObject][$k]['v'][$key])) {
								echo "\t\tERROR: Overwriting existing value from " . $cfg[$definedObject][$k]['v'][$key]['p'] . ":" . $cfg[$definedObject][$k]['v'][$key]['l'] . "  with the new value from " . $val['p'] . ":" . $val['l']."\n";
							}
							else {
								echo "\t\tERROR: Adding new value from " . $val['p'] . ":" . $val['l']." to an already defined object\n";
							}

							$cfg[$definedObject][$k]['v'][$key] = $val;
						}
					}
				}
			}
		}
		else {
			echo "\tProcessing file: $p\n";
			if($fp = fopen($p, 'r')) {
				$isInDefine = false;
				$definedObject = '';
				$definedVals = array('p' => '', 'l' => 0, 'v' => array());
				$l = 0;
				while(!feof($fp)) {
					$l++;
					$line = trim(fgets($fp, 512000));
					if($line == '' || $line[0] == '#') continue;
					//echo "\t\t$line\n";
					if($isInDefine) {
						if($line == '}') {
							//print_r($definedVals);
							//echo "End of Define: $definedObject\n";
							$k = strtolower(getCfgKey($definedObject, $definedVals));
							if(isset($cfg[$definedObject][$k])) {
								echo "\t\tERROR: $definedObject \"$k\" has been defined twice (defined in " . $cfg[$definedObject][$k]['p'] . ":" . $cfg[$definedObject][$k]['l'] . " and in $p:$l)\n";
								// $cfg[$definedObject][$k] = $definedVals; // comment the foreach loop, when using this line
								foreach($definedVals['v'] as $key => $val) { // comment the above line, when using this loop
									if(isset($cfg[$definedObject][$k]['v'][$key])) {
										echo "\t\tERROR: Overwriting existing value from " . $cfg[$definedObject][$k]['v'][$key]['p'] . ":" . $cfg[$definedObject][$k]['v'][$key]['l'] . "  with the new value from " . $val['p'] . ":" . $val['l']."\n";
									}
									else {
										echo "\t\tERROR: Adding new value from " . $val['p'] . ":" . $val['l']." to an already defined object\n";
									}
									$cfg[$definedObject][$k]['v'][$key] = $val;
								}
							}
							else {
								$cfg[$definedObject][$k] = $definedVals;
							}


							$isInDefine = false;
							$definedObject = '';
							$definedVals = array('p' => '', 'l' => 0, 'v' => array());
						}
						else {
							if(preg_match('/^(\S+)\s+(.*)$/', $line, $matches)) {
								if(isset($definedVals['v'][$matches[1]])) {
									echo "\t\tERROR: Value \"" . $matches[1] . "\" has been defined twice (defined in " . $definedVals['v'][$matches[1]]['p'] . ":"  . $definedVals['v'][$matches[1]]['l'] .  " and in $p:$l)\n";
								}
								$definedVals['v'][$matches[1]] = array('p' => $p, 'l' => $l, 'v' => trim($matches[2]));
							}
						}
					}
					else {
						if(preg_match('/^define\s+(\S+)\s*\{$/i', $line, $matches)) {
							$isInDefine = true;
							$definedObject = strtolower($matches[1]);
							$definedVals = array('p' => $p, 'l' => $l, 'v' => array());
							//echo "Define:        $definedObject\n";
						}
					}
				}
				if($isInDefine) {
					echo "\t\tERROR: a define has not been closed\n";
				}
				fclose($fp);
			}
			else {
				echo "\t\tERROR: cannot open file: " . $p . "\n";
			}
		}
	}
	return $cfg;
}

function coloredEcho($str, $color) {
	global $usecolors;
	if($usecolors) {
		switch(strtolower(trim($color))) {
			case 'black': $c = '0;30'; break;
			case 'dark_gray': $c = '1;30'; break;
			case 'blue': $c = '0;34'; break;
			case 'light_blue': $c = '1;34'; break;
			case 'green': $c = '0;32'; break;
			case 'light_green': $c = '1;32'; break;
			case 'cyan': $c = '0;36'; break;
			case 'light_cyan': $c = '1;36'; break;
			case 'red': $c = '0;31'; break;
			case 'light_red': $c = '1;31'; break;
			case 'purple': $c = '0;35'; break;
			case 'light_purple': $c = '1;35'; break;
			case 'brown': $c = '0;33'; break;
			case 'yellow': $c = '1;33'; break;
			case 'light_gray': $c = '0;37'; break;
			case 'white': $c = '1;37'; break;
			default: echo "$str"; return;
		}
		echo "\033[" . $c . "m" . $str . "\033[0m";
	}
	else {
		echo "$str";
	}
}

function displayCfgDiff($cfg1, $cfg2) {
	// find changed & old nodes
	foreach($cfg1 as $definedObject => $a) {
		if(!isset($cfg2[$definedObject])) {
			foreach($a as $k => $definedVals) {
				echo "Removed $definedObject: $k\n";
				coloredEcho("<<< ", 'red'); coloredEcho($definedVals['p'] . ':' . $definedVals['l'] . "\n", 'cyan');
				coloredEcho("< define $definedObject {\n", 'red');
				foreach($definedVals['v'] as $key => $val) {
					coloredEcho("< \t$key " . $val['v'] . "\n", 'red');
				}
				coloredEcho("< }\n", 'red');
			}
			continue;
		}
		foreach($a as $k => $definedVals) {
			if(!isset($cfg2[$definedObject][$k])) {
				echo "Removed $definedObject: $k\n";
				coloredEcho("<<< ", 'red'); coloredEcho($definedVals['p'] . ':' . $definedVals['l'] . "\n", 'cyan');
				coloredEcho("< define $definedObject {\n", 'red');
				foreach($definedVals['v'] as $key => $val) {
					coloredEcho("< \t$key " . $val['v'] . "\n", 'red');
				}
				coloredEcho("< }\n", 'red');
				continue;
			}
			$hasChanges = false;
			foreach($definedVals['v'] as $key => $val) {
				if(!isset($cfg2[$definedObject][$k]['v'][$key]) || $val['v'] != $cfg2[$definedObject][$k]['v'][$key]['v']) {
					$hasChanges = true;
					break;
				}
			}
			if($hasChanges) {
				echo "Changed $definedObject: $k\n";
				coloredEcho("<<< ", 'red'); coloredEcho($definedVals['p'] . ':' . $definedVals['l'] . "\n", 'cyan');
				coloredEcho(">>> ", 'green'); coloredEcho($cfg2[$definedObject][$k]['p'] . ':' . $cfg2[$definedObject][$k]['l'] . "\n", 'cyan');
				coloredEcho("| define $definedObject {\n", 'brown');
				foreach($definedVals['v'] as $key => $val) {
					if(!isset($cfg2[$definedObject][$k]['v'][$key])) {
						coloredEcho("< \t$key " . $val['v'] . "\n", 'red');
					}
					elseif($val['v'] != $cfg2[$definedObject][$k]['v'][$key]['v']) {
						coloredEcho("< \t$key " . $val['v'] . "\n", 'red');
						coloredEcho("> \t$key " . $cfg2[$definedObject][$k]['v'][$key]['v'] . "\n", 'green');
					}
					else {
						coloredEcho("| \t$key " . $val['v'] . "\n", 'brown');
					}
				}
				coloredEcho("| }\n", 'brown');
			}
		}
	}

	// new nodes
	foreach($cfg2 as $definedObject => $a) {
		if(!isset($cfg1[$definedObject])) {
			foreach($a as $k => $definedVals) {
				echo "New $definedObject: $k\n";
				coloredEcho(">>> ", 'green'); coloredEcho($definedVals['p'] . ':' . $definedVals['l'] . "\n", 'cyan');
				coloredEcho("> define $definedObject {\n", 'green');
				foreach($definedVals['v'] as $key => $val) {
					coloredEcho("> \t$key " . $val['v'] . "\n", 'green');
				}
				coloredEcho("> }\n", 'green');
			}
			continue;
		}
		foreach($a as $k => $definedVals) {
			if(!isset($cfg1[$definedObject][$k])) {
				echo "New $definedObject: $k\n";
				coloredEcho(">>> ", 'green'); coloredEcho($definedVals['p'] . ':' . $definedVals['l'] . "\n", 'cyan');
				coloredEcho("> define $definedObject {\n", 'green');
				foreach($definedVals['v'] as $key => $val) {
					coloredEcho("> \t$key " . $val['v'] . "\n", 'green');
				}
				coloredEcho("> }\n", 'green');
				continue;
			}
		}
	}
}

