<?php

if (!isset($_GET['key']) || $_GET['key'] != '1x3d34dd321') {
    echo '非法访问';exit;
}

// 读取数据库的值
$conn = new mysqli("10.66.176.42","blacklist","6l8nZOMqykM67yGl","blacklist");

//写SQL语句
$sql = "select * from ip";
$result = $conn->query($sql);
//读数据
$attr = $result->fetch_all();

$ipv4_lock = $ipv6_lock = $ipv4_unlock = $ipv6_unlock = '';
if ($attr) {
    $data = [];
    $data['ipv4'] = str_replace("\r\n", "\n", $attr[0][1]);
    $data['ipv6'] = str_replace("\r\n", "\n", $attr[0][2]);
    $data['white4'] = str_replace("\r\n", "\n", $attr[0][3]);
    $data['white6'] = str_replace("\r\n", "\n", $attr[0][4]);
    echo json_encode($data);
}
