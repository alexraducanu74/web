<?php
class ModelStats {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getMostReadBooks() {
        $sql = "SELECT b.title, SUM(ubp.pages_read) AS total_pages 
                FROM books b 
                JOIN user_book_progress ubp ON b.id = ubp.book_id 
                GROUP BY b.id 
                ORDER BY total_pages DESC 
                LIMIT 10";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopularGenres() {
        $sql = "SELECT genre, COUNT(*) AS count 
                FROM books 
                GROUP BY genre 
                ORDER BY count DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopRatedBooks() {
        $sql = "SELECT b.title, ROUND(AVG(ubp.rating), 2) AS avg_rating 
                FROM books b 
                JOIN user_book_progress ubp ON b.id = ubp.book_id 
                GROUP BY b.id 
                ORDER BY avg_rating DESC 
                LIMIT 10";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserBookStartCount() {
        $sql = "SELECT COUNT(DISTINCT user_id) AS user_count 
                FROM user_book_progress";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC)['user_count'];
    }
}
