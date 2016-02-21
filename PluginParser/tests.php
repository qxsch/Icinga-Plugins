<?php

require_once('PluginParser.php');


echo "\n";
var_dump(checkIcingaPlugin('test', '', 2));
var_dump(checkIcingaPlugin('test \\| test', '', 2));
var_dump(checkIcingaPlugin('test | test', '', 2));
var_dump(checkIcingaPlugin('test | single=1
test
test
test |
\'diskC\'=1
diskD=2', '', 2));
var_dump(checkIcingaPlugin('test | single=-1
| diskC=1.5KB
diskD=2', '', 2));
/*var_dump(checkIcingaPlugin('test
test
test
test', '', 2));*/
echo "\n";

