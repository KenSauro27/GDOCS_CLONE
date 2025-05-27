<?php
session_start();
require_once '../core/models.php';
require_once '../core/dbConfig.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$users = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, email, suspended FROM users WHERE role = :role");
    $stmt->execute(['role' => 'User']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading users: " . $e->getMessage());
    $error = "Error loading users";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #dc2626;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>GDocs Clone - User Management</h1>
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
                    <a href="admin_page.php">
                        <div class="dot dot-secondary"></div>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_usermanagement.php" class="active">
                        <div class="dot dot-primary"></div>
                        <span>User Management</span>
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="dot dot-primary"></div>
                        <h2>User Management</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-container">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr id="user-row-<?php echo htmlspecialchars($user['id']); ?>">
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span style="color: <?php echo $user['suspended'] ? '#dc2626' : '#22c55e'; ?>">
                                                <?php echo $user['suspended'] ? 'Suspended' : 'Active'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <input type="checkbox" 
                                                       id="suspend-<?php echo htmlspecialchars($user['id']); ?>" 
                                                       <?php echo $user['suspended'] ? 'checked' : ''; ?>
                                                       onchange="toggleSuspension(<?php echo htmlspecialchars($user['id']); ?>, this.checked)">
                                                <label for="suspend-<?php echo htmlspecialchars($user['id']); ?>" style="font-size: 12px; color: #6b7280;">
                                                    Suspend
                                                </label>
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

    <script>
        function toggleSuspension(userId, suspend) {
            const row = $(`#user-row-${userId}`);
            const checkbox = $(`#suspend-${userId}`);
            
            row.addClass('loading');
            checkbox.prop('disabled', true);
            
            $.ajax({
                url: 'togglesuspension.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    suspend: suspend ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        const statusCell = $(`#user-row-${userId} td:nth-child(4) span`);
                        statusCell.text(response.suspended ? 'Suspended' : 'Active');
                        statusCell.css('color', response.suspended ? '#dc2626' : '#22c55e');
                        
                        checkbox.prop('checked', response.suspended);
                    } else {
                        alert(response.message);

                        checkbox.prop('checked', !suspend);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error communicating with server. Please try again.');
                    console.error(error);
                    checkbox.prop('checked', !suspend);
                },
                complete: function() {
                    row.removeClass('loading');
                    checkbox.prop('disabled', false);
                }
            });
        }
    </script>
</body>
</html>