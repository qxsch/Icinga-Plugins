# PluginParser

a plugin output parser to validate check plugins


```php
<?php

$output = checkIcingaPlugin($stdOut, $stdErr, $returnCode);

// EMPTY LOGS = NO PROBLEMS FOUND - EVERYTHING IS OK
echo "LOGS:\n";
foreach($output['logs'] as $record) {
    // 0 = LOG TYPE  (ERROR, WARNING, INFO)
    // 1 = LOG MESSAGE
    // 2 = HELP MESSAGE / PROPOSED SOLUTION
    echo $record[0]."\t".$record[1]."\t".$record[2]."\n";
}

// DUMP ALL INFORMATION, THAT WE FOUND

echo "SUMMARY TEXT:\n";
print_r($output['Summary']);

echo "MULTILINE TEXT:\n";
print_r($output['Multiline']);

echo "PERFORMANCE DATA:\n";
print_r($output['PerfData']);

echo "MULTILINE PERFORMANCE DATA":\n";
print_r($output['MultilinePerfData']);

```
