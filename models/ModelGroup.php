<?php
class ModelGroup
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Dbh::getInstance()->getConnection();
    }

    public function createGroup(string $name, ?string $description, int $creatorUserId, bool $requiresApproval): ?int
    {
        $stmtCode = $this->db->prepare("SELECT generate_unique_secret_code() as secret_code");
        $stmtCode->execute();
        $secretCode = $stmtCode->fetchColumn();

        $sql = "INSERT INTO groups (group_name, group_description, creator_user_id, secret_code, requires_approval) 
                VALUES (:name, :description, :creator_user_id, :secret_code, :requires_approval)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':creator_user_id', $creatorUserId, PDO::PARAM_INT);
        $stmt->bindParam(':secret_code', $secretCode);
        $stmt->bindParam(':requires_approval', $requiresApproval, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $groupId = (int) $this->db->lastInsertId();
            $this->addMemberToGroup($groupId, $creatorUserId, 'approved');
            return $groupId;
        }
        return null;
    }

    public function getGroupById(int $groupId): ?array
    {
        $sql = "SELECT g.*, u.users_uid as creator_username 
                FROM groups g 
                JOIN users u ON g.creator_user_id = u.users_id
                WHERE g.group_id = :group_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group ?: null;
    }

    public function getGroupBySecretCode(string $secretCode): ?array
    {
        $sql = "SELECT * FROM groups WHERE secret_code = :secret_code";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':secret_code', $secretCode);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group ?: null;
    }

    public function addMemberToGroup(int $groupId, int $userId, string $initialStatus = 'pending'): ?int
    {
        $sqlCheck = "SELECT group_member_id FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            return null;
        }

        $sql = "INSERT INTO group_members (group_id, user_id, member_status) VALUES (:group_id, :user_id, :member_status)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':member_status', $initialStatus);

        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        return null;
    }

    public function isUserMember(int $groupId, int $userId, string $status = 'approved'): bool
    {
        $sql = "SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id AND member_status = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function getUserMembershipStatus(int $groupId, int $userId): ?string
    {
        $sql = "SELECT member_status FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['member_status'] : null;
    }


    public function getGroupMembers(int $groupId, string $status = 'approved'): array
    {
        $sql = "SELECT u.users_id, u.users_uid, gm.join_date, gm.member_status, gm.group_member_id
                FROM group_members gm
                JOIN users u ON gm.user_id = u.users_id
                WHERE gm.group_id = :group_id AND gm.member_status = :status
                ORDER BY u.users_uid ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateMemberStatus(int $groupMemberId, string $status): bool
    {
        $sql = "UPDATE group_members SET member_status = :status WHERE group_member_id = :group_member_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':group_member_id', $groupMemberId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getGroupMemberEntry(int $groupId, int $userId): ?array
    {
        $sql = "SELECT * FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function isUserGroupCreator(int $groupId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM groups WHERE group_id = :group_id AND creator_user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function getUserGroups(int $userId): array
    {
        $sql = "SELECT g.group_id, g.group_name, g.secret_code
                FROM groups g
                JOIN group_members gm ON g.group_id = gm.group_id
                WHERE gm.user_id = :user_id AND gm.member_status = 'approved'
                ORDER BY g.group_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupMemberEntryById(int $groupMemberId): ?array
    {
        $sql = "SELECT * FROM group_members WHERE group_member_id = :group_member_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_member_id', $groupMemberId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }


    public function addBookToGroup(int $groupId, int $bookId, int $adminUserId): bool
    {
        $sql = "INSERT INTO group_books (group_id, book_id, added_by_user_id) VALUES (:group_id, :book_id, :admin_user_id)
                ON CONFLICT (group_id, book_id) DO NOTHING";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->bindParam(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getGroupBooks(int $groupId): array
    {
        $sql = "SELECT b.id, b.title, b.author, b.cover_image
                FROM books b
                JOIN group_books gb ON b.id = gb.book_id
                WHERE gb.group_id = :group_id
                ORDER BY gb.added_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMembersProgressForBook(int $groupId, int $bookId): array
    {
        $sql = "SELECT 
                    u.users_uid,
                    b.total_pages,
                    ubp.pages_read,
                    ubp.review,
                    ubp.rating
                FROM group_members gm
                JOIN users u ON gm.user_id = u.users_id
                LEFT JOIN user_book_progress ubp ON u.users_id = ubp.user_id AND ubp.book_id = :book_id
                LEFT JOIN books b ON ubp.book_id = b.id
                WHERE gm.group_id = :group_id AND gm.member_status = 'approved'
                ORDER BY u.users_uid";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>