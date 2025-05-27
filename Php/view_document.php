<?php 
session_start();
require_once '../core/dbConfig.php'; 
require_once '../core/models.php'; 

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['User', 'Admin'])) {
    header("Location: index.php");
    exit;
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$document = getDocumentById($pdo, $documentId);
if (!$document) {
    header("Location: user_home.php");
    exit;
}

//Access control
if ($role === 'User') {
    $canView = userHasViewAccess($pdo, $documentId, $userId);
    if (!$canView) {
        header("Location: user_home.php");
        exit;
    }
}

//Log view activity
logActivity($pdo, $documentId, $userId, "Document viewed");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View: <?php echo htmlspecialchars($document['title']); ?> - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>GDocs Clone</h1>
        <div class="header-user">
            <span>Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="../core/handleForms.php?logout=1" class="btn btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-title">
            <h2><?php echo htmlspecialchars($document['title']); ?></h2>
            <div class="nav-links">
                <?php 
                $homeLink = ($_SESSION['role'] === 'Admin') ? 'admin_page.php' : 'user_home.php';
                ?>
                <a href="<?= $homeLink ?>">← Back to Documents</a>
                
                <?php 
                $canEdit = userHasEditAccess($pdo, $documentId, $userId);
                if ($canEdit): 
                ?>
                    <a href="user_edit.php?id=<?php echo $documentId; ?>">Edit Document</a>
                <?php endif; ?>
                
                <?php if ($canEdit): ?>
                    <a href="shareDocument.php?id=<?php echo $documentId; ?>">Share Document</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Info -->
        <div class="card">
            <div class="card-header">
                <div class="dot dot-primary"></div>
                <h2>Document Information</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; color: #6b7280; font-size: 14px;">
                <div>
                    <strong style="color: #374151;">Document ID:</strong> <?= $documentId ?>
                </div>
                <div>
                    <strong style="color: #374151;">Created:</strong><br>
                    <?= isset($document['created_at']) ? date('M j, Y g:i A', strtotime($document['created_at'])) : 'Unknown' ?>
                </div>
                <?php if (isset($document['updated_at']) && $document['updated_at']): ?>
                <div>
                    <strong style="color: #374151;">Last Updated:</strong><br>
                    <?= date('M j, Y g:i A', strtotime($document['updated_at'])) ?>
                </div>
                <?php endif; ?>
                <div>
                    <strong style="color: #374151;">Status:</strong><br>
                    <span style="background-color: #f0fdf4; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 12px;">
                        Read-only Mode
                    </span>
                </div>
            </div>
        </div>

        <!-- Document Content -->
        <div class="card">
            <div class="card-header">
                <div class="dot dot-secondary"></div>
                <h2>Document Content</h2>
            </div>
            
            <div style="padding: 16px; background-color: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; min-height: 200px;">
                <?php 
                if (!empty($document['content'])) {
                    echo $document['content']; 
                } else {
                    echo '<p style="color: #6b7280;"><em>This document is empty.</em></p>';
                }
                ?>
            </div>
            
            <?php if ($canEdit): ?>
            <div style="margin-top: 16px; text-align: right;">
                <a href="user_edit.php?id=<?php echo $documentId; ?>" class="btn btn-primary">Edit Document</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>