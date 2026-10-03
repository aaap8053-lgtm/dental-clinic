<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireLogin();

$patient_id = (int)($_GET['id'] ?? 0);

if ($patient_id <= 0) {
    header("Location: patients.php");
    exit();
}

// ئینانا زانیاریێن نەخۆشی
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    header("Location: patients.php");
    exit();
}

$msg = '';
$error = '';

// زێدەکرنا جەلسا نوو
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_session'])) {
    if (function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    if (($_SESSION['role'] ?? '') !== 'doctor' && ($_SESSION['role'] ?? '') !== 'admin') {
        $error = "تەنێ دکتۆر دشێت جەلسەیێن چارەسەریێ زێدە بکەت!";
    } else {
        $session_number     = sanitize($_POST['session_number'] ?? '');
        $treatment_done     = sanitize($_POST['treatment_done'] ?? '');
        $notes              = sanitize($_POST['notes'] ?? '');
        $remaining_sessions = (int)($_POST['remaining_sessions'] ?? 0);
        $cost               = (float)($_POST['cost'] ?? 0.00);

        if (!empty($session_number) && !empty($treatment_done)) {
            $stmtAdd = $pdo->prepare("
                INSERT INTO patient_sessions (patient_id, doctor_id, session_number, treatment_done, notes, remaining_sessions, cost) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAdd->execute([$patient_id, $_SESSION['user_id'] ?? 1, $session_number, $treatment_done, $notes, $remaining_sessions, $cost]);
            $msg = "جەلسا چارەسەرکرنێ ب سەرکەفتیانە تۆمار بوو!";
        } else {
            $error = "هیڤییە ناڤێ جەلسێ و چارەسەریا هاتیە کرن بنڤێسه.";
        }
    }
}

// ئینانا هەمی جەلسەیێن ڤی نەخۆشی
$stmtSessions = $pdo->prepare("
    SELECT ps.*, u.full_name as doctor_name 
    FROM patient_sessions ps
    LEFT JOIN users u ON ps.doctor_id = u.id
    WHERE ps.patient_id = ?
    ORDER BY ps.id DESC
");
$stmtSessions->execute([$patient_id]);
$sessions = $stmtSessions->fetchAll();

$gender_text = 'دیار نینە';
if (isset($patient['gender'])) {
    $gender_text = ($patient['gender'] === 'male' || $patient['gender'] === 'نێر') ? 'نێر' : 'مێ';
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ بەرزکردنەوەی ئاستی UI ی پڕۆفایلی نەخۆش -->
<style>
    .profile-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .btn-back-custom {
        border: 1.5px solid #cbd5e1;
        color: #475569;
        border-radius: 12px;
        font-weight: 600;
        padding: 8px 18px;
        transition: all 0.25s ease;
        background-color: #ffffff;
    }

    .btn-back-custom:hover {
        background-color: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .patient-info-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
    }

    .info-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        height: 100%;
        transition: all 0.2s ease;
    }

    .info-box:hover {
        border-color: #cbd5e1;
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }

    .info-box .info-label {
        font-size: 0.80rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .info-box .info-value {
        font-size: 1rem;
        color: #0f172a;
        font-weight: 700;
        margin: 0;
    }

    .session-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
    }

    .btn-add-session {
        background-color: #0E6BA8;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        padding: 8px 18px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }

    .btn-add-session:hover {
        background-color: #0a5282;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(14, 107, 168, 0.3);
    }

    .btn-print-report {
        border: 1.5px solid #334155;
        color: #334155;
        border-radius: 12px;
        font-weight: 600;
        padding: 8px 18px;
        transition: all 0.25s ease;
        background-color: transparent;
    }

    .btn-print-report:hover {
        background-color: #334155;
        color: #ffffff;
    }

    .custom-table thead {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .custom-table th {
        color: #475569;
        font-weight: 600;
        font-size: 0.70rem;
        padding: 16px 20px;
        white-space: nowrap;
        
        text-align: center;
    }

    .custom-table td {
        padding: 16px 20px;
        color: #1e293b;
        font-size: 0.70rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-session-num {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.70rem;
        border: 1px solid #bae6fd;
    }

    .badge-rem-sessions {
        background-color: #fef3c7;
        color: #b45309;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.70rem;
        border: 1px solid #fde68a;
    }

    .badge-completed {
        background-color: #dcfce7;
        color: #15803d;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.70rem;
        border: 1px solid #bbf7d0;
    }

    .badge-cost {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.70rem;
        border: 1px solid #a7f3d0;
    }

    .badge-doctor {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.70rem;
    }

    .alert-custom-success {
        border-radius: 12px;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 0.92rem;
        padding: 14px 20px;
    }

    .alert-custom-danger {
        border-radius: 12px;
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 0.92rem;
        padding: 14px 20px;
    }

    .modal-content-custom {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .modal-header-custom {
        border-bottom: 1px solid #f1f5f9;
        padding: 20px 24px;
    }

    .modal-body-custom {
        padding: 24px;
    }

    .modal-footer-custom {
        border-top: 1px solid #f1f5f9;
        padding: 16px 24px;
    }

    .form-control-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 0.80rem;
    }

    .form-control-custom:focus {
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.15);
    }
</style>

<div class="container-fluid py-4">
    <!-- سەردێڕ و دوگمەی زڤڕین -->
    <div class="profile-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center text-dark">
                <i class="bi bi-person-badge-fill text-primary me-2 fs-3"></i> پڕۆفایلێ نەخۆشی
            </h3>
            <p class="text-muted small mb-0">
                کۆدێ نەخۆشی: <code class="bg-light text-primary px-2 py-1 rounded fw-bold"><?php echo sanitize($patient['patient_code'] ?? $patient['id']); ?></code>
            </p>
        </div>
        <a href="patients.php" class="btn btn-back-custom d-flex align-items-center gap-2">
            <i class="bi bi-arrow-right"></i>
            <span>زڤڕین بۆ لیستا نەخۆشان</span>
        </a>
        
    </div>

    <!-- پەیامێن ئاگاداری و سەرکەوتنان -->
    <?php if ($msg): ?>
        <div class="alert alert-custom-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
            <div><?php echo $msg; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-custom-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
            <div><?php echo $error; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- زانیاریێن کەسۆکی -->
    <div class="patient-info-card mb-4">
        <h5 class="fw-bold text-dark mb-3 d-flex align-items-center">
            <i class="bi bi-person-lines-fill text-primary me-2"></i> زانیاریێن کەسۆکی
        </h5>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-person text-primary"></i> ناڤێ نەخۆشی</div>
                    <div class="info-value"><?php echo sanitize($patient['full_name'] ?? $patient['name'] ?? '-'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-telephone text-success"></i> ژمارا تەلەفۆنێ</div>
                    <div class="info-value"><?php echo sanitize($patient['phone'] ?? '-'); ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-hourglass-split text-info"></i> عەمر</div>
                    <div class="info-value"><?php echo isset($patient['age']) ? sanitize($patient['age']) . ' ساڵ' : '-'; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-gender-ambiguous text-warning"></i> ڕەگەز</div>
                    <div class="info-value"><?php echo $gender_text; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <div class="info-label"><i class="bi bi-calendar-check text-secondary"></i> تۆمارکرن</div>
                    <div class="info-value" style="font-size: 0.9rem;"><?php echo !empty($patient['created_at']) ? date('Y-m-d', strtotime($patient['created_at'])) : '-'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- مێژووا جەلسەیان -->
    <div class="session-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h5 class="fw-bold text-primary mb-0 d-flex align-items-center">
                <i class="bi bi-journal-medical me-2 fs-4"></i> مێژووا جەلسەیێن چارەسەریێ
            </h5>
            <div class="d-flex align-items-center gap-2">
                <a href="../print_treatment_report.php?id=<?php echo $patient_id; ?>" target="_blank" class="btn btn-print-report d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-6"></i>
                    <span>پرێنتکرنا ڕاپۆرتێ</span>
                </a>
                <?php if (($_SESSION['role'] ?? '') === 'doctor' || ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <button class="btn btn-add-session d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                        <i class="bi bi-plus-circle-fill fs-6"></i>
                        <span>زێدەکرنا جەلسا نوو</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($sessions) > 0): ?>
            <div class="table-responsive rounded-3 border">
                <table class="table custom-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>جەلسە</th>
                            <th>چارەسەریا هاتیە کرن</th>
                            <th>تێبینیێن دکتۆری</th>
                            <th>جەلسەیێن مایێن</th>
                            <th>پارێ جەلسێ</th>
                            <th>دکتۆر</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td>
                                <span class="badge-session-num">
                                    <?php echo sanitize($s['session_number'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark">
                                <?php echo nl2br(sanitize($s['treatment_done'] ?? '')); ?>
                            </td>
                            <td>
                                <span class="small text-secondary">
                                    <?php echo !empty($s['notes']) ? nl2br(sanitize($s['notes'])) : '-'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($s['remaining_sessions'] ?? 0) > 0): ?>
                                    <span class="badge-rem-sessions">
                                        <i class="bi bi-clock-history me-1"></i>
                                        <?php echo $s['remaining_sessions']; ?> جەلسێن دی ماینە
                                    </span>
                                <?php else: ?>
                                    <span class="badge-completed">
                                        <i class="bi bi-check2-circle me-1"></i>
                                        تەواو بوو
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-cost">
                                    $<?php echo number_format((float)($s['cost'] ?? 0), 2); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-doctor">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <?php echo sanitize($s['doctor_name'] ?? 'دکتۆر'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    <?php echo !empty($s['created_at']) ? date('Y-m-d h:i A', strtotime($s['created_at'])) : '-'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 my-2 rounded-3 bg-light border border-dashed">
                <i class="bi bi-journal-x display-5 text-secondary opacity-50 d-block mb-2"></i>
                <span class="fw-semibold text-muted">هیچ جەلسەیەک تۆمار نەکراوە.</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add Session -->
<?php if (($_SESSION['role'] ?? '') === 'doctor' || ($_SESSION['role'] ?? '') === 'admin'): ?>
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" class="modal-content modal-content-custom">
      <?php if (function_exists('generateCsrfToken')): ?>
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <?php endif; ?>
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
            <i class="bi bi-file-earmark-plus text-primary me-2 fs-4"></i> تۆمارکرنا جەلسا نوو
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body modal-body-custom">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">ناڤێ جەلسێ</label>
                <input type="text" name="session_number" class="form-control form-control-custom" placeholder="م.گ: جەلسا 1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">جەلسەیێن مایێن</label>
                <input type="number" name="remaining_sessions" class="form-control form-control-custom" value="0" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">پارێ جەلسێ ($)</label>
                <input type="number" step="0.01" name="cost" class="form-control form-control-custom text-success fw-bold" value="0.00">
            </div>
            <div class="col-md-12">
                <label class="form-label fw-semibold text-secondary">چارەسەریا هاتیە کرن</label>
                <textarea name="treatment_done" class="form-control form-control-custom" rows="3" placeholder="ڕوونکردنەوەی چارەسەر..." required></textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-semibold text-secondary">تێبینیێن دکتۆری</label>
                <textarea name="notes" class="form-control form-control-custom" rows="2" placeholder="تێبینییە تایبەتەکان..."></textarea>
            </div>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom">
        <button type="submit" name="add_session" class="btn btn-add-session w-100 py-2 fs-6">
            <i class="bi bi-check-circle me-1"></i> تۆمارکرن
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>