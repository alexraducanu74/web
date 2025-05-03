<?php
if (isset($_POST["submit"])) {
    $uid = $_POST["uid"];
    $pwd = $_POST["pwd"];
}
include "../classes/dbh.classes.php";
include "../classes/login.classes.php";
include "../classes/login-contrl.classes.php";

$login = new LoginContr($uid, $pwd);

$login->loginUser();

header("location: ../login.php?error=none");