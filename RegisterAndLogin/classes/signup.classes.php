<?php
class Signup extends Dbh
{
    protected function checkUser($uid, $email)
    {
        $stmt = $this->connect()->prepare('SELECT users_uid FROM users WHERE users_uid=? OR users_email=?;');
        if (!$stmt->execute(array($uid, $email))) {
            $stmt = null;
            header("location: ../index.php/?error=stmtfailed");
            exit();
        }

        if ($stmt->rowCount() > 0) {
            return false;
        } else {
            return true;
        }

    }

    protected function setUser($uid, $pwd, $email)
    {
        $stmt = $this->connect()->prepare('INSERT INTO users (users_uid, users_pwd, users_email) VALUES (?,?,?);');
        $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);
        if (!$stmt->execute(array($uid, $hashed_pwd, $email))) {
            $stmt = null;
            header("location: ../index.php/?error=stmtfailed");
            exit();
        }

        $stmt = null;

    }
}

