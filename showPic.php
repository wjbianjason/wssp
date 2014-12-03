<?php
$picurl = stripcslashes($_REQUEST["picurl"]);
$content = file_get_contents($picurl);
header("Content-Type: image/jpg; charset=UTF-8");
echo $content;
?>