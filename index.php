<?php
function databaseConn() {
    $conn = new mysqli("10.66.176.42","blacklist","6l8nZOMqykM67yGl","blacklist");
    return $conn;
}

function getIp() {
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if(getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if(getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else $ip = "Unknow";
    return $ip;
}


function alog($log) {
    $date = date("Y-m-d H:i:s");
    $log_str = "[{$date}] $log\n";
    file_put_contents('login.log', $log_str, FILE_APPEND);
}

function filterInput($str) {
	$tarr = explode("\n", $str);
	$arr = [];
	foreach ($tarr as $item) {
		$item = str_replace(['"', "'"], '', $item);
		$item = trim($item);
		if ($item !== '') {
			$arr []= $item;
		}
	}
	return implode("\n", $arr);
}

$allowIp = ['14.152.78.34' /* 工会后台Windows服务器 */
        , '121.8.164.78'   /* 侨景 IP */
        , '219.135.214.20' /* 远晖9楼IP */
        , '113.108.182.26' /* 远晖8楼IP */
];
if (!in_array(getIp(), $allowIp)) {
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 Forbidden</h1>";
    exit();
}


$message = '';
$indexMessage = '';
session_start();
$conn = databaseConn();
$isShowIndex = false;

/* 处理登录页 */
if ($_POST['username']) {
    $sql1 = "select * from user";
    $result = $conn->query($sql1);
    $user_exists = false;
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            if ($row["username"] == $_POST['username']) {
                if ($row["password"] == md5($_POST['password'])) {
                    $_SESSION["login"] = 1;
                    $user_exists = true;
                    alog("登录成功: {$_POST['username']}");
                } else {
                    $message = '密码错误';
                    $user_exists = true;
                    alog("登录失败: {$_POST['username']}");
                }
            }
        }
        if (!$user_exists) {
            $message = "密码错误";
            alog("用户不存在: {$_POST['username']}");
        }
    }
}

/* 处理index页面提交数据 */
if (isset($_SESSION["login"]) && ($_SESSION["login"] == 1) && $_POST['ipv4']) {
    $sql1 = "select * from ip";
    $result = $conn->query($sql1);
    $attr = $result->fetch_all();
    $ipv4 = filterInput($_POST['ipv4']);
    $ipv6 = filterInput($_POST['ipv6']);
    $white_ipv4 = filterInput($_POST['white_ipv4']);
    $white_ipv6 = filterInput($_POST['white_ipv6']);
    $ipv4 = $conn->real_escape_string($ipv4);
    $ipv6 = $conn->real_escape_string($ipv6);
    $white_ipv4 = $conn->real_escape_string($white_ipv4);
    $white_ipv6 = $conn->real_escape_string($white_ipv6);

    if ($attr) {
        $sql = "update ip set ipv4_lock = '$ipv4', ipv6_lock = '$ipv6', ipv4_unlock = '$white_ipv4', ipv6_unlock = '$white_ipv6'";
    } else {
        $sql = "insert into ip(ipv4_lock, ipv6_lock, ipv4_unlock, ipv6_unlock) value('$ipv4', '$ipv6', '$white_ipv4', '$white_ipv6')";
    }
    $result = $conn->query($sql);
    $indexMessage = '修改成功';
}

/* 显示index页面数据 */
if (isset($_SESSION["login"]) && $_SESSION["login"] == 1) {
    $isShowIndex = true;
    $sql = "select * from ip";
    $result = $conn->query($sql);
    $attr = $result->fetch_all();
    $ipv4_lock = $ipv6_lock = $ipv4_unlock = $ipv6_unlock = '';
    if ($attr) {
        $ipv4_lock = $attr[0][1];
        $ipv6_lock = $attr[0][2];
        $ipv4_unlock = $attr[0][3];
        $ipv6_unlock = $attr[0][4];
    }
}

if ($_POST['logout']) {
    $isShowIndex = false;
    $_SESSION["login"] = 0;
    session_destroy();
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>IP管理</title>
    </head>
    <body>
    <form method="post" id="login">
    <?php
        if (!$isShowIndex) {
    ?>
        <p>用户名: <input type="text" name="username"/></p>
        <p>密　码: <input type="password" name="password"/></p>
        <input type="submit" value="登录"/>
        <div><?=$message?></div>
    <?php
        } else {
    ?>
        <table>
            <tr>
                <td>IPV4封锁：</td>
                <td><textarea name="ipv4" style="width: 600px;height: 150px;"><?=$ipv4_lock?></textarea></td>
            </tr>
            <tr>
                <td>IPV6封锁: </td>
                <td><textarea name="ipv6" style="width: 600px;height: 150px;"><?=$ipv6_lock?></textarea></td>
            </tr>
            <tr>
                <td>IPV4白名单: </td>
                <td><textarea name="white_ipv4" style="width: 600px;height: 150px;"><?=$ipv4_unlock?></textarea></td>
            </tr>
            <tr>
                <td>IPV6白名单: </td>
                <td><textarea name="white_ipv6" style="width: 600px;height: 150px;"><?=$ipv6_unlock?></textarea></td>
            </tr>
        </table>
        <input type="submit" value="更新"/><input type="submit" value="退出登录" name="logout"/>
        <div><?=$indexMessage?></div>
    <?php
        }
    ?>
    </form>
    </body>
</html>