<?php 

require_once '../core/dbConfig.php'; 
require_once '../core/models.php'; 
require_once '../core/handleforms.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header("Location: index.php");
    exit;
}

$error = handleCreateDocumentForm($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Document - GDocs Clone</title>
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
        <div class="main-content">
            <!-- Sidebar -->
            <div class="sidebar">
                <nav>
                    <a href="createDocument.php" class="active">
                        <div class="dot dot-primary"></div>
                        <span>New Document</span>
                    </a>
                    <a href="user_home.php">
                        <div class="dot dot-secondary"></div>
                        <span>My Documents</span>
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-primary"></div>
                        <h2>Create New Document</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Document Title</label>
                            <input type="text" id="title" name="title" required placeholder="Enter document title">
                        </div>
                        
                        <div class="form-group" style="display: flex; gap: 12px; margin-top: 20px;">
                            <input type="submit" name="createDoc" value="Create Document" class="btn btn-primary">
                            <a href="user_home.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>