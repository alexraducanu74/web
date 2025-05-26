<?php
// File: models/ModelSignup.php

class ModelSignup
{ // No longer directly extends Dbh

    /**
     * Internal helper method to get the PDO connection from the Dbh singleton.
     * This allows existing calls like $this->connect()->prepare() to continue working.
     *
     * @return PDO
     */
    protected function connect(): PDO
    {
        return Dbh::getInstance()->getConnection();
    }

    /**
     * Checks if a username or email is already taken.
     *
     * @param string $uid Username
     * @param string $email Email
     * @return bool True if user does not exist (available), false if taken.
     */
    public function checkUser(string $uid, string $email): bool
    {
        // Use the connect() helper method to get the PDO connection
        $stmt = $this->connect()->prepare('SELECT users_uid FROM users WHERE users_uid = ? OR users_email = ?;');

        if (!$stmt->execute([$uid, $email])) {
            $stmt = null;
            // In a production environment, you might log this error
            // or throw an exception for better error handling in the controller.
            return false; // Indicating a problem or user potentially exists
        }

        return $stmt->rowCount() === 0; // True if no rows found (user is available)
    }

    /**
     * Creates a new user in the database.
     *
     * @param string $uid Username
     * @param string $pwd Password
     * @param string $email Email
     * @return bool True on success, false on failure.
     */
    public function setUser(string $uid, string $pwd, string $email): bool
    {
        $stmt = $this->connect()->prepare('INSERT INTO users (users_uid, users_pwd, users_email) VALUES (?, ?, ?);');
        $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);

        if (!$stmt->execute([$uid, $hashed_pwd, $email])) {
            $errorInfo = $stmt->errorInfo();
            echo "Eroare la inserare: " . $errorInfo[2] . "<br>";
            $stmt = null;
            return false; // Insertion failed
        }

        $stmt = null;
        return true; // User created successfully
    }
}
?>