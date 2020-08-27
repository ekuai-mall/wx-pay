<?php
include 'WxPay.php';

$notify = new WxPay();
$result = $notify->NativeGetPayUrl('$body', '$attach', 1, 233);
//$result = $notify->orderQuery('wx2714465605338687beee1af90c29f70000');
echo '<pre>';
print_r($result);