<?php
ob_start();
header("content-type:text/html;charset=utf-8");
$posts = $_POST;
//  清除一些空白符号
foreach ($posts as $key => $value) {
    $posts[$key] = trim($value);
}
if (isset($posts['us']) && isset($posts['pw'])) {
    if (!empty($posts['us']) && !empty($posts['pw'])) {
        $u = $posts['us'];
        $p = md5($posts['pw']);
    } else {
        err(404);
    }
} else {
    err(404);
}
$state = 0;
session_start();
if (isset($_SESSION["lr_count"]) && isset($_SESSION["state"]) && isset($_SESSION["last_time"])) {
    $_SESSION["lr_count"] = $_SESSION["lr_count"] + 1;
    $temp_time = time() - 600;
    if ($_SESSION["lr_count"] > 3 && $_SESSION["last_time"] > $temp_time) {
        err(429,"登录频繁，请在5分钟后重试");
    } else {
        $_SESSION["lr_count"] = 0;
        $_SESSION["state"] = 0;
        $_SESSION["last_time"] = time() + 900;
        //session_unset();
        //session_destroy();
        $msg = array($_SESSION["lr_count"], $_SESSION["state"], $_SESSION["last_time"]);

    }
} else {
    $_SESSION["lr_count"] = 1;
    $_SESSION["state"] = 0;
    $_SESSION["last_time"] = time() + 900;
    $msg = array($_SESSION["lr_count"], $_SESSION["state"], $_SESSION["last_time"]);
}
if ($msg[0] < 5 && $msg[1] >= 0) {
    if (UserNameCheck($u)[0]) {
        //校验P
        $PCS = PasswordCheck($u, $p);
        if ($PCS[0]) {
            $_SESSION["user"] = $u;
            $_SESSION["state"] = 1;
            $_SESSION["uid"] = get_uid($u)[0];
            //cookie注入与后台加载阶段
            MgLoading($msg[2], session_id(), session_name());
        } else {
            //err(403,"密码错误!");
        }
    } else {
        err(403,"该用户不存在!");
    }
} else {
    err(404);
}
//[建立会话-注入cookie]-[检查U]-校验P-[注入cookie-加载页面(后台)]

//错误返回函数
/**
 * Summary of err
 * @param int $errcode
 * @param string|null $errtext
 * @return never
 */
function err(int $errcode,string $errtext = null){
    if ($errcode === 429){
        header("HTTP/1.1 429 Too Many Requests");
        header("Status: 429 Too Many Requests");
        exit;
    }else if($errcode === 403){
        header("HTTP/1.1 403 Forbidden");
        header("Status: 403 Forbidden");
        exit;
    }else{
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        exit;
    }
}

//用户名检查函数
/**
 * Summary of UserNameCheck
 * @param string $tag
 * @param string|null $mode
 * @return array
 */
function UserNameCheck(string $tag, string $mode = null)
{
	$result = array();
	include('./db_data.php');
	$dbc = mysqli_connect($host, $user, $pass, $dbn, $port);
	// Check connection
	if (!$dbc) {
		if ($mode === "debug") {
			$result[0] = false;
			$result[1] = mysqli_connect_error();
		} else {
			$result[0] = false;
		}
	} else {
		$dbs = mysqli_query($dbc, "SELECT *  FROM `usersdata` WHERE `UserName` LIKE '" . $tag . "';");
		if (mysqli_num_rows($dbs) === 1) {
			$result[0] = true;
		} else {
			if ($mode === "debug") {
				$result[0] = false;
				if ($dbs === false) {
					$result[1] = "查询失败";
				} else {
					$result[1] = "查询结果数量:" . mysqli_num_rows($dbs);
				}
			} else {
				$result[0] = false;
			}
		}
	}
	mysqli_close($dbc);
	return $result;
}

//密码校验
/**
 * Summary of PWCheck
 * @param string $u
 * @param string $p
 * @param string|null $mode
 * @return array
 */
function PasswordCheck(string $u, string $p, string $mode = null)
{
	$result = array();
	include('./db_data.php');
	$dbc = mysqli_connect($host, $user, $pass, $dbn, $port);
	// Check connection
	if (!$dbc) {
		if ($mode === "debug") {
			$result[0] = false;
			$result[1] = mysqli_connect_error();
		} else {
			$result[0] = false;
		}
	} else {
		$dbs = mysqli_query($dbc, "SELECT *  FROM `usersdata` WHERE `UserName` LIKE '" . $u . "';");
		if (mysqli_num_rows($dbs) === 1) {
			$dbsa = mysqli_fetch_all($dbs);
			if ($dbsa[0][3] === $p) {
				$result[0] = true;
			} else {
				$result[0] = false;
				if ($mode === "debug") {
					$result[1] = "查询结果数量:" . mysqli_num_rows($dbs) . "查询结果:" . $dbsa[0][3] . "比对目标:" . $p;
				}
			}

		} else {
			if ($mode === "debug") {
				$result[0] = false;
				if ($dbs === false) {
					$result[1] = "查询失败";
				} else {
					$result[1] = "查询结果数量:" . mysqli_num_rows($dbs);

				}
			} else {
				$result[0] = false;
			}
		}
	}
	mysqli_close($dbc);
	return $result;
}

//UID获取
function get_uid(string $username)
{
    $result = array();
    include('./db_data.php');
    $dbc = mysqli_connect($host, $user, $pass, $dbn, $port);
    // Check connection
    if (!$dbc) {
        if ($mode === "debug") {
            $result[0] = false;
            $result[1] = mysqli_connect_error();
        } else {
            $result[0] = false;
        }
    } else {
        $dbs = mysqli_query($dbc, "SELECT `UID` FROM `usersdata` WHERE `UserName` LIKE '" . $username . "'");
        if (mysqli_num_rows($dbs) > 0) {
            $result[0] = true;
            $result[1] = mysqli_fetch_all($dbs);

        } elseif (mysqli_num_rows($dbs) == 0) {
            $result[0] = true;
            $result[1] = mysqli_fetch_all($dbs);
        } else {
            $result[0] = false;
            if ($mode === "debug") {
                $result[1] = mysqli_error($dbc);
            }
        }
    }
    mysqli_close($dbc);
    return $result;
}

//后台页载入
function MgLoading(int $timeout, string $sid, string $sn){
    setcookie("sid", $sid, $timeout);
    setcookie("sn", $sn, $timeout);
    setcookie("to", $timeout, $timeout);
    //加载后台页面
    header('location:./mgc.html');
    exit();
}
?>