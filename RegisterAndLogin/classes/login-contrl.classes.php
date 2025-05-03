<?php

class LoginContr extends Login
{
    private $uid;
    private $pwd;

    public function __construct($uid, $pwd)
    {
        $this->uid = $uid;
        $this->pwd = $pwd;
    }

    public function loginUser()
    {
        if ($this->emptyInput() == false) {
            return "Please fill in all fields.";
        }

        if (!$this->getUser($this->uid, $this->pwd)) {
            return "Incorrect login details.";
        }

        return "";
    }


    private function emptyInput()
    {
        return !(empty($this->uid) || empty($this->pwd));
    }



}
