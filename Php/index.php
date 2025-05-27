<?php 
    session_start();
    require_once '../core/dbConfig.php'; 
    require_once '../core/models.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GDocs Clone</title>
    <link rel="stylesheet" href="../core/styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>GDocs Clone</h1>
    </div>

    <div class="form-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);  
                ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="form-box">
            <h4>Login to Your Account</h4>
            <form action="../core/handleForms.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <input type="submit" name="loginBtn" value="Login">
                </div>
                <div style="text-align: center; margin-top: 16px; color: #6b7280;">
                    Don't have an account? <a href="registerpage.php" style="color: #22c55e;">Register here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>