<?php 
session_start();

require_once '../core/dbConfig.php'; 
require_once '../core/models.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$document_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0);

if ($document_id <= 0) {
    $_SESSION['message'] = "Invalid document ID.";
    $_SESSION['status'] = "400";
    header("Location: admin_home.php");
    exit;
}

$document = getDocumentById($pdo, $document_id);
if (!$document) {
    $_SESSION['message'] = "Document not found.";
    $_SESSION['status'] = "404";
    header("Location: admin_home.php");
    exit;
}

$messages = getMessagesForDocument($pdo, $document_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin View - Messages: <?php echo htmlspecialchars($document['title']); ?></title>
    <link rel="stylesheet" href="../core/styles.css">
    <style>
        .admin-badge {
            background: #22c55e;
            color: white;
            padding: 2px 8px;
            font-size: 12px;
            border-radius: 4px;
            margin-left: 10px;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }
        
        .stat-item {
            text-align: center;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f9fafb;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #22c55e;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .messages-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .message-item {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .msg-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .msg-author {
            font-weight: 600;
            color: #374151;
        }
        
        .msg-time {
            color: #6b7280;
            font-size: 14px;
        }
        
        .msg-content {
            color: #374151;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .no-messages {
            padding: 40px;
            text-align: center;
            color: #6b7280;
        }
        
        .doc-meta {
            color: #6b7280;
            font-size: 14px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Document Messages <span class="admin-badge">ADMIN VIEW</span></h1>
        <div class="header-user">
            <span>Admin Dashboard</span>
        </div>
    </div>

    <div class="container">
        <div class="main-content" style="flex-direction: column;">
            
            <!-- Document Info -->
            <div class="card">
                <div class="card-header">
                    <div class="dot dot-primary"></div>
                    <h2><?php echo htmlspecialchars($document['title']); ?></h2>
                </div>
                <div class="doc-meta">
                    Owner: <?php echo htmlspecialchars($document['owner_name']); ?> | 
                    Created: <?php echo date('M j, Y g:i A', strtotime($document['created_at'])); ?> |
                    Last Updated: <?php echo date('M j, Y g:i A', strtotime($document['last_updated'])); ?>
                </div>
            </div>
            
        
            <!-- Messages -->
            <div class="card">
                <div class="card-header">
                    <div class="dot dot-secondary"></div>
                    <h2>All Messages</h2>
                </div>
                <div class="messages-container" id="messages-container">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            No messages found for this document.
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-item">
                                <div class="msg-header">
                                    <span class="msg-author"><?php echo htmlspecialchars($msg['user_name']); ?></span>
                                    <span class="msg-time"><?php echo date('M j, Y g:i A', strtotime($msg['sent_at'])); ?></span>
                                </div>
                                <div class="msg-content"><?php echo htmlspecialchars($msg['message']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div style="text-align: center;">
                <a href="admin_page.php" class="btn btn-secondary">← Back to Admin Dashboard</a>
            </div>
            
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages-container');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</body>
</html>