<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['secretary', 'doctor']);

$today = date('Y-m-d');

// گۆڕینا دۆخێ ژڤانی
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['action'] ?? '');
    if (in_array($status, ['pending', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: reminders.php');
        exit();
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, p.id as patient_id, COALESCE(p.full_name, p.name, 'نەناسراو') as patient_name, p.phone 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.appointment_date = ? 
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$today]);
$today_apps = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ نوێکردنەوە و بڵندکرنا دیزاینا لاپەڕا ئاگەهداریان -->
<style>
    .reminders-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    .reminder-card {
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
        overflow: hidden;
    }
    .reminder-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08) !important;
    }
    .border-card-pending {
        border-right: 5px solid #f59e0b !important;
    }
    .border-card-completed {
        border-right: 5px solid #10b981 !important;
    }
    .border-card-cancelled {
        border-right: 5px solid #ef4444 !important;
    }
    .time-badge {
        background-color: #f1f5f9;
        color: #334155;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 12px;
        border-radius: 10px;
    }
    .btn-whatsapp-custom {
        background-color: #25d366;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-whatsapp-custom:hover {
        background-color: #1da851;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);
    }
    .btn-phone-custom {
        border: 1.5px solid #0E6BA8;
        color: #0E6BA8;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-phone-custom:hover {
        background-color: #0E6BA8;
        color: #ffffff;
    }
    .patient-link {
        color: #0f172a;
        transition: color 0.2s ease;
    }
    .patient-link:hover {
        color: #0E6BA8;
    }
    .w-48 {
        width: 48.5%;
    }
    .empty-state-box {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        padding: 50px 20px;
    }
    .status-badge-pending {
        background-color: #fef3c7;
        color: #b45309;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
    }
    .status-badge-completed {
        background-color: #d1fae5;
        color: #047857;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
    }
    .status-badge-cancelled {
        background-color: #fee2e2;
        color: #b91c1c;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
    }
</style>

<!-- سەردێڕ و زانیاری سەرەکی لاپەڕە -->
<div class="reminders-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 d-flex align-items-center">
            <i class="bi bi-bell-fill text-warning me-2 fs-3"></i> ئاگەهداریێن ژڤانێن ئەڤرۆ
        </h3>
        <p class="text-muted small mb-0">
            <i class="bi bi-calendar3 me-1"></i> بەروار: <?php echo $today; ?> | ئاگادارکرنا نەخۆشان بەری سەرەدانێ
        </p>
    </div>
    <span class="badge bg-primary fs-6 px-3 py-2 rounded-3 shadow-sm">
        کۆم: <?php echo count($today_apps); ?> ژڤان
    </span>
</div>

<!-- گرید و لیستەی ژڤانەکان -->
<div class="row g-3">
    <?php if (empty($today_apps)): ?>
        <div class="col-12">
            <div class="empty-state-box text-center text-muted shadow-sm">
                <div class="mb-3">
                    <i class="bi bi-calendar-x display-4 text-secondary opacity-50"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">هیچ ژڤانەک بۆ ئەڤرۆ نینە.</h5>
                <p class="small text-muted mb-0">لە ئێستادا هیچ ژوانێک بۆ ئەمڕۆ تۆمار نەکراوە.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($today_apps as $app): 
            // پاڵفتەکرنا ژمارا تەلەفۆنێ بۆ WhatsApp ب پارێزراوی
            $phone_number = $app['phone'] ?? '';
            $clean_phone = preg_replace('/[^0-9]/', '', $phone_number);
            if (strpos($clean_phone, '0') === 0) {
                $clean_phone = '964' . substr($clean_phone, 1);
            }
            $wa_message = rawurlencode("سڵاو ڕێز " . ($app['patient_name'] ?? '') . "، هیڤداری ئاگەهدار بی ژڤانێ تە ل کلینیکا دکتورێ ددانان ئەڤرۆ دەمژمێر " . date('h:i A', strtotime($app['appointment_time'])) . " یە.");
            
            // دیاریکردنی کلاسی هێڵی دەوربەری کارتەکە
            $card_border_class = $app['status'] === 'completed' ? 'border-card-completed' : ($app['status'] === 'cancelled' ? 'border-card-cancelled' : 'border-card-pending');
        ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card reminder-card h-100 shadow-sm border-0 <?php echo $card_border_class; ?>">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <!-- بەشی دەم و دۆخ -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="time-badge">
                                    <i class="bi bi-clock me-1 text-primary"></i> <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                </span>
                                <?php if ($app['status'] === 'pending'): ?>
                                    <span class="status-badge-pending"><i class="bi bi-hourglass-split me-1"></i> د چاڤەڕێکرنێ دا</span>
                                <?php elseif ($app['status'] === 'completed'): ?>
                                    <span class="status-badge-completed"><i class="bi bi-check-circle-fill me-1"></i> چوو ژوور / تەمام</span>
                                <?php else: ?>
                                    <span class="status-badge-cancelled"><i class="bi bi-x-circle-fill me-1"></i> نەهاتیە</span>
                                <?php endif; ?>
                            </div>

                            <!-- بەشی زانیاریی نەخۆش -->
                            <h5 class="fw-bold mb-2">
                                <a href="patient_profile.php?id=<?php echo $app['patient_id']; ?>" class="text-decoration-none patient-link">
                                    <?php echo sanitize($app['patient_name'] ?? 'نەناسراو'); ?> 
                                    <i class="bi bi-arrow-up-left-square small text-primary ms-1"></i>
                                </a>
                            </h5>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-telephone me-1 text-secondary"></i> <?php echo sanitize($app['phone'] ?? '-'); ?>
                            </p>

                            <!-- دوگمەکانی پەیوەندی -->
                            <div class="d-grid gap-2 mb-3">
                                <a href="https://wa.me/<?php echo $clean_phone; ?>?text=<?php echo $wa_message; ?>" target="_blank" class="btn btn-whatsapp-custom btn-sm py-2">
                                    <i class="bi bi-whatsapp me-1 fs-6"></i> فرێکرنا ئاگاداریێ (WhatsApp)
                                </a>
                                <a href="tel:<?php echo $app['phone'] ?? ''; ?>" class="btn btn-phone-custom btn-sm py-2">
                                    <i class="bi bi-telephone-outbound me-1"></i> پەیوەندیکرنا ڕاستەوخۆ
                                </a>
                            </div>
                        </div>

                        <div>
                            <hr class="my-3 opacity-25">

                            <!-- دوگمەکانی گۆڕینی دۆخ -->
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="?action=completed&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success w-48 py-2 rounded-3 fw-semibold">
                                    <i class="bi bi-check-lg me-1"></i> هاتە کلینیکێ
                                </a>
                                <a href="?action=cancelled&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-danger w-48 py-2 rounded-3 fw-semibold">
                                    <i class="bi bi-x-lg me-1"></i> نەهاتیە
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>