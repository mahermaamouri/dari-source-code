<?php
// admin/login.php
require_once __DIR__ . '/../config/init.php';

// If admin is already logged in, redirect to dashboard.
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_PATH . 'admin/dashboard.php');
    exit();
}

$error_message = '';
$login_attempted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_attempted = true;
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_message = 'Veuillez saisir votre identifiant et mot de passe.';
    } else {
        global $pdo; 
        try {
            // CORRECTED: Table name is capitalized
            $sql = "SELECT admin_id, username, password_hash, full_name FROM Admins WHERE username = :username_identifier OR email = :email_identifier";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':username_identifier' => $username_or_email,
                ':email_identifier' => $username_or_email
            ]); 

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Password is correct, login successful.
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['last_activity'] = time();

                // Update last_login_at timestamp
                // CORRECTED: Table name is capitalized
                $update_sql = "UPDATE Admins SET last_login_at = CURRENT_TIMESTAMP WHERE admin_id = :admin_id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([':admin_id' => $admin['admin_id']]);

                // Redirect to the intended page or dashboard.
                $redirect_url = $_SESSION['redirect_url'] ?? BASE_PATH . 'admin/dashboard.php';
                unset($_SESSION['redirect_url']); 
                header('Location: ' . $redirect_url);
                exit();
            } else {
                $error_message = 'Identifiant ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error_message = 'Erreur de base de données. Veuillez réessayer.';
            if (DEVELOPMENT_MODE) {
                error_log("Login PDOException: " . $e->getMessage());
            }
        }
    }
}

$page_title = "Connexion Admin";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Dari</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 0.75rem;
        }
        .login-header {
            background-color: var(--color-primary, #2A6478);
            color: white;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card shadow-lg login-card">
            <div class="card-header login-header text-center py-4">
                <img src="<?php echo ASSETS_PATH; ?>/images/logo.png" alt="Dari Logo" class="mb-2" style="width: 60px; height: 60px; border-radius: 50%;">
                <h2 class="h4 mb-0 mt-2">Dari Admin</h2>
                <p class="mb-0 small">Panneau d'administration</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <h3 class="card-title text-center mb-4 h5">Connexion</h3>

                <?php if ($login_attempted && !empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] === 'session_expired'): ?>
                    <div class="alert alert-warning" role="alert">
                        Votre session a expiré. Veuillez vous reconnecter.
                    </div>
                <?php endif; ?>
                 <?php if (isset($_GET['status']) && $_GET['status'] === 'logged_out'): ?>
                    <div class="alert alert-success" role="alert">
                        Vous avez été déconnecté avec succès.
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="username_or_email" class="form-label">Identifiant ou Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                            <input type="text" class="form-control" id="username_or_email" name="username_or_email" required autofocus
                                   value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                             <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ph ph-sign-in me-2"></i>Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>