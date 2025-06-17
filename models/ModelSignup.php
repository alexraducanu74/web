<?php
class ModelSignup
{
    protected function connect(): PDO
    {
        return Dbh::getInstance()->getConnection();
    }
    public function checkUser(string $uid, string $email): bool
    {
        $stmt = $this->connect()->prepare('SELECT users_uid FROM users WHERE users_uid = ? OR users_email = ?;');
        if (!$stmt->execute([$uid, $email])) {
            $stmt = null;
            return false;
        }
        return $stmt->rowCount() === 0;
    }
    public function setUser(string $uid, string $pwd, string $email): bool
    {
        try {
            $stmt = $this->connect()->prepare('INSERT INTO users (users_uid, users_pwd, users_email) VALUES (?, ?, ?);');
            $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);
            if (!$stmt->execute([$uid, $hashed_pwd, $email])) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database error: " . $errorInfo[2]);
            }
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>