<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// ئەگەر بەکارهێنەر پێشتر لۆگین بووبێت، ڕاستەوخۆ ئاڕاستەی لیستا نەخۆشان دەکرێت
if (isLoggedIn()) {
    header("Location: secretary/patients.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            $user_status = $user['status'] ?? 'active';

            if ($user_status === 'disabled' || $user_status === 'inactive') {
                $error = "ئەڤ هەژمارە هاتیا ڕاگرتن! پەیوەندی ب دکتۆری بکه.";
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['role']      = $user['role'] ?? 'doctor';

                header("Location: secretary/patients.php");
                exit();
            }
        } else {
            $error = "ناوی بەکارهێنەر یان وشەی بۆڕینی هەڵەیە!";
        }
    } else {
        $error = "تکایە هەموو خانەکان پڕ بکەرەوە.";
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چوونا ژوورێ - کلینیکا دکتۆری ددانان</title>
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts (Noto Sans Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0E6BA8;
            --primary-hover: #0a5282;
            --bg-gradient: linear-gradient(135deg, #eef5fc 0%, #f8fafc 100%);
            --card-shadow: 0 15px 35px rgba(14, 107, 168, 0.08), 0 5px 15px rgba(0, 0, 0, 0.04);
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 40px 35px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: transform 0.3s ease;
        }

        .brand-icon-wrapper {
            width: 70px;
            height: 70px;
            background: rgba(14, 107, 168, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .form-label {
            font-size: 0.88rem;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
            color: #a0aec0;
            font-size: 1.1rem;
            z-index: 4;
            transition: color 0.2s ease;
        }

        .form-control-custom {
            padding-right: 45px !important;
            padding-left: 15px;
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            background-color: #f8fafc;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(14, 107, 168, 0.12);
        }

        .form-control-custom:focus + .input-icon {
            color: var(--primary-color);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            height: 48px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(14, 107, 168, 0.22);
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(14, 107, 168, 0.32);
            color: #ffffff;
        }

        .btn-primary-custom:active {
            transform: translateY(0);
        }

        .alert-custom {
            border-radius: 12px;
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
            font-size: 0.88rem;
            padding: 12px 16px;
        }

        .clinic-footer {
            margin-top: 25px;
            font-size: 0.8rem;
            color: #a0aec0;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="brand-icon-wrapper">
            <i class="bi bi-hospital"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">سیستەمێ کلینیکی</h4>
        <p class="text-muted small">چوونا ژوورێ بۆ بەکارهێنەران</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>
                <?php echo $error; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label fw-semibold">ناوی بەکارهێنەر (Username)</label>
            <div class="input-group-custom">
                <input type="text" name="username" class="form-control form-control-custom" placeholder="ناوی بەکارهێنەر بنووسە" required dir="ltr">
                <i class="bi bi-person input-icon"></i>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">وشەی بۆڕینی (Password)</label>
            <div class="input-group-custom">
                <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" required dir="ltr">
                <i class="bi bi-lock input-icon"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> چوونا ژوورێ
        </button>
    </form>

    <div class="text-center clinic-footer">
        <small>سیستەمی بەڕێوەبردنی کلینیکی ددان</small>
    </div>
</div>

</body>
</html>