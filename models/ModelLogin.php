<?php
class ModelLogin
{
    public function getUser(string $uid, string $pwd)
    {
        $dbh = Dbh::getInstance()->getConnection();
        $stmt = $dbh->prepare('SELECT users_id, users_uid, users_pwd FROM users WHERE users_uid = ? OR users_email = ?;');
        if (!$stmt->execute([$uid, $uid])) {
            $stmt = null;
            return "stmtfailed";
        }
        if ($stmt->rowCount() == 0) {
            $stmt = null;
            return "usernotfound";
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!password_verify($pwd, $user['users_pwd'])) {
            $stmt = null;
            return "wrongpassword";
        }
        $stmt = null;
        return ['users_id' => $user['users_id'], 'users_uid' => $user['users_uid']];
    }
    protected function connect()
    {
        return Dbh::getInstance()->getConnection();
    }
}
?>