<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['doctor']);

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF Token Invalid');
    }

    $app_id = (int)($_POST['app_id'] ?? 0);
    $notes = sanitize($_POST['doctor_notes'] ?? '');

    if ($app_id > 0) {
        $stmt = $pdo->prepare("UPDATE appointments SET doctor_notes = ? WHERE id = ?");
        if ($stmt->execute([$notes, $app_id])) {
            $msg = "تێبینیا نوژداری ب سەرکەفتیانە هاتە پاراستن!";
        } else {
            $error = "هەڵەیەک لە پاراستنی تێبینی دروست بوو.";
        }
    }
}

$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, p.id as patient_id, p.name as patient_name, p.age, p.general_notes
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.appointment_date = ? AND a.status = 'completed'
    ORDER BY a.appointment_time DESC
");
$stmt->execute([$today]);
$completed_apps = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<h3 class="fw-bold mb-4">ژڤانێن تەمامبووی یێن ئەڤرۆ - تێبینیێن نوژداری</h3>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <?php if (empty($completed_apps)): ?>
        <div class="col-12"><div class="alert alert-info">هیچ نەخۆشەک بۆ ئەڤرۆ تەمام نەبوویە.</div></div>
    <?php else: ?>
        <?php foreach ($completed_apps as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold text-primary mb-0"><?php echo sanitize($app['patient_name'] ?? ''); ?></h5>
                        <span class="badge bg-secondary"><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></span>
                    </div>
                    <p class="small text-muted mb-2">ژیی: <?php echo sanitize($app['age'] ?? 'نەدیار'); ?> | تێبینیێن گشتی: <?php echo sanitize($app['general_notes'] ?? ''); ?></p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                        <div class="mb-2">
                            <label class="form-label fw-bold">تێبینیا نوژداری / چارەسەریا ئەڤرۆ:</label>
                            <textarea name="doctor_notes" class="form-control" rows="3"><?php echo sanitize($app['doctor_notes'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="save_notes" class="btn btn-sm btn-success w-100"><i class="bi bi-save me-1"></i> پاراستنا تێبینیێ</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>