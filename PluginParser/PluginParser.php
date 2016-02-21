<?php


/**
 * Parses the icinga plugin output
 * @param string $stdOut the output received from standard output
 * @param string $stdErr the output received standard error
 * @param int $returnCode  the return code, that was received
 * @return array an associative array with the following keys: logs, Summary, SummaryPerfData, Multiline, MultilinePerfData
 */
function checkIcingaPlugin($stdOut, $stdErr, $returnCode) {
	$parsed=array();
	$log=array();
	if($stdErr!='') {
		$log[]=array('ERROR', 'Standard error stream is not empty.', 'Please use 2>/dev/null to surpress standard error messages within your plugin.');
	}
	if(!preg_match('/^(0|1|2|3)$/', $returnCode)) {
		$log[]=array('ERROR', 'Invalid Plugin return code "'.$returnCode.'" found.', 'Please use just use 0,1,2,3 as plugin return code.');
	}
	// length > 64 KB
	if(strlen($stdOut)>65536) {
		$log[]=array('ERROR', 'Plugin output is longer than 64KB.', 'The plugin output cannot be longer than 64KB.');
	}
	// length > 8 KB
	elseif(strlen($stdOut)>8192) {
		$log[]=array('WARNING', 'Plugin output is longer than 8KB.', 'It is recommended, that the plugin output should not be longer than 8KB.');
	}


	if(!preg_match(
		'/^'.	// start of string
		'(?<Summary>([^|\n]+|((?<=\\\\)\|))+)([\t ]+\|[\t ]+(?<SummaryPerfData>.+))?'.  // summary with optional perf data
		'(?<Multiline>(\s+([^|\n]+|((?<=\\\\)\|))+)+)?'.               // optional multiline output
		'(\s+\|(?<MultilinePerfData>(\s+([^|]+|((?<=\\\\)\|))+)+))?'.  // optional multiline perfdata
		'$/',   // end of string
		$stdOut,
		$parsed
	)) {
		$log[]=array('ERROR', 'Invalid Plugin output format.', '');
	}

	foreach($parsed as $key => $val) {
		if(is_numeric($key)) unset($parsed[$key]);
	}

	// Summary, SummaryPerfData, Multiline, MultilinePerfData
	foreach(array('SummaryPerfData', 'MultilinePerfData') as $key) {
		if(isset($parsed[$key])) {
			if(!preg_match(
				'/^'.   // start of string
				'(\s+'.
					'([^ \t\r\n=\']+|\'[^\r\n\']+\')'. // label
					'='.				   // equals
					'-?\d+(\.\d+)?'.		   // value
					'(s|us|ms|%|b|kb|mb|tb|gb|c)?'.    // unit
					'(;\d+)?'.        // warn
					'(;\d+)?'.        // crit
					'(;\d+)?'.        // min
					'(;\d+)?'.        // max
				')+'.
				'\s*'.
				'$/i',   // end of string
				' '.$parsed[$key]
			)) {
				$log[]=array('ERROR', 'Invalid '.$key.' found.', 'Format is \'Label\'=Value[UOM];[warn];[crit];[min];[max]  (Multiple key/value pairs are seperated by whitespaces)');
			}
			if(preg_match_all(
				'/\s+'.
				'(?<label>[^ \t\r\n=\']+|\'[^\r\n\']+\')'. // label
				'='.				   // equals
				'(?<value>-?\d+(\.\d+)?)'.	   // value
				'(?<unit>s|us|ms|%|b|kb|mb|tb|gb|c)?'.    // unit
				'(?<warn>;\d+)?'.        // warn
				'(?<crit>;\d+)?'.        // crit
				'(?<min>;\d+)?'.        // min
				'(?<max>;\d+)?'.        // max
				'/i', 
				' '.$parsed[$key],
				$matches
			)) {
				foreach($matches as $key => $val) {
					if(is_numeric($key))  unset($matches[$key]);
				}
				foreach($matches['label'] as $key => $label) {
					$label=trim($label, '\'');
					if(!isset($parsed['PerfData'][$label])) {
						$parsed['PerfData'][$label]=array(
							'label' => $label,
							'value' => $matches['value'][$key],
							'unit' => $matches['unit'][$key],
							'warn' => $matches['warn'][$key],
							'crit' => $matches['crit'][$key],
							'min' => $matches['min'][$key],
							'max' => $matches['max'][$key],
						);
					}
					else {
						$log[]=array('ERROR', 'Performance data label "'.$label.'" is multiple times defined.', 'Labels are unique.');
					}
					if(strlen($label)>19) {
						$log[]=array('WARNING', 'Performance data label '.$label.' is longer than 19 characters.', 'Labels should not be longer than 19 characters.');
					}
				}
			}

		}
	}

	$parsed['logs'] = $log;
	return $parsed;
}



