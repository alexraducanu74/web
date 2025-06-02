<?php
class ViewGroup
{
    private function loadLayout(string $title, string $content, string $authLinks): string
    {
        $layoutPath = __DIR__ . '/layout.tpl';
        if (!file_exists($layoutPath)) {
            return "Error: Main layout template not found at {$layoutPath}.";
        }

        $layout = file_get_contents($layoutPath);
        $layout = str_replace('{$title}', htmlspecialchars($title), $layout);
        $layout = str_replace('{$content}', $content, $layout);
        $layout = str_replace('{$authLinks}', $authLinks, $layout);

        return $layout;
    }

    private function getAuthLinks(): string
    {
        if (isset($_SESSION['user_id'])) {
            return '<a href="index.php?controller=auth&actiune=logout">Logout (' . htmlspecialchars($_SESSION['username'] ?? '') . ')</a>';
        } else {
            return '
                <a href="index.php?controller=auth&actiune=showLoginForm">Login</a>
                <a href="index.php?controller=auth&actiune=showRegisterForm">Register</a>';
        }
    }

    public function renderPage(string $title, string $content): void
    {
        $authLinks = $this->getAuthLinks();
        echo $this->loadLayout($title, $content, $authLinks);
    }

    public function renderCreateForm(array $data = []): void
    {
        $error = $data['error'] ?? '';
        $success = $data['success'] ?? '';

        $content = '<h2>Create New Group</h2>';
        if ($error) {
            $content .= '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        if ($success) {
            $content .= '<p style="color:green;">' . htmlspecialchars($success) . '</p>';
        }
        $content .= '<form action="index.php?controller=group&actiune=create" method="post" class="form-styled">
                        <div class="form-group">
                            <label for="group_name">Group Name:</label>
                            <input type="text" id="group_name" name="group_name" required>
                        </div>
                        <div class="form-group">
                            <label for="group_description">Description (Optional):</label>
                            <textarea id="group_description" name="group_description"></textarea>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" id="requires_approval" name="requires_approval" value="1">
                            <label for="requires_approval">Require admin approval for new members</label>
                        </div>
                        <button type="submit" class="btn">Create Group</button>
                      </form>';
        $this->renderPage('Create Group', $content);
    }

    public function renderGroupPage(array $group, array $members, bool $isMember, bool $isCreator, ?string $memberStatus, ?string $message = null, ?string $errorMessage = null): void
    {
        $content = '';
        if ($message) {
            $content .= '<p style="color:green;">' . htmlspecialchars($message) . '</p>';
        }
        if ($errorMessage) {
            $content .= '<p style="color:red;">' . htmlspecialchars($errorMessage) . '</p>';
        }

        $content .= '<h1>' . htmlspecialchars($group['group_name']) . '</h1>';
        $content .= '<p class="group-description">' . nl2br(htmlspecialchars($group['group_description'] ?? 'No description.')) . '</p>';
        $content .= '<p><em>Created by: ' . htmlspecialchars($group['creator_username']) . ' on ' . date("F j, Y", strtotime($group['created_at'])) . '</em></p>';

        if ($isCreator) {
            $content .= '<div class="admin-info">';
            $content .= '<p><strong>Secret Code: <code>' . htmlspecialchars($group['secret_code']) . '</code></strong> (Share this with friends to invite them)</p>';
            $content .= '<p><a href="index.php?controller=group&actiune=manageRequests&parametrii=' . $group['group_id'] . '" class="btn btn-admin">Manage Join Requests</a></p>';
            $content .= '</div>';
        }

        $currentUserId = $_SESSION['user_id'] ?? null;
        if ($currentUserId && !$isMember && $memberStatus !== 'pending') {
            $content .= '<div class="join-group-section">
                            <h3>Join this Group</h3>
                            <form action="index.php?controller=group&actiune=joinWithCode" method="post" class="form-inline">
                                <input type="hidden" name="group_id_for_code_join" value="' . $group['group_id'] . '">
                                <div class="form-group">
                                    <label for="secret_code_join">Secret Code:</label>
                                    <input type="text" id="secret_code_join" name="secret_code" required>
                                </div>
                                <button type="submit" class="btn">Join Group</button>
                            </form>
                         </div>';
        } elseif ($currentUserId && $memberStatus === 'pending') {
            $content .= '<p class="status-pending">Your request to join this group is pending approval.</p>';
        } elseif ($currentUserId && $isMember) {
            $content .= '<p class="status-member">You are a member of this group.</p>';
        }


        $content .= '<h3>Members (' . count($members) . ')</h3>';
        if (empty($members)) {
            $content .= '<p>No approved members yet.</p>';
        } else {
            $content .= '<ul class="member-list">';
            foreach ($members as $member) {
                $content .= '<li>' . htmlspecialchars($member['users_uid']) . ' (Joined: ' . date("F j, Y", strtotime($member['join_date'])) . ')</li>';
            }
            $content .= '</ul>';
        }

        $this->renderPage(htmlspecialchars($group['group_name']), $content);
    }

