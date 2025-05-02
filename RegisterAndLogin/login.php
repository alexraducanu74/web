<section class="index-login">
    <div class="wrapper">
        <div class="index-login-signup">
            <h4>SIGN UP</h4>
            <p>Don't have an account yet? Sign up here!</p>
            <form action="includes/signup.inc.php" method="post">
                <div> <input type="text" name="uid" placeholder="Username"> </div>
                <div> <input type="password" name="pwd" placeholder="Password"> </div>
                <div> <input type="password" name="pwdrepeat" placeholder="Repeat Password"> </div>
                <div> <input type="text" name="email" placeholder="E-mail"> </div>
                <br>
                <button type="submit" name="submit">SIGN UP</button>
            </form>
        </div>
        <div class="index-login-login">
            <h4>LOGIN</h4>
            <p>Already have an account? Login here!</p>
            <form action="includes/login.inc.php" method="post">
                <div> <input type="text" name="uid" placeholder="Username"> </div>
                <div> <input type="password" name="pwd" placeholder="Password"> </div>
                <div> <input type="text" name="email" placeholder="E-mail"> </div>
                <br>
                <button type="submit" name="submit">LOGIN</button>
            </form>
        </div>
    </div>
</section>