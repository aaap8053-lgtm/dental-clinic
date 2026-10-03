<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['admin', 'doctor']); // ڕێگری ل سکرتێر و بەکارهێنەرێن بێ دەسەڵات دکەت

$msg = '';
$error = '';

// ١. زێدەکرنا بەکارهێنەری نوو
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $full_name = sanitize($_POST['full_name'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = sanitize($_POST['role'] ?? 'secretary');

    if (!empty($full_name) && !empty($username) && !empty($password)) {
        if (strlen($password) < 6) {
            $error = "تکایە وشەیا بؤڕینی (Password) پێویستە لانیکەم ٦ پێکهاتە (کەرەکتەر) بێت.";
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);

            if ($checkStmt->rowCount() > 0) {
                $error = "ئەڤ ناڤێ بەکارهێنەری (Username) بەرێ هاتیە بەکارهینان!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->execute([$full_name, $username, $hashed_password, $role]);
                $msg = "بەکارهێنەری نوو ب سەرکەفتیانە زێدە بوو!";
            }
        }
    } else {
        $error = "تکایە هەموو خانەکان پڕ بکەرەوە.";
    }
}

// ٢. گوڕینا دۆخێ هەژمارێ (چالاکرن / ڕاگرتن) ب ڕێیا POST و CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $user_id = (int)$_POST['user_id'];
    
    if ($user_id === (int)$_SESSION['user_id']) {
        $error = "تۆ نەشێی هەژمارا خۆ ڕابگری!";
    } else {
        $stmtStatus = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmtStatus->execute([$user_id]);
        $uData = $stmtStatus->fetch();

        if ($uData) {
            $current_status = $uData['status'] ?? 'active';
            $new_status = ($current_status === 'disabled') ? 'active' : 'disabled';

            $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $user_id]);
            $msg = "دۆخێ هەژمارێ ب سەرکەفتیانە هاتە گوڕین!";
        }
    }
}

// ٣. گوڕینا پاسوۆردی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $target_id    = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'] ?? '';

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $error = "تکایە وشەیا بؤڕینی نوو پێویستە لانیکەم ٦ پێکهاتە (کەرەکتەر) بێت.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtPass->execute([$hashed_password, $target_id]);
            $msg = "وشەیا بؤڕینی ب سەرکەفتیانە هاتە گوڕین!";
        }
    } else {
        $error = "تکایە وشەیا بؤڕینی نوو بنڤێسه.";
    }
}

// ٤. ئینانا هەمی بەکارهێنەران
$stmtUsers = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmtUsers->fetchAll();

$csrf_token = generateCsrfToken();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ بەرزکردنەوەی ئاستی UIی بەڕێوەبردنی بەکارهێنەران -->
<style>
    .users-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .btn-add-user-custom {
        background-color: #0E6BA8;
        color: #ffffff;
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 22px;
        transition: all 0.25s ease;
        border: none;
    }

    .btn-add-user-custom:hover {
        background-color: #0b5586;
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(14, 107, 168, 0.3);
    }

    .users-table-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .custom-table thead {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .custom-table th {
        color: #475569;
        font-weight: 700;
        font-size: 0.88rem;
        padding: 16px 20px;
        white-space: nowrap;
    }

    .custom-table td {
        padding: 16px 20px;
        color: #1e293b;
        font-size: 0.92rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-role-admin {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        border: 1px solid #bae6fd;
    }

    .badge-role-secretary {
        background-color: #f0fdf4;
        color: #15803d;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        border: 1px solid #bbf7d0;
    }

    .badge-status-active {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        border: 1px solid #a7f3d0;
    }

    .badge-status-disabled {
        background-color: #fef2f2;
        color: #b91c1c;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        border: 1px solid #fecaca;
    }

    .badge-self {
        background-color: #f1f5f9;
        color: #64748b;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
    }

    .btn-action-pass {
        border: 1.5px solid #f59e0b;
        color: #b45309;
        background-color: #fffbe3;
        border-radius: 10px;
        font-weight: 600;
        padding: 6px 14px;
        font-size: 0.83rem;
        transition: all 0.2s ease;
    }

    .btn-action-pass:hover {
        background-color: #f59e0b;
        color: #ffffff;
    }

    .btn-action-toggle-active {
        border: 1.5px solid #ef4444;
        color: #dc2626;
        background-color: #fef2f2;
        border-radius: 10px;
        font-weight: 600;
        padding: 6px 14px;
        font-size: 0.83rem;
        transition: all 0.2s ease;
    }

    .btn-action-toggle-active:hover {
        background-color: #ef4444;
        color: #ffffff;
    }

    .btn-action-toggle-disabled {
        border: 1.5px solid #10b981;
        color: #059669;
        background-color: #ecfdf5;
        border-radius: 10px;
        font-weight: 600;
        padding: 6px 14px;
        font-size: 0.83rem;
        transition: all 0.2s ease;
    }

    .btn-action-toggle-disabled:hover {
        background-color: #10b981;
        color: #ffffff;
    }

    .custom-modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .custom-modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 18px 24px;
    }

    .form-control-custom, .form-select-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 0.92rem;
        transition: all 0.2s ease;
    }

    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.12);
    }
</style>

