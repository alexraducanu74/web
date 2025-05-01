<?php

class SignupContr extends Signup
{
    private $uid;
    private $pwd;
    private $pwdRepeat;
    private $email;

    public function __construct($uid, $pwd, $pwdRepeat, $email)
    {
        $this->uid = $uid;
        $this->pwd = $pwd;
        $this->pwdRepeat = $pwdRepeat;
        $this->email = $email;
    }

    public function signupUser()
    {
        if ($this->emptyInput() == false) {
            header("location: ../index.php/?error=emptyinput");
            exit();
        }
        if ($this->invalidUid() == false) {
            header("location: ../index.php/?error=invaliduid");
            exit();
        }
        if ($this->invalidEmail() == false) {
            header("location: ../index.php/?error=invalidemail");
            exit();
        }
        if ($this->pwdMatch() == false) {
            header("location: ../index.php/?error=pwdmatch");
            exit();
        }
        if ($this->uidTakenCheck() == false) {
            header("location: ../index.php/?error=usernameoremailtaken");
            exit();
        }

        $this->setUser($this->uid, $this->pwd, $this->email);

    }

    private function emptyInput()
    {
        return !(empty($this->uid) || empty($this->pwd) || empty($this->pwdRepeat) || empty($this->email));
    }

    private function invalidUid()
    {
        return preg_match("/^[a-zA-Z0-9]*$/", $this->uid);
    }

    private function invalidEmail()
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    private function pwdMatch()
    {
        return $this->pwd === $this->pwdRepeat;
    }

    private function uidTakenCheck()
    {
        if (!$this->checkUser($this->uid, $this->email)) {
            return false;
        } else
            return true;
    }



}
