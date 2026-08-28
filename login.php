<?php
/**
 * KANDY CO. - Customer Login Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: account.php');
    exit();
}

$redirect = $_GET['redirect'] ?? 'account.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        setFlash('danger', 'Please enter your email and password.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUserSession($user);
            setFlash('success', 'Welcome back, ' . e($user['full_name']) . '!');
            header("Location: {$redirect}");
            exit();
        } else {
            setFlash('danger', 'Invalid email address or password.');
        }
    }
}

$pageTitle = "Login | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container-narrow" style="max-width: 480px;">
        <div style="text-align: center; margin-bottom: 36px;">
            <span class="section-subtitle">WELCOME BACK</span>
            <h1 class="section-title" style="margin-top: 8px;">CUSTOMER LOGIN</h1>
        </div>

        <div style="background-color: var(--bg-alt); padding: 40px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <form action="login.php?redirect=<?= urlencode($redirect) ?>" method="POST">
                <div class="form-group">
                    <label class="form-label">EMAIL ADDRESS *</label>
                    <input type="email" name="email" class="form-input" required placeholder="name@example.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">PASSWORD *</label>
                    <input type="password" name="password" class="form-input" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>

                <button type="submit" class="btn btn-primary full-width" style="height: 50px; margin-top: 12px;">SIGN IN</button>
            </form>

            <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 0.85rem;">
                <span style="color: var(--text-muted);">DON'T HAVE AN ACCOUNT?</span>
                <a href="register.php" style="font-weight: 700; text-decoration: underline; margin-left: 6px;">CREATE ACCOUNT</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
