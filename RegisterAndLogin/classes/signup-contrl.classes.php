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
            return "Please input all the fields.";
        }
        if ($this->invalidUid() == false) {
            return "Username is invalid.";
        }
        if ($this->invalidEmail() == false) {
            return "Email adress is invalid";
        }
        if ($this->pwdMatch() == false) {
            return "Passwords do not match.";
        }
        if ($this->uidTakenCheck() == false) {
            return "Username already taken";
        }

        $this->setUser($this->uid, $this->pwd, $this->email);

        return "";

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