<div class="container-fluid py-4">
    <!-- بەشی سەرەوە / سەردێڕ -->
    <div class="users-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark d-flex align-items-center">
                <i class="bi bi-shield-lock-fill text-primary me-2 fs-3"></i> ڕێڤەبرنا بەکارهێنەران
            </h3>
            <p class="text-muted small mb-0">زێدەکرنا سکرتێر و دکتۆران، دەسەڵات و کۆنترۆڵکرنا هەژماران</p>
        </div>
        <button class="btn btn-add-user-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill fs-5"></i>
            <span>زێدەکرنا بەکارهێنەری نوو</span>
        </button>
    </div>

    <!-- پەیامێن ئاگەهدارکرنێ -->
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <strong><?php echo $msg; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <strong><?php echo $error; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- خشتەی بەکارهێنەران -->
    <div class="users-table-card">
        <div class="table-responsive">
            <table class="table custom-table table-hover align-middle mb-0 text-center">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-start">ناڤێ سیانی</th>
                        <th>ناڤێ بەکارهێنەری</th>
                        <th>دەسەڵات (Role)</th>
                        <th>دۆخ (Status)</th>
                        <th>دوماهیک چوونا ژوورێ</th>
                        <th>کریار</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $u): 
                        $user_status = $u['status'] ?? 'active';
                    ?>
                    <tr>
                        <td class="text-muted fw-semibold"><?php echo $index + 1; ?></td>
                        <td class="text-start">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 38px; height: 38px; border: 1px solid #e2e8f0;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="fw-bold text-dark"><?php echo sanitize($u['full_name'] ?? $u['username']); ?></span>
                            </div>
                        </td>
                        <td>
                            <code class="bg-light text-primary px-2 py-1 rounded fw-bold border"><?php echo sanitize($u['username']); ?></code>
                        </td>
                        <td>
                            <?php if (($u['role'] ?? '') === 'doctor' || ($u['role'] ?? '') === 'admin'): ?>
                                <span class="badge-role-admin"><i class="bi bi-stethoscope me-1"></i> دکتۆر / ئەدمین</span>
                            <?php else: ?>
                                <span class="badge-role-secretary"><i class="bi bi-person-workspace me-1"></i> سکرتێر</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user_status === 'disabled'): ?>
                                <span class="badge-status-disabled"><i class="bi bi-x-circle me-1"></i> ڕاگرتی</span>
                            <?php else: ?>
                                <span class="badge-status-active"><i class="bi bi-check-circle me-1"></i> چالاک</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php echo !empty($u['last_login']) ? '<i class="bi bi-clock-history me-1"></i>' . date('Y-m-d h:i A', strtotime($u['last_login'])) : 'نەچوویە ژوورێ'; ?>
                        </td>
                        <td>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="badge-self"><i class="bi bi-shield-check me-1"></i> هەژمارا تە یە</span>
                            <?php else: ?>
                                <div class="d-inline-flex gap-2">
                                    <!-- گوڕینا پاسوۆردی -->
                                    <button class="btn btn-action-pass d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#passModal<?php echo $u['id']; ?>">
                                        <i class="bi bi-key-fill"></i>
                                        <span>گوڕینا پاسوۆردی</span>
                                    </button>

                                    <!-- چالاکرن / ڕاگرتن ب ڕێیا Form بۆ پاراستنا CSRF -->
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('تۆ پشتڕاستی ژ گوڕینا دۆخێ ڤێ هەژمارێ؟');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <?php if ($user_status === 'disabled'): ?>
                                            <button type="submit" class="btn btn-action-toggle-disabled d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>چالاکرن</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-action-toggle-active d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-slash-circle"></i>
                                                <span>ڕاگرتن</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals گۆڕینا پاسوۆردی -->
<?php foreach ($users as $u): ?>
    <?php if ($u['id'] != $_SESSION['user_id']): ?>
        <div class="modal fade" id="passModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content custom-modal-content">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <div class="modal-header custom-modal-header">
                        <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock text-warning fs-5"></i>
                            <span>گوڕینا پاسوۆردێ: <strong><?php echo sanitize($u['username']); ?></strong></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">وشەیا بؤڕینی (پاسوۆردێ نوو)</label>
                            <input type="password" name="new_password" class="form-control form-control-custom" placeholder="••••••••" required dir="ltr">
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-light fw-bold rounded-3 border" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                        <button type="submit" name="change_password" class="btn btn-warning fw-bold rounded-3 px-4">پاشکەوتکرن</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Modal زێدەکرنا بەکارهێنەری نوو -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content custom-modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="modal-header custom-modal-header">
                <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill text-primary fs-5"></i>
                    <span>زێدەکرنا بەکارهێنەری نوو</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">ناڤێ سیانی</label>
                    <input type="text" name="full_name" class="form-control form-control-custom" placeholder="مۆدێل: ئەحمەد عەلی شەریف" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">ناڤێ بەکارهێنەری (Username)</label>
                    <input type="text" name="username" class="form-control form-control-custom" placeholder="ahmed_dev" required dir="ltr">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">وشەیا بؤڕینی (Password)</label>
                    <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" required dir="ltr">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small">دەسەڵات (Role)</label>
                    <select name="role" class="form-select form-select-custom">
                        <option value="secretary">سکرتێر (Secretary)</option>
                        <option value="doctor">دکتۆر (Doctor)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3">
                <button type="button" class="btn btn-light fw-bold rounded-3 border" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                <button type="submit" name="add_user" class="btn btn-primary fw-bold rounded-3 px-4" style="background-color: #0E6BA8; border: none;">تۆمارکرن</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>