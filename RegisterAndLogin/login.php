<?php
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "classes/dbh.classes.php";
    include "classes/login.classes.php";
    include "classes/login-contrl.classes.php";

    $login = new LoginContr($_POST["uid"], $_POST["pwd"]);
    $error = $login->LoginUser();
}
?>

<section class="index-login">
    <div class="wrapper">
        <div class="index-login-login">
            <h4>LOGIN</h4>
            <p>Already have an account? Login here!</p>

            <?php
            if (!empty($error)) {
                echo "<p style='color:red;'>$error</p>";
            }
            ?>

            <form method="post">
                <div><input type="text" name="uid" placeholder="Username"></div>
                <div><input type="password" name="pwd" placeholder="Password"></div>
                <div><input type="text" name="email" placeholder="E-mail"></div>
                <br>
                <button type="submit" name="submit">LOGIN</button>
            </form>
        </div>
    </div>
</section>