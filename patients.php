<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['secretary', 'doctor']);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $name  = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $age   = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $notes = sanitize($_POST['general_notes'] ?? '');

    if (!empty($name) && !empty($phone)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, phone, age, general_notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $age, $notes]);
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("INSERT INTO patients (name, phone, age, general_notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $age, $notes]);
        }
        $msg = "نەخۆش ب سەرکەفتیانە هاتە تۆمارکرن!";
    }
}

$search = sanitize($_GET['search'] ?? '');
if (!empty($search)) {
    try {
        $stmt = $pdo->prepare("SELECT id, COALESCE(full_name, name) as name, phone, age, general_notes FROM patients WHERE full_name LIKE ? OR name LIKE ? OR phone LIKE ? ORDER BY id DESC");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("SELECT id, name, phone, age, general_notes FROM patients WHERE name LIKE ? OR phone LIKE ? ORDER BY id DESC");
        $stmt->execute(["%$search%", "%$search%"]);
    }
} else {
    try {
        $stmt = $pdo->query("SELECT id, COALESCE(full_name, name) as name, phone, age, general_notes FROM patients ORDER BY id DESC LIMIT 50");
    } catch (PDOException $e) {
        $stmt = $pdo->query("SELECT id, name, phone, age, general_notes FROM patients ORDER BY id DESC LIMIT 50");
    }
}
$patients = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ ڕێکخستن و بڵندکرنا ئاستێ UI ی لاپەڕا نەخۆشان -->
<style>
    .patients-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .search-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }

    .input-search-group {
        position: relative;
    }

    .input-search-group .search-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        right: 15px;
        color: #94a3b8;
        font-size: 1.1rem;
        z-index: 4;
    }

    .form-control-search {
        padding-right: 45px !important;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        height: 48px;
        font-size: 0.93rem;
        transition: all 0.25s ease;
        background-color: #f8fafc;
    }

    .form-control-search:focus {
        background-color: #ffffff;
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.12);
    }

    .btn-search-custom {
        background-color: #0E6BA8;
        color: #ffffff;
        border: none;
        height: 48px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    .btn-search-custom:hover {
        background-color: #0a5282;
        color: #ffffff;
    }

    .btn-add-patient {
        background-color: #0E6BA8;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 20px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }

    .btn-add-patient:hover {
        background-color: #0a5282;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(14, 107, 168, 0.3);
    }

    .table-card {
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
        font-weight: 600;
        font-size: 0.88rem;
        padding: 16px 20px;
        white-space: nowrap;
    }

    .custom-table td {
        padding: 16px 20px;
        color: #1e293b;
        font-size: 0.93rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .patient-name-link {
        color: #0f172a;
        font-weight: 600;
        transition: color 0.2s ease;
    }

    .patient-name-link:hover {
        color: #0E6BA8;
    }

    .badge-id {
        background-color: #f1f5f9;
        color: #64748b;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 0.82rem;
    }

    .phone-badge {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        border: 1px solid #a7f3d0;
    }

    .age-badge {
        background-color: #f0f9ff;
        color: #0369a1;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.82rem;
    }

    .btn-view-profile {
        border: 1.5px solid #0E6BA8;
        color: #0E6BA8;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 14px;
        transition: all 0.2s ease;
        background-color: transparent;
    }

    .btn-view-profile:hover {
        background-color: #0E6BA8;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }

    .alert-custom-success {
        border-radius: 12px;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 0.92rem;
        padding: 14px 20px;
    }

    .modal-content-custom {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
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
        font-size: 0.92rem;
    }

    .form-control-custom:focus {
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.15);
    }
</style>

<!-- سەردێڕ و دوگمەی زێدەکرنا نەخۆشی نوو -->
<div class="patients-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 d-flex align-items-center text-dark">
            <i class="bi bi-people-fill text-primary me-2 fs-3"></i> تۆمارکرن و لێگەڕیانا نەخۆشان
        </h3>
        <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i> بڕێڤەبرنا لیستەیا نەخۆشان و دۆزینەڤەیا خێرا یا فۆرمێن پزیشکی
        </p>
    </div>
    <button class="btn btn-add-patient d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPatientModal">
        <i class="bi bi-person-plus-fill fs-5"></i>
        <span>زێدەکرنا نەخۆشێ نوو</span>
    </button>
</div>

<!-- پەیاما سەرکەوتنا زێدەکرنێ -->
<?php if ($msg): ?>
    <div class="alert alert-custom-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div><?php echo $msg; ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- فۆڕما لێگەڕیانێ -->
<div class="search-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-10">
            <div class="input-search-group">
                <input type="text" name="search" class="form-control form-control-search" placeholder="لێگەڕیان ب ناڤی یان ژمارا تەلەفۆنێ..." value="<?php echo $search; ?>">
                <i class="bi bi-search search-icon"></i>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-search-custom w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-search"></i>
                <span>لێگەڕیان</span>
            </button>
        </div>
    </form>
</div>

<!-- خشتەی نەخۆشەکان -->
<div class="table-card p-0 mb-4">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 70px;">#</th>
                    <th>ناڤێ نەخۆشی (کلیک بکە بۆ پڕۆفایلی)</th>
                    <th>ژمارا تەلەفۆنێ</th>
                    <th>ژیی</th>
                    <th>تێبینیێن گشتی</th>
                    <th class="text-end">کریاڕ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><span class="badge-id">#<?php echo $p['id']; ?></span></td>
                        <td>
                            <!-- لینکا ڕاستەوخۆ بۆ پڕۆفایلێ نەخۆشی -->
                            <a href="patient_profile.php?id=<?php echo $p['id']; ?>" class="text-decoration-none patient-name-link d-inline-flex align-items-center gap-2">
                                <i class="bi bi-person-circle fs-5 text-primary"></i> 
                                <span><?php echo sanitize($p['name'] ?? 'نەناسراو'); ?></span>
                            </a>
                        </td>
                        <td>
                            <span class="phone-badge">
                                <i class="bi bi-telephone me-1 opacity-75"></i>
                                <?php echo sanitize($p['phone'] ?? '-'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($p['age'])): ?>
                                <span class="age-badge"><?php echo $p['age']; ?> ساڵ</span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-secondary small text-truncate d-inline-block" style="max-width: 250px;" title="<?php echo sanitize($p['general_notes'] ?? ''); ?>">
                                <?php echo !empty($p['general_notes']) ? sanitize($p['general_notes']) : '<span class="text-muted opacity-50">بێ تێبینی</span>'; ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="patient_profile.php?id=<?php echo $p['id']; ?>" class="btn btn-view-profile">
                                <i class="bi bi-eye me-1"></i> تەماشاکرنا پڕۆفایلی
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x display-5 text-secondary opacity-50 d-block mb-2"></i>
                            <span class="fw-semibold">هیچ نەخۆشێک نەدۆزرایەوە.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Patient -->
<div class="modal fade" id="addPatientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
            <i class="bi bi-person-plus text-primary me-2"></i> تۆمارکرنا نەخۆشێ نوو
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body modal-body-custom">
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">ناڤێ سیانی</label>
            <input type="text" name="name" class="form-control form-control-custom" placeholder="ناوی سیانیی نەخۆش..." required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">ژمارا تەلەفۆنێ</label>
            <input type="text" name="phone" class="form-control form-control-custom" placeholder="0750XXXXXXX" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">ژیی</label>
            <input type="number" name="age" class="form-control form-control-custom" placeholder="نموونە: 25">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">تێبینیێن گشتی یێن تەندروستی</label>
            <textarea name="general_notes" class="form-control form-control-custom" rows="3" placeholder="تێبینی، نەخۆشییە درێژخایەنەکان یان حەساسیەت..."></textarea>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom">
        <button type="submit" name="add_patient" class="btn btn-add-patient w-100 py-2 fs-6">
            <i class="bi bi-check-circle me-1"></i> تۆمارکرن
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>