<?php 

session_start();
require_once '../core/dbConfig.php'; 
require_once '../core/models.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header("Location: index.php");
    exit;
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

$document = getDocumentById($pdo, $documentId);
if (!$document) {
    header("Location: user_home.php");
    exit;
}

$canEdit = userHasEditAccess($pdo, $documentId, $userId);
$canView = userHasViewAccess($pdo, $documentId, $userId);

if (!$canEdit && !$canView) {
    header("Location: user_home.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?php echo htmlspecialchars($document['title']); ?> - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .editor-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .editor-toolbar {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .editor-toolbar button {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .editor-toolbar button:hover {
            background-color: #f3f4f6;
            border-color: #22c55e;
        }
        
        .editor-toolbar select {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 14px;
        }
        
        #editor {
            min-height: 500px;
            padding: 20px;
            font-family: "Arial", sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
            outline: none;
        }
        
        #editor:focus {
            background-color: #fefefe;
        }
        
        .editor-status {
            background-color: #f0fdf4;
            border-top: 1px solid #e5e7eb;
            padding: 8px 16px;
            font-size: 14px;
            color: #166534;
        }
        
        .access-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
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
                <a href="user_home.php">← Back to Documents</a>
                <a href="doc_logs.php?doc_id=<?php echo $documentId; ?>">View Activity Log</a>
                <a href="doc_messages.php?doc_id=<?php echo $documentId; ?>">View Messages</a>
                <?php if (isDocumentOwner($pdo, $documentId, $userId)): ?>
                    <a href="shareDocument.php?id=<?php echo $documentId; ?>">Share Document</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$canEdit): ?>
            <div class="access-notice">
                <strong>Read-Only Access:</strong> You have view-only permission for this document. You cannot make edits.
            </div>
        <?php endif; ?>

        <div class="editor-container">
            <?php if ($canEdit): ?>
            <div class="editor-toolbar">
                <!-- Text formatting -->
                <button onclick="formatText('bold')" title="Bold"><strong>B</strong></button>
                <button onclick="formatText('italic')" title="Italic"><em>I</em></button>
                <button onclick="formatText('underline')" title="Underline"><u>U</u></button>
                
                <div style="border-left: 1px solid #d1d5db; height: 24px; margin: 0 4px;"></div>
                
                <!-- Headings -->
                <select id="formatBlock" onchange="formatText('formatBlock', this.value)">
                    <option value="div">Normal Text</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                </select>
                
                <div style="border-left: 1px solid #d1d5db; height: 24px; margin: 0 4px;"></div>
                
                <!-- Alignment -->
                <button onclick="formatText('justifyLeft')" title="Align Left">◄</button>
                <button onclick="formatText('justifyCenter')" title="Align Center">■</button>
                <button onclick="formatText('justifyRight')" title="Align Right">►</button>
                
                <div style="border-left: 1px solid #d1d5db; height: 24px; margin: 0 4px;"></div>
                
                <!-- Lists -->
                <button onclick="formatText('insertOrderedList')" title="Numbered List">1.</button>
                <button onclick="formatText('insertUnorderedList')" title="Bullet List">•</button>
            </div>
            <?php endif; ?>
            
            <div id="editor" 
                 contenteditable="<?php echo $canEdit ? 'true' : 'false'; ?>" 
                 data-document-id="<?php echo $documentId; ?>">
                <?php echo $document['content'] ?? '<p>Start typing your document...</p>'; ?>
            </div>
            
            <div class="editor-status" id="status">
                <?php if ($canEdit): ?>
                    Ready to edit • Changes save automatically
                <?php else: ?>
                    Viewing document • Read-only access
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Info -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <div class="dot dot-secondary"></div>
                <h2>Document Details</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; color: #6b7280; font-size: 14px;">
                <div>
                    <strong style="color: #374151;">Owner:</strong> <?= htmlspecialchars($document['owner_name']) ?><br>
                    <strong style="color: #374151;">Document ID:</strong> <?= $document['id'] ?>
                </div>
                <div>
                    <strong style="color: #374151;">Last Updated:</strong><br>
                    <?= date('M j, Y g:i A', strtotime($document['last_updated'])) ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../core/script.js"></script>
    <script>
        function formatText(command, value) {
            if (command === 'formatBlock') {
                document.execCommand('formatBlock', false, value);
            } else {
                document.execCommand(command, false, value);
            }
            document.getElementById('editor').focus();
        }
    </script>
</body>
</html>