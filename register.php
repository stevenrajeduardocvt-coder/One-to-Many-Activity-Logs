<?php
/**
 * register.php — New user registration page.
 * Validates username uniqueness and stores hashed password.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

// If already logged in, redirect to home
if (isset($_SESSION['username'])) {
    header("Location: /student_grades/index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getConnection();

        // Check for duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username \"$username\" is already taken. Please choose another.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->bind_param("ss", $username, $hash);
            $ins->execute();
            $ins->close();
            $success = 'Account created! You can now log in.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | GradeTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#0d0f14;--surface:#151821;--surface2:#1e2230;--border:#2a2f3f;
            --accent:#5b8af5;--accent2:#f5a623;--danger:#e05a5a;--success:#4caf82;
            --text:#e8eaf0;--text-muted:#7a8099;
            --font-head:'Syne',sans-serif;--font-body:'DM Sans',sans-serif;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--bg);color:var(--text);font-family:var(--font-body);
             min-height:100vh;display:flex;align-items:center;justify-content:center;
             background-image: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(91,138,245,.12) 0%, transparent 70%);}
        .auth-box{background:var(--surface);border:1px solid var(--border);border-radius:14px;
                  padding:2.5rem;width:100%;max-width:420px;}
        .brand{font-family:var(--font-head);font-size:1.6rem;font-weight:800;
               color:var(--accent);text-align:center;margin-bottom:.25rem;letter-spacing:-.03em;}
        .brand span{color:var(--accent2);}
        .subtitle{text-align:center;color:var(--text-muted);font-size:.875rem;margin-bottom:2rem;}
        h2{font-family:var(--font-head);font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;}
        .form-group{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
        label{font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;}
        input{background:var(--surface2);border:1px solid var(--border);color:var(--text);
              padding:.65rem .9rem;border-radius:7px;font-family:var(--font-body);font-size:.9rem;
              transition:border-color .15s;width:100%;}
        input:focus{outline:none;border-color:var(--accent);}
        .btn{width:100%;padding:.7rem;border:none;border-radius:7px;background:var(--accent);
             color:#fff;font-family:var(--font-head);font-size:1rem;font-weight:700;
             cursor:pointer;transition:opacity .15s;margin-top:.5rem;}
        .btn:hover{opacity:.85}
        .alert{padding:.7rem 1rem;border-radius:7px;margin-bottom:1rem;font-size:.85rem;}
        .alert-danger{background:rgba(224,90,90,.12);border:1px solid var(--danger);color:var(--danger);}
        .alert-success{background:rgba(76,175,130,.12);border:1px solid var(--success);color:var(--success);}
        .switch{text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--text-muted);}
        .switch a{color:var(--accent);text-decoration:none;font-weight:600;}
        .switch a:hover{text-decoration:underline;}
    </style>
</head>
<body>
<div class="auth-box">
    <div class="brand">Grade<span>Track</span></div>
    <div class="subtitle">Student Grades Management System</div>
    <h2>Create Account</h2>

    <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. jdoe2024"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn">Create Account</button>
    </form>
    <div class="switch">Already have an account? <a href="/login.php">Log in</a></div>
</div>
</body>
</html>
