<?php 
session_start();
require_once '../core/dbConfig.php'; 
require_once '../core/models.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
</head>
<body>
    <div class="header">
        <h1>GDocs Clone</h1>
        <div class="header-user">
            <span>Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="../core/handleForms.php?logout=1" class="btn btn-logout">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="main-content">

            <div class="content">
                <?php 
                $userId = $_SESSION['user_id'];
                $ownedDocs = getOwnedDocuments($pdo, $userId);
                $sharedDocs = getSharedDocuments($pdo, $userId);
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-primary"></div>
                        <h2>My Documents (<?php echo count($ownedDocs); ?>)</h2>
                    </div>

                    <?php if (empty($ownedDocs)): ?>
                        <p style="color: #6b7280;">You haven't created any documents yet. <a href="createDocument.php" style="color: #22c55e;">Create your first document</a></p>
                    <?php else: ?>
                        <div class="document-list">
                            <?php foreach ($ownedDocs as $doc): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                                        <p>Last edited: <?php echo htmlspecialchars($doc['last_updated']); ?></p>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="user_edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary">Edit</a>
                                        <a href="shareDocument.php?id=<?php echo $doc['id']; ?>" class="btn btn-secondary">Share</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-secondary"></div>
                        <h2>Shared with Me (<?php echo count($sharedDocs); ?>)</h2>
                    </div>

                    <?php if (empty($sharedDocs)): ?>
                        <p style="color: #6b7280;">No documents have been shared with you yet.</p>
                    <?php else: ?>
                        <div class="document-list">
                            <?php foreach ($sharedDocs as $doc): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                                        <p>Owner: <?php echo htmlspecialchars($doc['owner_name']); ?> • Last edited: <?php echo htmlspecialchars($doc['last_updated']); ?></p>
                                        <p style="font-size: 12px; color: #22c55e;">Access: <?php echo ucfirst($doc['access_type']); ?></p>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($doc['access_type'] === 'editor'): ?>
                                            <a href="user_edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary">Edit</a>
                                        <?php endif; ?>
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-secondary">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>