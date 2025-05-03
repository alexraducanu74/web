<?php
include '../nav/navbar.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "classes/dbh.classes.php";
    include "classes/signup.classes.php";
    include "classes/signup-contrl.classes.php";

    $signup = new SignupContr($_POST["uid"], $_POST["pwd"], $_POST["pwdrepeat"], $_POST["email"]);
    $error = $signup->signupUser();
}
?>


<section class="index-login">
    <div class="wrapper">
        <div class="index-login-signup">
            <h4>SIGN UP</h4>
            <p>Don't have an account yet? Sign up here!</p>

            <?php if (!empty($error)): ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="post">
                <div> <input type="text" name="uid" placeholder="Username"> </div>
                <div> <input type="password" name="pwd" placeholder="Password"> </div>
                <div> <input type="password" name="pwdrepeat" placeholder="Repeat Password"> </div>
                <div> <input type="text" name="email" placeholder="E-mail"> </div>
                <br>
                <button type="submit" name="submit">SIGN UP</button>
            </form>
        </div>
    </div>
</section>