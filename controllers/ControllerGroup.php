<?php
class ControllerGroup extends Controller
{
    private ModelGroup $modelGroup;
    private ViewGroup $ViewGroup;

    public function __construct(string $actiune, array $parametri)
    {
        parent::__construct();

        try {
            $this->modelGroup = new ModelGroup();
            $this->ViewGroup = new ViewGroup();

            $user = $this->getAuthenticatedUser();
            $currentUserId = $user ? $user['user_id'] : null;

            if (!$currentUserId && !in_array($actiune, ['view'])) {
                $this->ViewGroup->renderError("You must be logged in to access this page.", "Login Required");
                exit;
            }

            $message = isset($_GET['status']) ? $this->getFlashMessage($_GET['status']) : null;
            $errorMessage = isset($_GET['error']) ? $this->getFlashMessage($_GET['error'], true) : null;


            switch ($actiune) {
                case 'showCreateForm':
                    $this->showCreateForm();
                    break;
                case 'create':
                    $this->createGroup();
                    break;
                case 'view':
                    if (isset($parametri[0])) {
                        $this->viewGroup((int) $parametri[0], $message, $errorMessage);
                    } else {
                        $this->listUserGroups($message, $errorMessage);
                    }
                    break;
                case 'joinWithCode':
                    $this->joinGroupWithCode();
                    break;
                case 'manageRequests':
                    if (isset($parametri[0]) && $currentUserId) {
                        $this->manageRequests((int) $parametri[0], $currentUserId, $message);
                    } else {
                        $this->ViewGroup->renderError("Group ID not provided or user not logged in.", "Access Error");
                    }
                    break;
                case 'processRequest':
                    $groupIdForRedirect = isset($_POST['group_id_for_redirect']) ? (int) $_POST['group_id_for_redirect'] : null;
                    if (isset($_POST['group_member_id'], $_POST['request_action']) && $currentUserId) {
                        $this->processJoinRequest((int) $_POST['group_member_id'], $_POST['request_action'], $currentUserId, $groupIdForRedirect);
                    } else {
                        $this->ViewGroup->renderError("Missing parameters for processing request.", "Processing Error");
                    }
                    break;
                case 'myGroups':
                    $this->listUserGroups($message, $errorMessage);
                    break;
                case 'viewBook':
                    if (isset($parametri[0], $parametri[1]) && $currentUserId) {
                        $this->viewGroupBook((int) $parametri[0], (int) $parametri[1], $currentUserId);
                    } else {
                        $this->ViewGroup->renderError("Group or Book ID not provided.", "Access Error");
                    }
                    break;
                default:
                    $this->ViewGroup->renderError("The requested group action was not found.", "Action Not Found");
                    break;
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Numele grupului este prea scurt') !== false) {
                $message = "Numele grupului trebuie sa aiba cel putin 3 caractere.";
            } else {
                $message = "Eroare la inregistrare. Incearca din nou.";
            }

            $this->ViewGroup->renderError($message, "Database Error");
            exit;
        }
    }

    private function showCreateForm(): void
    {
        $data = [];
        if (isset($_GET['error'])) {
            $data['error'] = $this->getFlashMessage($_GET['error'], true);
        }
        $this->ViewGroup->renderCreateForm($data);
    }

    private function createGroup(): void
    {
        $user = $this->getAuthenticatedUser();
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !$user) {
            header('Location: index.php?controller=group&actiune=showCreateForm&error=invalid_request');
            exit;
        }

        $name = trim($_POST['group_name'] ?? '');
        $description = trim($_POST['group_description'] ?? null);
        $creatorUserId = $user['user_id'];
        $requiresApproval = isset($_POST['requires_approval']);

        if (empty($name)) {
            header('Location: index.php?controller=group&actiune=showCreateForm&error=group_name_required');
            exit;
        }

        $groupId = $this->modelGroup->createGroup($name, $description, $creatorUserId, $requiresApproval);

