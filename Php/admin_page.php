<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

require_once '../core/dbConfig.php';
require_once '../core/models.php';


$documents = getAllDocuments($pdo);
$users = getAllUsersAdmin($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>GDocs Clone - Admin Dashboard</h1>
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
                    <a href="#" class="active">
                        <div class="dot dot-primary"></div>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_usermanagement.php">
                        <div class="dot dot-secondary"></div>
                        <span>User Management</span>
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="content">
                <!-- Users Overview -->
                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-primary"></div>
                        <h2>Users Overview</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Documents</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td><?php echo $user['document_count']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $user['suspended'] ? '#dc2626' : '#22c55e'; ?>">
                                            <?php echo $user['suspended'] ? 'Suspended' : 'Active'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents Overview -->
                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-secondary"></div>
                        <h2>All Documents</h2>
                    </div>
                    <p style="margin-bottom: 16px; color: #6b7280;">Total Documents: <strong style="color: #22c55e;"><?php echo count($documents); ?></strong></p>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Owner</th>
                                    <th>Owner Email</th>
                                    <th>Shared With</th>
                                    <th>Created</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo $doc['id']; ?></td>
                                    <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['owner_email']); ?></td>
                                    <td><?php echo $doc['shared_count']; ?> users</td>
                                    <td><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($doc['last_updated'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary" style="font-size: 12px; padding: 4px 8px;">View</a>
                                            <a href="doc_logs.php?doc_id=<?php echo $doc['id']; ?>" class="btn btn-secondary" style="font-size: 12px; padding: 4px 8px;">Logs</a>
                                            <a href="admin_viewmessages.php?doc_id=<?php echo $doc['id']; ?>" class="btn btn-secondary" style="font-size: 12px; padding: 4px 8px;">Messages</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>