<?php
require_once 'dbConfig.php';

function authenticateUser($pdo, $email, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password']) && !$user['suspended']) {
            return $user;
        }
    } catch (PDOException $e) {
        error_log("Error in authenticateUser: " . $e->getMessage());
    }
    return false;
}

function registerUser($pdo, $name, $email, $password, $role) {
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $role);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error in registerUser: " . $e->getMessage());
        return false;
    }
}

function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getAllUsers($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = :role");
    $stmt->execute(['role' => 'User']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllUsersAdmin($pdo) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role, u.created_at, u.suspended,
               (SELECT COUNT(*) FROM documents WHERE owner_id = u.id) as document_count
        FROM users u 
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserByEmail($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUserByEmail: " . $e->getMessage());
        return false;
    }
}

function createDocument($pdo, $title, $owner_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO documents (title, owner_id) VALUES (?, ?)");
        if ($stmt->execute([$title, $owner_id])) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error in createDocument: " . $e->getMessage());
        return false;
    }
}

function updateDocumentContent($pdo, $docId, $content, $userId = null) {
    $stmt = $pdo->prepare("UPDATE documents SET content = ?, last_updated = NOW() WHERE id = ?");
    $success = $stmt->execute([$content, $docId]);
    if ($success && $userId !== null) {
        logActivity($pdo, $docId, $userId, "document content updated");
    }
    return $success;
}

function updateDocumentContentWithMessage($pdo, $docId, $content, $logMessage, $userId = null) {
    $stmt = $pdo->prepare("UPDATE documents SET content = ?, last_updated = NOW() WHERE id = ?");
    $success = $stmt->execute([$content, $docId]);
    if ($success && $userId !== null) {
        logActivity($pdo, $docId, $userId, $logMessage);
    }
    return $success;
}


function getDocumentById($pdo, $docId) {
    $stmt = $pdo->prepare("SELECT d.*, u.name as owner_name FROM documents d JOIN users u ON d.owner_id = u.id WHERE d.id = ?");
    $stmt->execute([$docId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOwnedDocuments($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.last_updated, u.name as owner_name, 'owner' as access_type
        FROM documents d
        JOIN users u ON d.owner_id = u.id
        WHERE d.owner_id = ?
        ORDER BY d.last_updated DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllDocuments($pdo) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.created_at, d.last_updated, u.name as owner_name, u.email as owner_email,
               (SELECT COUNT(*) FROM access_share WHERE document_id = d.id) as shared_count
        FROM documents d 
        JOIN users u ON d.owner_id = u.id 
        ORDER BY d.last_updated DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getSharedDocuments($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.last_updated, u.name as owner_name, 
               CASE WHEN ac.can_edit = 1 THEN 'editor' ELSE 'viewer' END as access_type
        FROM access_share ac
        JOIN documents d ON ac.document_id = d.id
        JOIN users u ON d.owner_id = u.id
        WHERE ac.user_id = ? AND d.owner_id != ?
        ORDER BY d.last_updated DESC
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function shareDocument($pdo, $documentId, $userId, $canEdit = true, $sharedByUserId = null) {
    $stmt = $pdo->prepare("INSERT INTO access_share (document_id, user_id, can_edit) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_edit = VALUES(can_edit)");
    $success = $stmt->execute([$documentId, $userId, $canEdit]);
    if ($success && $sharedByUserId !== null) {
        $accessType = $canEdit ? 'editor' : 'viewer';
        logActivity($pdo, $documentId, $sharedByUserId, "shared document with user_id $userId as $accessType");
    }
    return $success;
}

function userHasEditAccess($pdo, $documentId, $userId) {
    if (isDocumentOwner($pdo, $documentId, $userId)) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT can_edit FROM access_share WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$documentId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (bool)$row['can_edit'] : false;
}

function userHasViewAccess($pdo, $documentId, $userId) {
    if (isDocumentOwner($pdo, $documentId, $userId)) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT id FROM access_share WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$documentId, $userId]);
    return $stmt->rowCount() > 0;
}

function getSharedUsersByDocumentId(PDO $pdo, int $documentId): array {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, ac.can_edit 
            FROM access_share ac 
            JOIN users u ON ac.user_id = u.id 
            WHERE ac.document_id = ?
            ORDER BY u.name ASC
        ");
        $stmt->execute([$documentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching shared users: " . $e->getMessage());
        return [];
    }
}

function isDocumentOwner($pdo, $documentId, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM documents WHERE id = ? AND owner_id = ?");
    $stmt->execute([$documentId, $userId]);
    return $stmt->rowCount() > 0;
}

function logActivity($pdo, $documentId, $userId, $action) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
    return $stmt->execute([$documentId, $userId, $action]);
}

function getDocumentActivityLogs($pdo, $documentId) {
    $stmt = $pdo->prepare("
        SELECT al.*, u.name as user_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        WHERE al.document_id = ?
        ORDER BY al.timestamp DESC
    ");
    $stmt->execute([$documentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sendMessage($pdo, $documentId, $userId, $message) {
    $stmt = $pdo->prepare("INSERT INTO document_messages (document_id, user_id, message) VALUES (?, ?, ?)");
    return $stmt->execute([$documentId, $userId, $message]);
}

function getMessagesForDocument($pdo, $documentId) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as user_name 
        FROM document_messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.document_id = ?
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$documentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>