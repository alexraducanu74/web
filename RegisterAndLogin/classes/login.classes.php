<?php
class Login extends Dbh
{
    protected function getUser($uid, $pwd)
    {
        $stmt = $this->connect()->prepare('SELECT * FROM users WHERE users_uid = ? OR users_email = ?;');

        if (!$stmt->execute(array($uid, $uid))) {
            $stmt = null;
            header("location: ../index.php/?error=stmtfailed");
            return false;
        }

        if ($stmt->rowCount() == 0) {
            $stmt = null;
            header("location: ../index.php/?error=usernotfound");
            return false;
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($pwd, $user['users_pwd'])) {
            $stmt = null;
            return false;
        }

        session_start();
        $_SESSION['userid'] = $user['users_id'];
        $_SESSION['useruid'] = $user['users_uid'];

        $stmt = null;

        return true;
    }
}
