<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['doctor', 'admin']);

$patient_id = (int)($_GET['patient_id'] ?? 0);

if ($patient_id <= 0) {
    die("ناسنامەی نەخۆش نادروستە!");
}

// ئینانا زانیاریێن نەخۆشی
$stmtP = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmtP->execute([$patient_id]);
$patient = $stmtP->fetch();

if (!$patient) {
    die("نەخۆش نەهاتە دیتن!");
}

// ئینانا دەرمانێن خەزێنێ بۆ پێشنیارکرنێ
$medicines_list = $pdo->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $med_names    = $_POST['med_name'] ?? [];
    $dosages      = $_POST['dosage'] ?? [];
    $frequencies  = $_POST['frequency'] ?? [];
    $durations    = $_POST['duration'] ?? [];
    $instructions = $_POST['instructions'] ?? [];
    $notes        = sanitize($_POST['notes'] ?? '');

    // فلتەرکرنا دەرمانان - تەنێ ئەوانەی ناڤی وان هەبی
    $valid_items = [];
    for ($i = 0; $i < count($med_names); $i++) {
        $name = trim($med_names[$i] ?? '');
        if (!empty($name)) {
            $valid_items[] = [
                'name'         => sanitize($name),
                'dosage'       => sanitize($dosages[$i] ?? ''),
                'frequency'    => sanitize($frequencies[$i] ?? ''),
                'duration'     => sanitize($durations[$i] ?? ''),
                'instructions' => sanitize($instructions[$i] ?? '')
            ];
        }
    }

    if (!empty($valid_items)) {
        try {
            $pdo->beginTransaction();

            // 1. تۆمارکرنا سەرێ نۆسخەیێ
            $stmtRx = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, notes) VALUES (?, ?, ?)");
            $stmtRx->execute([$patient_id, $_SESSION['user_id'], $notes]);
            $prescription_id = $pdo->lastInsertId();

            // 2. تۆمارکرنا ئایتمێن دەرمانان
            $stmtItem = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, instructions) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($valid_items as $item) {
                $stmtItem->execute([
                    $prescription_id,
                    $item['name'],
                    $item['dosage'],
                    $item['frequency'],
                    $item['duration'],
                    $item['instructions']
                ]);
            }

            $pdo->commit();
            header("Location: ../secretary/patient_profile.php?id=" . $patient_id . "&msg=rx_success");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Prescription Insert Error: " . $e->getMessage());
            $error = "هەڵەیەک ڕوویدا د تۆمارکرنا نۆسخەیێ دا. تکایە دووبارە هەوڵبدەرەوە.";
        }
    } else {
        $error = "هیڤییە لانیکەم دەرمانەکێ د نۆسخەیێ دا بنڤێسه!";
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ بەرزکردنەوەی ئاستی UIی لاپەڕەی نڤێسینا نۆسخەیێ -->
<style>
    .rx-header-card {
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

    .rx-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
    }

    .medicine-item {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0;
        border-radius: 14px !important;
        padding: 16px !important;
        transition: all 0.2s ease;
    }

    .medicine-item:hover {
        border-color: #cbd5e1;
        background-color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }

    .form-label-custom {
        font-size: 0.83rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .form-control-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 9px 12px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    .form-control-custom:focus {
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.12);
    }

    .btn-add-med {
        border: 1.5px dashed #0E6BA8;
        color: #0E6BA8;
        background-color: #f0f9ff;
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 20px;
        transition: all 0.25s ease;
    }

    .btn-add-med:hover {
        background-color: #0E6BA8;
        color: #ffffff;
        border-style: solid;
    }

    .btn-submit-rx {
        background-color: #10b981;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        padding: 12px 28px;
        font-size: 1rem;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
    }

    .btn-submit-rx:hover {
        background-color: #059669;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
    }

    .btn-remove-custom {
        border-radius: 10px;
        padding: 8px 12px;
        transition: all 0.2s ease;
    }

    .alert-custom-danger {
        border-radius: 12px;
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 0.92rem;
        padding: 14px 20px;
    }
</style>

<div class="container-fluid py-4">
    <!-- سەردێڕ و زانیاریێن نەخۆشی -->
    <div class="rx-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center text-dark">
                <i class="bi bi-file-earmark-medical-fill text-primary me-2 fs-3"></i> نڤێسینا نۆسخەیا دەرمانان
            </h3>
            <p class="text-muted small mb-0 d-flex align-items-center gap-2 mt-1">
                <span>نەخۆش: <strong class="text-dark"><?php echo sanitize($patient['full_name'] ?? ''); ?></strong></span>
                <span class="text-secondary">•</span>
                <span>کۆد: <code class="bg-light text-primary px-2 py-1 rounded fw-bold"><?php echo sanitize($patient['patient_code'] ?? ''); ?></code></span>
            </p>
        </div>
        <a href="../secretary/patient_profile.php?id=<?php echo $patient_id; ?>" class="btn btn-back-custom d-flex align-items-center gap-2">
            <i class="bi bi-arrow-right"></i>
            <span>زڤڕین بۆ پڕۆفایلی</span>
        </a>
    </div>

    <!-- پەیاما هەڵەیێ -->
    <?php if ($error): ?>
        <div class="alert alert-custom-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <!-- لیستا دەرمانان -->
        <div class="rx-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                    <i class="bi bi-capsule-pill text-primary me-2 fs-4"></i> لیستا دەرمانێن نۆسخەیێ
                </h5>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">تەنێ ئەو ڕیزانە پڕ بکەوە کە پێویستن</span>
            </div>
            
            <div id="medicine-rows" class="d-flex flex-column gap-3 mb-3">
                <div class="row g-2 medicine-item align-items-end">
                    <div class="col-md-3">
                        <label class="form-label-custom"><i class="bi bi-capsule text-primary"></i> ناڤێ دەرمانی</label>
                        <input type="text" name="med_name[]" class="form-control form-control-custom" list="meds_list" placeholder="m.g: Amoxicillin" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-custom"><i class="bi bi-node-plus text-info"></i> ژمارە / ژەم (Dosage)</label>
                        <input type="text" name="dosage[]" class="form-control form-control-custom" placeholder="m.g: 500mg">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-custom"><i class="bi bi-arrow-repeat text-warning"></i> چەند جار (Frequency)</label>
                        <input type="text" name="frequency[]" class="form-control form-control-custom" placeholder="m.g: 3 جار ل ڕۆژێ">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-custom"><i class="bi bi-clock-history text-secondary"></i> ماوە (Duration)</label>
                        <input type="text" name="duration[]" class="form-control form-control-custom" placeholder="m.g: 5 ڕۆژان">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-custom"><i class="bi bi-info-circle text-success"></i> ڕێنما (Instructions)</label>
                        <input type="text" name="instructions[]" class="form-control form-control-custom" placeholder="m.g: پشتی خوارنێ">
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-remove-custom remove-row w-100" disabled>
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <datalist id="meds_list">
                <?php foreach ($medicines_list as $m): ?>
                    <option value="<?php echo sanitize($m['name']); ?>">
                <?php endforeach; ?>
            </datalist>

            <div class="mt-3">
                <button type="button" id="add-more-med" class="btn btn-add-med d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>زێدەکرنا دەرمانەکێ دی</span>
                </button>
            </div>
        </div>

        <!-- تێبینی و تۆمارکرن -->
        <div class="rx-card mb-4">
            <div class="mb-4">
                <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-journal-text text-primary fs-5"></i> تێبینی یان ڕێننماییێن زێدە بۆ دەرمانخانێ یان نەخۆشی
                </label>
                <textarea name="notes" class="form-control form-control-custom" rows="3" placeholder="تێبینیێن تایبەت ئەگەر هەبن..."></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-submit-rx d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check-lg fs-5"></i>
                    <span>تۆمارکرن و تۆمارکرنا نۆسخەیێ</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('add-more-med').addEventListener('click', function() {
    const container = document.getElementById('medicine-rows');
    const firstRow = container.querySelector('.medicine-item');
    const newRow = firstRow.cloneNode(true);
    
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    newRow.querySelector('.remove-row').disabled = false;
    
    container.appendChild(newRow);
    checkRemoveButtons();
});

document.getElementById('medicine-rows').addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-row');
    if (btn && !btn.disabled) {
        const rows = document.querySelectorAll('.medicine-item');
        if (rows.length > 1) {
            btn.closest('.medicine-item').remove();
            checkRemoveButtons();
        }
    }
});

function checkRemoveButtons() {
    const rows = document.querySelectorAll('.medicine-item');
    if (rows.length === 1) {
        rows[0].querySelector('.remove-row').disabled = true;
    } else {
        rows.forEach(r => r.querySelector('.remove-row').disabled = false);
    }
}
</script>

<?php include '../includes/footer.php'; ?>