        if ($groupId) {
            header('Location: index.php?controller=group&actiune=view&parametri=' . $groupId . '&status=created');
            exit;
        } else {
            header('Location: index.php?controller=group&actiune=showCreateForm&error=create_failed');
            exit;
        }
    }

    private function viewGroup(int $groupId, ?string $message = null, ?string $errorMessage = null): void
    {
        $group = $this->modelGroup->getGroupById($groupId);
        if (!$group) {
            $this->ViewGroup->renderError("The group you are looking for does not exist.", "Group Not Found");
            return;
        }

        $user = $this->getAuthenticatedUser();
        $currentUserId = $user ? $user['user_id'] : null;
        $isMember = false;
        $isCreator = false;
        $memberStatus = null;

        if ($currentUserId) {
            $isMember = $this->modelGroup->isUserMember($groupId, $currentUserId);
            $isCreator = $this->modelGroup->isUserGroupCreator($groupId, $currentUserId);
            $memberStatus = $this->modelGroup->getUserMembershipStatus($groupId, $currentUserId);
        }

        $members = $this->modelGroup->getGroupMembers($groupId, 'approved');
        $groupBooks = $this->modelGroup->getGroupBooksFromMembersProgress($groupId);

        $this->ViewGroup->renderGroupPage($group, $members, $isMember, $isCreator, $memberStatus, $groupBooks, $message, $errorMessage);
    }

    private function joinGroupWithCode(): void
    {
        $user = $this->getAuthenticatedUser();
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !$user || !isset($_POST['secret_code'])) {
            header('Location: index.php?controller=group&actiune=myGroups&error=invalid_join_attempt');
            exit;
        }

        $currentUserId = $user['user_id'];
        $secretCode = trim($_POST['secret_code']);
        $groupIdForContext = isset($_POST['group_id_for_code_join']) ? (int) $_POST['group_id_for_code_join'] : null;

        if (empty($secretCode)) {
            $redirectTarget = $groupIdForContext ? 'index.php?controller=group&actiune=view&parametri=' . $groupIdForContext . '&error=code_required' : 'index.php?controller=group&actiune=myGroups&error=code_required';
            header('Location: ' . $redirectTarget);
            exit;
        }

        $group = $this->modelGroup->getGroupBySecretCode($secretCode);

        if (!$group) {
            $redirectTarget = $groupIdForContext ? 'index.php?controller=group&actiune=view&parametri=' . $groupIdForContext . '&error=invalid_code' : 'index.php?controller=group&actiune=myGroups&error=invalid_code';
            header('Location: ' . $redirectTarget);
            exit;
        }

        $groupIdToJoin = (int) $group['group_id'];
        if ($groupIdForContext && $groupIdForContext !== $groupIdToJoin) {
            header('Location: index.php?controller=group&actiune=view&parametri=' . $groupIdForContext . '&error=code_mismatch');
            exit;
        }

        if ($this->modelGroup->isUserMember($groupIdToJoin, $currentUserId)) {
            header('Location: index.php?controller=group&actiune=view&parametri=' . $groupIdToJoin . '&status=already_member');
            exit;
        }

        $initialStatus = $group['requires_approval'] ? 'pending' : 'approved';
        $joinAttempt = $this->modelGroup->addMemberToGroup($groupIdToJoin, $currentUserId, $initialStatus);

        if ($joinAttempt) {
            $statusMessage = $initialStatus === 'pending' ? 'request_sent' : 'joined_successfully';
            header('Location: index.php?controller=group&actiune=view&parametri=' . $groupIdToJoin . '&status=' . $statusMessage);
        } else {
            header('Location: index.php?controller=group&actiune=view&parametri=' . $groupIdToJoin . '&error=join_failed_or_pending');
        }
        exit;
    }

    private function manageRequests(int $groupId, int $currentUserId, ?string $message = null): void
    {
        if (!$this->modelGroup->isUserGroupCreator($groupId, $currentUserId)) {
            $this->ViewGroup->renderError("You do not have permission to manage requests for this group.", "Access Denied");
            exit;
        }
        $group = $this->modelGroup->getGroupById($groupId);
        if (!$group) {
            $this->ViewGroup->renderError("Group not found.", "Error");
            exit;
        }
        $pendingMembers = $this->modelGroup->getGroupMembers($groupId, 'pending');

        $this->ViewGroup->renderManageRequests($group, $pendingMembers, $message);
    }

    private function processJoinRequest(int $groupMemberId, string $action, int $currentUserId, ?int $groupIdForRedirect): void
    {
        $memberEntry = $this->modelGroup->getGroupMemberEntryById($groupMemberId);

        if (!$memberEntry) {
            $this->ViewGroup->renderError("Join request not found.", "Error");
            exit;
        }

        $groupId = $memberEntry['group_id'];

        if (!$this->modelGroup->isUserGroupCreator($groupId, $currentUserId)) {
            $this->ViewGroup->renderError("You do not have permission to process this request.", "Access Denied");
            exit;
        }

        $success = false;
        if ($action === 'approve') {
            $success = $this->modelGroup->updateMemberStatus($groupMemberId, 'approved');
        } elseif ($action === 'deny') {
            $success = $this->modelGroup->updateMemberStatus($groupMemberId, 'denied');
        }

        $redirectGroupId = $groupIdForRedirect ?? $groupId;

        if ($success) {
            header('Location: index.php?controller=group&actiune=manageRequests&parametri=' . $redirectGroupId . '&status=request_processed');
        } else {
            header('Location: index.php?controller=group&actiune=manageRequests&parametri=' . $redirectGroupId . '&error=processing_failed');
        }
        exit;
    }

    private function listUserGroups(?string $message = null, ?string $errorMessage = null): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            $this->ViewGroup->renderError("You must be logged in to view your groups.", "Login Required");
            exit;
        }
        $userId = $user['user_id'];
        $groups = $this->modelGroup->getUserGroups($userId);

        $this->ViewGroup->renderUserGroupsPage($groups, $message, $errorMessage);
    }

    private function getFlashMessage(string $key, bool $isError = false): ?string
    {
        $messages = [
            'created' => 'Group created successfully!',
            'request_sent' => 'Your request to join the group has been sent.',
            'joined_successfully' => 'You have successfully joined the group!',
            'already_member' => 'You are already a member of this group.',
            'request_processed' => 'The join request has been processed.',
            'login_required' => 'You need to be logged in to perform this action.',
            'invalid_request' => 'Invalid request method.',
            'group_name_required' => 'Group name is required.',
            'create_failed' => 'Failed to create the group. Please try again.',
            'invalid_join_attempt' => 'Invalid attempt to join the group.',
            'code_required' => 'A secret code is required to join.',
            'invalid_code' => 'The secret code provided is invalid.',
            'code_mismatch' => 'The secret code does not match this group.',
            'join_failed_or_pending' => 'Failed to join the group. You may have already sent a request or an error occurred.',
            'not_admin' => 'You do not have permission to manage this group.',
            'access_denied' => 'Access Denied. You do not have permission for this action.',
        ];
        if (isset($messages[$key])) {
            return $messages[$key];
        }
        return $isError ? "An unknown error occurred." : "Action completed.";
    }

    private function viewGroupBook(int $groupId, int $bookId, int $currentUserId): void
    {
        if (!$this->modelGroup->isUserMember($groupId, $currentUserId)) {
            $this->ViewGroup->renderError("You must be a member of the group to view this content.", "Access Denied");
            exit;
        }

        $modelFeed = new ModelFeed();
        $book = $modelFeed->getBookById($bookId);
        if (!$book) {
            $this->ViewGroup->renderError("Book not found.", "Error");
            exit;
        }

        $group = $this->modelGroup->getGroupById($groupId);
        $membersProgress = $this->modelGroup->getMembersProgressForBook($groupId, $bookId);

        $this->ViewGroup->renderGroupBookProgressPage($group, $book, $membersProgress);
    }
}
?>