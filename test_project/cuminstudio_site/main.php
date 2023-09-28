<?php
ob_start();
header("content-type:text/html;charset=utf-8");
if (isset($_COOKIE['sid']) && isset($_COOKIE['sn']) && isset($_COOKIE['to'])) {
    $sid = $_COOKIE['sid'];
    $sn = $_COOKIE['sn'];
    $to = $_COOKIE['to'];
    session_start();
    if (session_id() === $sid && session_name() === $sn) {
        if ($to <= time()) {
            err("会话超时", "8");
        } else {
            //
            require_once("./template.php");
            if (is_file('./module/perm.php')) {
                //鉴权操作
            } else {
                //构造页面
                LoadingMainPage($tmp[1], $to);
                $_SESSION["last_time"] = time() + 900;
                setcookie("to", $_SESSION["last_time"], $_SESSION["last_time"]);
            }
        }
    } else {
        //  验证失败
        err(403);
    }
} else {
    err(404);
}

//错误返回函数
/**
 * Summary of err
 * @param int $errcode
 * @param string|null $errtext
 * @return never
 */
function err(int $errcode, string $errtext = null)
{
    if ($errcode === 429) {
        header("HTTP/1.1 429 Too Many Requests");
        header("Status: 429 Too Many Requests");
        exit;
    } else if ($errcode === 403) {
        header("HTTP/1.1 403 Forbidden");
        header("Status: 403 Forbidden");
        exit;
    } else {
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        exit;
    }
}

?>