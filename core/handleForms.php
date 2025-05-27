<?php
session_start();
require_once 'dbConfig.php';
require_once 'models.php';

//Register
if (isset($_POST['registerBtn'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role']; 
 
    if (!empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $result = registerUser($pdo, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role);
            header("Location: ../php/index.php");
        } else {
            header("Location: ../php/registerpage.php");
        }
    } else {
        header("Location: ../php/registerpage.php");
    }
}

//Login
if (isset($_POST['loginBtn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $user = getUserByEmail($pdo, $email);

        if ($user) {
            if ($user['suspended']) {
                $_SESSION['message'] = "Your account has been suspended.";
                $_SESSION['status'] = "403";
                header("Location: ../php/index.php");
                exit;
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'Admin') {
                    header("Location: ../php/admin_page.php");
                } elseif ($user['role'] === 'User') {
                    header("Location: ../php/user_home.php");
                } else {
                    header("Location: ../php/index.php");
                }
                exit;
            } else {

                header("Location: ../php/index.php");
                exit;
            }
        } else {
            header("Location: ../php/index.php");
            exit;
        }
    } else {
        header("Location: ../php/index.php");
        exit;
    }
}

//Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../php/index.php");
}

//Createdocument
function handleCreateDocumentForm($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createDoc'])) {
        $title = trim($_POST['title']);

        if (!empty($title)) {
            $userId = $_SESSION['user_id'];
            $documentId = createDocument($pdo, $title, $userId);

            if ($documentId) {
                logActivity($pdo, $documentId, $userId, "Document created");
                header("Location: user_edit.php?id=" . $documentId);
                exit;
            } else {
                return "Failed to create document. Please try again.";
            }
        } else {
            return "Please enter a document title.";
        }
    }

    return null; 
}

//Sendmessage
function handleSendMessageForm($pdo, $document_id)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $message = trim($_POST['message']);

        if (!empty($message)) {
            if (sendMessage($pdo, $document_id, $_SESSION['user_id'], $message)) {
                logActivity($pdo, $document_id, $_SESSION['user_id'], 'sent_message');
                $_SESSION['message'] = "Message sent successfully!";
                $_SESSION['status'] = "200";
            } else {
                $_SESSION['message'] = "Failed to send message.";
                $_SESSION['status'] = "400";
            }
        } else {
            $_SESSION['message'] = "Message cannot be empty.";
            $_SESSION['status'] = "400";
        }

        header("Location: doc_messages.php?doc_id=" . $document_id);
        exit;
    }
}

//Sharedocument
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $formType = $_POST['form_type'];

    switch ($formType) {
        case 'share_document':
            $documentId = (int)($_POST['document_id'] ?? 0);
            $shareEmail = trim($_POST['share_email'] ?? '');
            $canEdit = isset($_POST['can_edit']);
            $currentUserId = $_SESSION['user_id'] ?? 0;

            if ($documentId <= 0 || !$shareEmail) {
                $_SESSION['message'] = "Invalid document or email.";
                $_SESSION['status'] = "400";
                header("Location: ../php/shareDocument.php?id=$documentId");
                exit;
            }

            // Ownership check
            if (!isDocumentOwner($pdo, $documentId, $currentUserId)) {
                $_SESSION['message'] = "You don't have permission to share this document.";
                $_SESSION['status'] = "403";
                header("Location: user_home.php");
                exit;
            }

            $shareUser = getUserByEmail($pdo, $shareEmail);
            if (!$shareUser) {
                $_SESSION['message'] = "User with email '{$shareEmail}' not found.";
                $_SESSION['status'] = "404";
            } elseif ($shareUser['id'] == $currentUserId) {
                $_SESSION['message'] = "You cannot share a document with yourself.";
                $_SESSION['status'] = "400";
            } else {
                $success = shareDocument($pdo, $documentId, $shareUser['id'], $canEdit, $currentUserId);
                if ($success) {
                    $accessType = $canEdit ? 'editor' : 'viewer';
                    $_SESSION['message'] = "Document successfully shared with {$shareUser['name']} as {$accessType}.";
                    $_SESSION['status'] = "200";
                } else {
                    $_SESSION['message'] = "Failed to share document. Please try again.";
                    $_SESSION['status'] = "500";
                }
            }

            header("Location: ../php/shareDocument.php?id=$documentId");
            exit;

        case 'remove_access':
            $documentId = (int)($_POST['document_id'] ?? 0);
            $removeUserId = (int)($_POST['remove_user_id'] ?? 0);
            $currentUserId = $_SESSION['user_id'] ?? 0;

            if ($documentId <= 0 || $removeUserId <= 0) {
                $_SESSION['message'] = "Invalid request.";
                $_SESSION['status'] = "400";
                header("Location: ../php/shareDocument.php?id=$documentId");
                exit;
            }

            if (!isDocumentOwner($pdo, $documentId, $currentUserId)) {
                $_SESSION['message'] = "You don't have permission to modify this document.";
                $_SESSION['status'] = "403";
                header("Location: user_home.php");
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM access_share WHERE document_id = ? AND user_id = ?");
                $success = $stmt->execute([$documentId, $removeUserId]);

                if ($success) {
                    logActivity($pdo, $documentId, $currentUserId, "removed access for user_id $removeUserId");
                    $_SESSION['message'] = "Access removed successfully.";
                    $_SESSION['status'] = "200";
                } else {
                    $_SESSION['message'] = "Failed to remove access.";
                    $_SESSION['status'] = "500";
                }
            } catch (PDOException $e) {
                error_log("Error removing access: " . $e->getMessage());
                $_SESSION['message'] = "An error occurred.";
                $_SESSION['status'] = "500";
            }

            header("Location: ../php/shareDocument.php?id=$documentId");
            exit;

        default:
            $_SESSION['message'] = "Unknown form submission.";
            $_SESSION['status'] = "400";
            header("Location: user_home.php");
            exit;
    }
}

//Autosave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_POST['document_id'])) {
    $content = $_POST['content'];
    $documentId = (int)$_POST['document_id'];
    $userId = $_SESSION['user_id'];

    $canEdit = userHasEditAccess($pdo, $documentId, $userId);

    if ($canEdit) {
        if (updateDocumentContent($pdo, $documentId, $content, $userId)) {
            echo json_encode(['status' => 'success', 'message' => 'Document saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to edit this document']);
    }
    exit;
}


