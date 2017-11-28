<?php
$path  = '/home/www/html/twitter/';
$files = array('data1'=>'data1.json', 'data2'=>'data2.json');

echo ("{");

$i = 0;
foreach($files as $name => $file) {
    $i++;
    if($i>=2) { echo ","; }
    echo '"'.$name.'"'.":";
    readfile($path.$file);
}
echo ("}");
?>
