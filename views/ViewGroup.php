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

    private function loadTemplate(string $filePath, array $data): string
    {
        if (!file_exists($filePath)) {
            return "Error: Template file not found at {$filePath}";
        }

        $template = file_get_contents($filePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', (string) $value, $template);
        }
        return $template;
    }

    private function getAuthLinks(): string
    {
        if (isset($_SESSION['user_id'])) {
            return '<a href="index.php?controller=feed&actiune=myBooks">My Books</a>
                    <a href="index.php?controller=group&actiune=myGroups">My Groups</a>
                    <a href="index.php?controller=group&actiune=showCreateForm">Create Group</a>
                    <div class="separator"></div>
                    <a href="index.php?controller=auth&actiune=logout">Logout (' . htmlspecialchars($_SESSION['username'] ?? '') . ')</a>';
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

    public function renderGroupPage(array $group, array $members, bool $isMember, bool $isCreator, ?string $memberStatus, array $groupBooks, ?string $message = null, ?string $errorMessage = null): void
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
                            <form action="index.php?controller=group&actiune=joinWithCode" method="post" class="form-inline join-code-form">
                                <input type="hidden" name="group_id_for_code_join" value="' . $group['group_id'] . '">
                                <div class="form-group">
                                    <label for="secret_code_join">Secret Code:</label>
                                    <input type="text" id="secret_code_join" name="secret_code" required class="form-control">
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

        $content .= '<hr><h3>Group Bookshelf</h3>';

        if (empty($groupBooks)) {
            $content .= '<p>No books have been added to this group yet.</p>';
        } else {
            $content .= '<div class="book-list">';
            foreach ($groupBooks as $book) {
                $content .= "
                    <div class='book'>
                        <a href='index.php?controller=group&actiune=viewBook&parametrii={$group['group_id']},{$book['id']}' class='book-link'>
                            <img src='assets/" . htmlspecialchars($book['cover_image']) . "' alt='Cover of " . htmlspecialchars($book['title']) . "'>
                            <h3>" . htmlspecialchars($book['title']) . "</h3>
                            <p>by " . htmlspecialchars($book['author']) . "</p>
                        </a>
                    </div>
                ";
            }
            $content .= '</div>';
        }

        $this->renderPage(htmlspecialchars($group['group_name']), $content);
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

    public function renderAddBookForm(array $group, array $books, string $searchTerm): void
    {
        $searchResultsHtml = '';
        if (!empty($searchTerm)) {
            if (empty($books)) {
                $searchResultsHtml = '<p>No books found matching your search.</p>';
            } else {
                $searchResultsHtml .= '<h3>Search Results:</h3><ul class="request-list">';
                foreach ($books as $book) {
                    $searchResultsHtml .= '<li>
                        ' . htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author']) . '
                        <form action="index.php?controller=group&actiune=addBook" method="post" style="display:inline; margin-left: 10px;">
                            <input type="hidden" name="group_id" value="' . $group['group_id'] . '">
                            <input type="hidden" name="book_id" value="' . $book['id'] . '">
                            <button type="submit" class="btn btn-approve">Add to Group</button>
                        </form>
                    </li>';
                }
                $searchResultsHtml .= '</ul>';
            }
        }

        $viewContent = $this->loadTemplate(__DIR__ . '/add-book-form.tpl', [
            'group_name' => htmlspecialchars($group['group_name']),
            'group_id' => $group['group_id'],
            'search_term' => htmlspecialchars($searchTerm),
            'search_results_html' => $searchResultsHtml
        ]);

        $this->renderPage('Add Book to Group', $viewContent);
    }

    public function renderGroupBookProgressPage(array $group, array $book, array $membersProgress): void
    {
        $membersProgressHtml = '';
        if (empty($membersProgress)) {
            $membersProgressHtml = '<p>No progress recorded from members yet.</p>';
        } else {
            foreach ($membersProgress as $progress) {
                $progressPercent = 0;
                if (!empty($progress['total_pages']) && $progress['total_pages'] > 0) {
                    $progressPercent = round(((int) ($progress['pages_read'] ?? 0) / (int) $progress['total_pages']) * 100);
                }

                $membersProgressHtml .= '<div class="review-item" style="border-bottom: 1px solid #ccc; padding-bottom: 15px; margin-bottom: 15px;">';
                $membersProgressHtml .= '<h4>' . htmlspecialchars($progress['users_uid']) . '</h4>';

                $pagesRead = (int) ($progress['pages_read'] ?? 0);
                $totalPages = (int) ($book['total_pages'] ?? 0);

                if ($totalPages > 0) {
                    $membersProgressHtml .= '<div class="progress-bar">';
                    $membersProgressHtml .= '<div class="progress" style="width: ' . $progressPercent . '%;">' . $progressPercent . '%</div>';
                    $membersProgressHtml .= '</div>';
                    $membersProgressHtml .= '<p>' . $pagesRead . ' / ' . $totalPages . ' pages read</p>';
                }

                if (!empty($progress['review'])) {
                    $rating = (int) ($progress['rating'] ?? 0);
                    $ratingStars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                    $membersProgressHtml .= '<p><strong>Rating:</strong> <span class="review-rating">' . $ratingStars . '</span></p>';
                    $membersProgressHtml .= '<blockquote><p>' . nl2br(htmlspecialchars($progress['review'])) . '</p></blockquote>';
                } else {
                    $membersProgressHtml .= '<p><em>No review submitted yet.</em></p>';
                }

                $membersProgressHtml .= '</div>';
            }
        }

        $viewContent = $this->loadTemplate(__DIR__ . '/group-book-progress.tpl', [
            'book_title' => htmlspecialchars($book['title']),
            'group_name' => htmlspecialchars($group['group_name']),
            'members_progress_html' => $membersProgressHtml,
        ]);

        $this->renderPage($book['title'] . ' - Group Progress', $viewContent);
    }
}
?>