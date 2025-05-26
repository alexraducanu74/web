<?php
// File: models/ModelLogin.php

class ModelLogin
{ // No longer directly extends Dbh

    /**
     * Fetches user by UID or email and verifies password.
     *
     * @param string $uid Username or Email
     * @param string $pwd Password
     * @return array|string Returns user data array on success, or an error string indicator on failure.
     */
    public function getUser(string $uid, string $pwd)
    {
        $dbh = Dbh::getInstance()->getConnection();
        $stmt = $dbh->prepare('SELECT users_id, users_uid, users_pwd FROM users WHERE users_uid = ? OR users_email = ?;');

        if (!$stmt->execute([$uid, $uid])) {
            $stmt = null;
            return "stmtfailed"; // Statement execution failed
        }

        if ($stmt->rowCount() == 0) {
            $stmt = null;
            return "usernotfound"; // User not found
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($pwd, $user['users_pwd'])) {
            $stmt = null;
            return "wrongpassword"; // Password verification failed
        }

        // Password verified, return relevant user data for token generation
        $stmt = null;
        return ['users_id' => $user['users_id'], 'users_uid' => $user['users_uid']];
    }

    protected function connect()
    { // To maintain compatibility with existing code
        return Dbh::getInstance()->getConnection();
    }
}
?>