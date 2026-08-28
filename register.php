<?php
/**
 * KANDY CO. - Customer Registration Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: account.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$fullName || !$email || !$password || !$confirmPassword) {
        setFlash('danger', 'Please fill out all required fields.');
    } elseif ($password !== $confirmPassword) {
        setFlash('danger', 'Passwords do not match. Please try again.');
    } elseif (strlen($password) < 6) {
        setFlash('danger', 'Password must be at least 6 characters long.');
    } else {
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlash('danger', 'An account with this email address already exists.');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $insertStmt->execute([$fullName, $email, $hashedPassword, $phone]);
            $userId = $pdo->lastInsertId();

            loginUserSession([
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => 'customer'
            ]);

            setFlash('success', 'Account created successfully! Welcome to KANDY CO.');
            header('Location: account.php');
            exit();
        }
    }
}

$pageTitle = "Register | KANDY CO.";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
    <div class="container-narrow" style="max-width: 520px;">
        <div style="text-align: center; margin-bottom: 36px;">
            <span class="section-subtitle">JOIN KANDY CO.</span>
            <h1 class="section-title" style="margin-top: 8px;">CREATE AN ACCOUNT</h1>
        </div>

        <div style="background-color: var(--bg-alt); padding: 40px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label">FULL NAME *</label>
                    <input type="text" name="full_name" class="form-input" required placeholder="Alex Turner" value="<?= e($_POST['full_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">EMAIL ADDRESS *</label>
                    <input type="email" name="email" class="form-input" required placeholder="alex@example.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">PHONE NUMBER</label>
                    <input type="tel" name="phone" class="form-input" placeholder="+1 (555) 000-0000" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">PASSWORD *</label>
                        <input type="password" name="password" class="form-input" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CONFIRM PASSWORD *</label>
                        <input type="password" name="confirm_password" class="form-input" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary full-width" style="height: 50px; margin-top: 12px;">REGISTER ACCOUNT</button>
            </form>

            <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 0.85rem;">
                <span style="color: var(--text-muted);">ALREADY HAVE AN ACCOUNT?</span>
                <a href="login.php" style="font-weight: 700; text-decoration: underline; margin-left: 6px;">SIGN IN</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