    public function renderManageRequests(array $group, array $pendingMembers, ?string $message = null): void
    {
        $content = '<h2>Manage Join Requests for "' . htmlspecialchars($group['group_name']) . '"</h2>';
        if ($message) {
            $content .= '<p style="color:green;">' . htmlspecialchars($message) . '</p>';
        }

        if (empty($pendingMembers)) {
            $content .= '<p>No pending requests.</p>';
        } else {
            $content .= '<ul class="request-list">';
            foreach ($pendingMembers as $member) {
                $content .= '<li>
                                User: <strong>' . htmlspecialchars($member['users_uid']) . '</strong> (Requested on: ' . date("F j, Y", strtotime($member['join_date'])) . ')
                                <form action="index.php?controller=group&actiune=processRequest" method="post" style="display:inline; margin-left: 10px;">
                                    <input type="hidden" name="group_member_id" value="' . $member['group_member_id'] . '">
                                    <input type="hidden" name="group_id_for_redirect" value="' . $group['group_id'] . '">
                                    <button type="submit" name="request_action" value="approve" class="btn btn-approve">Approve</button>
                                    <button type="submit" name="request_action" value="deny" class="btn btn-deny">Deny</button>
                                </form>
                             </li>';
            }
            $content .= '</ul>';
        }
        $content .= '<p style="margin-top: 20px;"><a href="index.php?controller=group&actiune=view&parametrii=' . $group['group_id'] . '" class="btn">Back to Group</a></p>';

        $this->renderPage('Manage Join Requests', $content);
    }


    public function renderUserGroupsPage(array $groups, ?string $message = null, ?string $errorMessage = null): void
    {
        $content = '<h2>My Groups</h2>';

        if ($message) {
            $content .= '<p style="color:green;">' . htmlspecialchars($message) . '</p>';
        }
        if ($errorMessage) {
            $content .= '<p style="color:red;">' . htmlspecialchars($errorMessage) . '</p>';
        }

        $content .= '<hr>
                     <h3>Join an Existing Group</h3>
                     <form action="index.php?controller=group&actiune=joinWithCode" method="post" class="form-inline">
                        <div class="form-group">
                            <label for="secret_code_join_general">Secret Code:</label>
                            <input type="text" id="secret_code_join_general" name="secret_code" required>
                        </div>
                        <button type="submit" class="btn">Join Group</button>
                     </form>
                     <hr>';

        if (empty($groups)) {
            $content .= '<p>You are not a member of any groups yet.</p>';
        } else {
            $content .= '<h3>Your Groups:</h3><ul class="group-list">';
            foreach ($groups as $group) {
                $content .= '<li><a href="index.php?controller=group&actiune=view&parametrii=' . $group['group_id'] . '">' . htmlspecialchars($group['group_name']) . '</a></li>';
            }
            $content .= '</ul>';
        }
        $this->renderPage('My Groups', $content);
    }

    public function renderError(string $errorMessage, string $title = "Error"): void
    {
        $content = '<h2>Error</h2><p style="color:red;">' . htmlspecialchars($errorMessage) . '</p>';
        $content .= '<p><a href="index.php">Go to Homepage</a></p>';
        $this->renderPage($title, $content);
    }
}
?>