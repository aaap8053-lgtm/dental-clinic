<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();

$rx_id = (int)($_GET['id'] ?? 0);

if ($rx_id <= 0) {
    die("کۆدی نۆسخەیێ هەڵەیە!");
}

// ئینانا زانیاریێن نۆسخەیێ ب شێوازێکی پارێزراو
try {
    $stmt = $pdo->prepare("
        SELECT rx.*, 
               COALESCE(p.full_name, p.name, 'نەناسراو') as patient_name, 
               p.age, 
               p.gender, 
               p.patient_code, 
               u.full_name as doctor_name 
        FROM prescriptions rx
        JOIN patients p ON rx.patient_id = p.id
        JOIN users u ON rx.doctor_id = u.id
        WHERE rx.id = ?
    ");
    $stmt->execute([$rx_id]);
    $rx = $stmt->fetch();
} catch (PDOException $e) {
    // ئەگەر patient_code لە مێزی patients نەبوو
    $stmt = $pdo->prepare("
        SELECT rx.*, 
               COALESCE(p.full_name, p.name, 'نەناسراو') as patient_name, 
               p.age, 
               p.gender, 
               p.id as patient_code, 
               u.full_name as doctor_name 
        FROM prescriptions rx
        JOIN patients p ON rx.patient_id = p.id
        JOIN users u ON rx.doctor_id = u.id
        WHERE rx.id = ?
    ");
    $stmt->execute([$rx_id]);
    $rx = $stmt->fetch();
}

if (!$rx) {
    die("نۆسخە نەهاتە دیتن!");
}

// ئینانا دەرمانێن ناو نۆسخەیێ
$stmtItems = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
$stmtItems->execute([$rx_id]);
$items = $stmtItems->fetchAll();

$patient_code   = !empty($rx['patient_code']) ? $rx['patient_code'] : $rx['patient_id'];
$patient_age    = !empty($rx['age']) ? $rx['age'] . ' ساڵ' : '-';
$patient_gender = (isset($rx['gender']) && ($rx['gender'] === 'male' || $rx['gender'] === 'نێر')) ? 'نێر' : 'مێ';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرێنتکرنا نۆسخەیێ - <?php echo sanitize($rx['patient_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f1f5f9; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #1e293b;
        }

        .action-bar {
            max-width: 800px;
            margin: 24px auto 0 auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 14px 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .btn-print-main {
            background-color: #0E6BA8;
            color: #ffffff;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 24px;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-print-main:hover {
            background-color: #0b5586;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(14, 107, 168, 0.3);
        }

        .btn-close-custom {
            border: 1.5px solid #cbd5e1;
            color: #475569;
            background-color: #ffffff;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .btn-close-custom:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .rx-card { 
            background: #ffffff; 
            max-width: 800px; 
            margin: 20px auto 40px auto; 
            padding: 48px; 
            border-radius: 16px; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .rx-header { 
            border-bottom: 2px solid #0E6BA8; 
            padding-bottom: 20px; 
            margin-bottom: 24px; 
        }

        .patient-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
        }

        .rx-symbol { 
            font-size: 3.2rem; 
            font-weight: 900; 
            color: #0E6BA8; 
            font-family: 'Times New Roman', Times, serif; 
            line-height: 1;
            margin-top: 10px;
        }

        .custom-table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .custom-table thead {
            background-color: #f8fafc;
        }

        .custom-table th {
            color: #475569;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table td {
            padding: 14px 16px;
            font-size: 0.92rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .notes-box {
            background-color: #fffbe3;
            border-right: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
        }

        .badge-code {
            background-color: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
        }

        /* ستایلی بەرز بؤ کاتی چاپکردن (A4 / Print) */
        @media print {
            .no-print { 
                display: none !important; 
            }
            body { 
                background: #ffffff !important; 
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .rx-card { 
                border: none !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                max-width: 100% !important; 
                box-shadow: none !important;
            }
            .patient-box {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
            }
            .custom-table th {
                background-color: #f1f5f9 !important;
            }
            .notes-box {
                background-color: #fffbe3 !important;
            }
        }
    </style>
</head>
<body>

<!-- دوگمەکانی دەسەڵاتی سەرەوە -->
<div class="container no-print">
    <div class="action-bar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-medical-fill text-primary fs-4"></i>
            <span class="fw-bold text-dark">نۆسخەیا دەرمانان بۆ چاپکردنێ</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-print-main d-flex align-items-center gap-2">
                <i class="bi bi-printer-fill fs-5"></i>
                <span>پرێنتکرنا نۆسخەیێ (Print)</span>
            </button>
            <button onclick="window.close()" class="btn btn-close-custom">داخستن</button>
        </div>
    </div>
</div>

<!-- کارتی سەرەکی نۆسخەیێ -->
<div class="rx-card">
    <!-- Header -->
    <div class="rx-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-primary mb-1 d-flex align-items-center">
                <i class="bi bi-hospital me-2 fs-3"></i> کلینیکا دکتۆڕێ ددانان
            </h3>
            <p class="text-muted small mb-0">
                دکتۆر: <strong><?php echo sanitize($rx['doctor_name']); ?></strong> | تایبەتمەندێ نەخۆشیێن دەڤ و ددانان
            </p>
        </div>
        <div class="text-end">
            <div class="mb-2">
                <span class="badge-code">کۆد: <?php echo sanitize($patient_code); ?></span>
            </div>
            <div class="small text-muted d-flex align-items-center justify-content-end gap-1">
                <i class="bi bi-calendar3"></i>
                <span>بەروار: <?php echo date('Y-m-d', strtotime($rx['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Patient Details -->
    <div class="patient-box mb-4">
        <div class="row align-items-center g-2">
            <div class="col-md-6">
                <span class="text-muted small d-block">ناڤێ نەخۆشی:</span>
                <strong class="text-dark fs-6"><?php echo sanitize($rx['patient_name']); ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">عەمر:</span>
                <strong class="text-dark"><?php echo sanitize($patient_age); ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">ڕەگەز:</span>
                <strong class="text-dark"><?php echo $patient_gender; ?></strong>
            </div>
        </div>
    </div>

    <!-- Rx Symbol -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="rx-symbol">Rx</div>
        <div class="text-muted small fst-italic">Prescription Details</div>
    </div>

    <!-- Medicine Table -->
    <div class="table-responsive mb-4">
        <table class="table custom-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">ناڤێ دەرمانی</th>
                    <th style="width: 15%;">ژەم (Dosage)</th>
                    <th style="width: 20%;">تکراربوون (Frequency)</th>
                    <th style="width: 15%;">ماوە</th>
                    <th style="width: 15%;">ڕێننمایی</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php $no = 1; foreach ($items as $item): ?>
                    <tr>
                        <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                        <td class="fw-bold text-dark"><?php echo sanitize($item['medicine_name'] ?? ''); ?></td>
                        <td>
                            <code class="bg-light text-primary px-2 py-1 rounded border fw-bold"><?php echo sanitize($item['dosage'] ?? '-'); ?></code>
                        </td>
                        <td class="text-secondary"><?php echo sanitize($item['frequency'] ?? '-'); ?></td>
                        <td class="text-secondary"><?php echo sanitize($item['duration'] ?? '-'); ?></td>
                        <td class="small text-muted"><?php echo sanitize($item['instructions'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">هیچ دەرمانێک تۆمار نەکراوە.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Notes Section -->
    <?php if (!empty($rx['notes'])): ?>
        <div class="notes-box mb-4">
            <strong class="text-warning-emphasis d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-exclamation-circle-fill"></i> تێبینیێن دکتۆری:
            </strong>
            <p class="mb-0 small text-dark"><?php echo nl2br(sanitize($rx['notes'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Footer Signature -->
    <div class="row mt-5 pt-4 align-items-end">
        <div class="col-6">
            <p class="small text-muted mb-0">هیڤییا ساخیا باش بۆ تە سەرکەفتن.</p>
        </div>
        <div class="col-6 text-center">
            <p class="fw-bold text-dark mb-4">ئیمزا و مۆرا دکتۆری:</p>
            <p class="text-muted mb-0">_______________________</p>
        </div>
    </div>
</div>

</body>
</html>