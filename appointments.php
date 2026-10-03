<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['secretary', 'doctor']);

// زێدەکرنا ژڤانی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $date = sanitize($_POST['appointment_date'] ?? '');
    $time = sanitize($_POST['appointment_time'] ?? '');

    if ($patient_id > 0 && !empty($date) && !empty($time)) {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time) VALUES (?, ?, ?)");
        $stmt->execute([$patient_id, $date, $time]);
    }
}

// گۆڕینا دۆخێ ژڤانی
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['action'] ?? '');
    if (in_array($status, ['pending', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: appointments.php');
        exit();
    }
}

$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, COALESCE(p.full_name, p.name, 'نەناسراو') as patient_name, p.phone 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.appointment_date = ? 
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$today]);
$today_apps = $stmt->fetchAll();

$patients = $pdo->query("SELECT id, COALESCE(full_name, name, 'نەناسراو') as name, phone FROM patients ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ نوێکردنەوە و ڕێکخستنی دیزاینی لاپەڕەی ژڤانەکان -->
<style>
    .appointments-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
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
    .time-badge {
        background-color: #f1f5f9;
        color: #0f172a;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
    }
    .status-badge-pending {
        background-color: #fef3c7;
        color: #b45309;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        display: inline-block;
    }
    .status-badge-completed {
        background-color: #d1fae5;
        color: #047857;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        display: inline-block;
    }
    .status-badge-cancelled {
        background-color: #fee2e2;
        color: #b91c1c;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        display: inline-block;
    }
    .btn-add-appointment {
        background-color: #0E6BA8;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        padding: 10px 18px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }
    .btn-add-appointment:hover {
        background-color: #0a5282;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(14, 107, 168, 0.3);
    }
    .btn-phone-link {
        border: 1px solid #10b981;
        color: #059669;
        background-color: #ecfdf5;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.85rem;
        padding: 5px 12px;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    .btn-phone-link:hover {
        background-color: #10b981;
        color: #ffffff;
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
    .form-control-custom, .form-select-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 0.92rem;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #0E6BA8;
        box-shadow: 0 0 0 3px rgba(14, 107, 168, 0.15);
    }
</style>

<!-- سەردێڕ و دوگمەی زێدەکرنی ژڤان -->
<div class="appointments-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 d-flex align-items-center">
            <i class="bi bi-calendar-check text-primary me-2 fs-3"></i> ژڤانێن ئەڤرۆ
        </h3>
        <p class="text-muted small mb-0">
            <i class="bi bi-calendar3 me-1"></i> بەروار: <strong><?php echo $today; ?></strong> | لێستەکا گشتی یا ژڤانێن تۆمارکری
        </p>
    </div>
    <button class="btn btn-add-appointment d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addAppModal">
        <i class="bi bi-calendar-plus fs-5"></i>
        <span>تۆمارکرنا ژڤانێ نوو</span>
    </button>
</div>

<!-- خشتەی ژڤانەکان -->
<div class="table-card p-0 mb-4">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>کاتژمێر</th>
                    <th>ناڤێ نەخۆشی</th>
                    <th>تەلەفۆن</th>
                    <th>دۆخ</th>
                    <th class="text-end">کریاڕ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($today_apps)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x display-5 text-secondary opacity-50 d-block mb-2"></i>
                            <span class="fw-semibold">هیچ ژڤانەک بۆ ئەڤرۆ نینە.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($today_apps as $app): ?>
                    <tr>
                        <td>
                            <span class="time-badge">
                                <i class="bi bi-clock me-1 text-primary"></i>
                                <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                            </span>
                        </td>
                        <td class="fw-bold text-dark">
                            <?php echo sanitize($app['patient_name'] ?? 'نەناسراو'); ?>
                        </td>
                        <td>
                            <a href="tel:<?php echo $app['phone'] ?? ''; ?>" class="btn-phone-link">
                                <i class="bi bi-telephone me-1"></i>
                                <?php echo sanitize($app['phone'] ?? '-'); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($app['status'] === 'pending'): ?>
                                <span class="status-badge-pending">
                                    <i class="bi bi-hourglass-split me-1"></i> د چاڤەڕێکرنێ دا
                                </span>
                            <?php elseif ($app['status'] === 'completed'): ?>
                                <span class="status-badge-completed">
                                    <i class="bi bi-check-circle-fill me-1"></i> چوو ژوور / تەمام بوو
                                </span>
                            <?php else: ?>
                                <span class="status-badge-cancelled">
                                    <i class="bi bi-x-circle-fill me-1"></i> نەهاتیە / هاتە هەڵوەشاندن
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group gap-1" role="group">
                                <a href="?action=completed&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success rounded-2 px-3 fw-semibold">
                                    <i class="bi bi-check-lg me-1"></i> چوو ژوور
                                </a>
                                <a href="?action=cancelled&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-danger rounded-2 px-3 fw-semibold">
                                    <i class="bi bi-x-lg me-1"></i> نەهات
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Appointment -->
<div class="modal fade" id="addAppModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
            <i class="bi bi-calendar-plus text-primary me-2"></i> ژڤانێ نوو
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body modal-body-custom">
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">هەڵبژارتنا نەخۆشی</label>
            <select name="patient_id" class="form-select form-select-custom" required>
                <option value="">-- دیاربکە --</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?> (<?php echo sanitize($p['phone'] ?? '-'); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">بەروار (Date)</label>
            <input type="date" name="appointment_date" class="form-control form-control-custom" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">کاتژمێر (Time)</label>
            <input type="time" name="appointment_time" class="form-control form-control-custom" required>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom">
        <button type="submit" name="add_appointment" class="btn btn-add-appointment w-100 py-2 fs-6">
            <i class="bi bi-check-circle me-1"></i> تۆمارکرن
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>