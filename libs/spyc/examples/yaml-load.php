<?php

#
#    S P Y C
#      a simple php yaml class
#
# license: [MIT License, http://www.opensource.org/licenses/mit-license.php]
#

include(dirname(__DIR__) . '/Spyc.php');

$array = Spyc::YAMLLoad(dirname(__DIR__) . '/spyc.yaml');

echo '<pre><a href="spyc.yaml">spyc.yaml</a> loaded into PHP:<br/>';
print_r($array);
echo '</pre>';


echo '<pre>YAML Data dumped back:<br/>';
echo Spyc::YAMLDump($array);
echo '</pre>';
