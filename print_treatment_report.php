<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();

$patient_id = (int)($_GET['id'] ?? 0);

if ($patient_id <= 0) {
    die("کۆدی نەخۆش دیار نینە!");
}

// ئینانا زانیاریێن نەخۆشی
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("نەخۆش نەهاتە دیتن!");
}

// ئینانا هەمی جەلسەیێن چارەسەریێ
$stmtSessions = $pdo->prepare("
    SELECT ps.*, u.full_name as doctor_name 
    FROM patient_sessions ps
    LEFT JOIN users u ON ps.doctor_id = u.id
    WHERE ps.patient_id = ?
    ORDER BY ps.id ASC
");
$stmtSessions->execute([$patient_id]);
$sessions = $stmtSessions->fetchAll();

// دیارکرنا ڕەگەز و عەمر ب شێوازەکێ پارێزراو
$gender_text = 'دیار نینە';
if (isset($patient['gender']) && !empty($patient['gender'])) {
    $gender_text = ($patient['gender'] === 'male' || $patient['gender'] === 'نێر') ? 'نێر' : 'مێ';
}

$patient_name  = $patient['full_name'] ?? $patient['name'] ?? 'نەناسراو';
$patient_code  = $patient['patient_code'] ?? $patient['id'];
$patient_phone = $patient['phone'] ?? '-';
$patient_age   = $patient['age'] ?? '-';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆڕتا تە مامبوونا چارەسەریێ - <?php echo sanitize($patient_name); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-teal: #0E6BA8;
            --primary-teal-hover: #0E6BA8;
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            
        }

        .action-bar {
            max-width: 880px;
            margin: 24px auto 0 auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 14px 24px;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .btn-print-main {
            background-color: var(--primary-teal);
            color: #ffffff;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 22px;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-print-main:hover {
            background-color: var(--primary-teal-hover);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 80, 118, 0.3);
        }

        .btn-back-custom {
            border: 1.5px solid #cbd5e1;
            color: #475569;
            background-color: #ffffff;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-back-custom:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .report-card {
            max-width: 880px;
            margin: 20px auto 40px auto;
            background: #ffffff;
            padding: 48px;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .clinic-brand {
            color: var(--primary-teal);
        }

        .info-box {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .info-item-label {
            font-size: 0.65rem;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .info-item-value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .badge-code {
            background-color: #f0f9fd;
            color: var(--primary-teal);
            border: 1px solid #bbe1f7;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 8px;
        }

        .badge-session {
            background-color: #0E6BA8;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.60rem;
            font-weight: 700;
            
        }

        .custom-table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            align-items: center;
            text-align: center;
            justify-content: space-between;
            
            
        }

        .custom-table thead {
            background-color: #f8fafc;
            
        }

        .custom-table th {
            color: #475569;
            font-weight: 700;
            font-size: 0.65rem;
            padding: 14px;
            border-bottom: 2px solid #e2e8f0;
            
        }

        .custom-table td {
            padding: 14px;
            font-size: 0.65rem;
            border-bottom: 1px solid #f1f5f9;
            
        }

        .cost-tag {
            color: #0f766e;
            font-weight: 700;
            background-color: #f0fdf4;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #dcfce7;
        }

        /* ستایلی چاپکردن (Print Optimization) */
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
            .report-card {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                border: none !important;
            }
            .info-box {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
            }
            .custom-table th {
                background-color: #f1f5f9 !important;
            }
        }
    </style>
</head>
<body>

<!-- دوگمەکانی سەرەوە (دەسەڵات) -->
<div class="container no-print">
    <div class="action-bar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-medical-fill text-teal fs-4" style="color: #0E6BA8;"></i>
            <span class="fw-bold text-dark">ڕاپۆڕتا تە مامبوونا چارەسەریێ</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-print-main d-flex align-items-center gap-2">
                <i class="bi bi-printer-fill fs-5"></i>
                <span>پرێنتکرنا ڕاپۆرتێ</span>
            </button>
            <a href="secretary/patient_profile.php?id=<?php echo $patient_id; ?>" class="btn btn-back-custom d-flex align-items-center gap-1">
                <i class="bi bi-arrow-right"></i>
                <span>زڤڕین</span>
            </a>
        </div>
    </div>
</div>

<div class="container">
    <div class="report-card">
        <!-- Header -->
        <div class="row align-items-center mb-4 border-bottom pb-4">
            <div class="col-7">
                <h3 class="fw-bold text-dark mb-2">ڕاپۆڕتا تە مامبوونا چارەسەریێ</h3>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">کۆدێ نەخۆشی: <code class="badge-code"><?php echo sanitize($patient_code); ?></code></span>
                    <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>بەروار: <?php echo date('Y-m-d'); ?></span>
                </div>
            </div>
            <div class="col-5 text-end">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <div class="text-end">
                        <h4 class="fw-bold clinic-brand mb-0">میآن لطب الأسنان</h4>
                        <small class="text-muted fw-semibold">Meyan Dental Clinic</small>
                    </div>
                    <i class="bi bi-heart-pulse-fill clinic-brand fs-1"></i>
                </div>
            </div>
        </div>

        <!-- Patient Details -->
        <div class="info-box mb-4">
            <div class="row g-3 text-start">
                <div class="col-md-5">
                    <span class="info-item-label"><i class="bi bi-person-fill me-1"></i>ناڤێ نەخۆشی:</span>
                    <span class="info-item-value"><?php echo sanitize($patient_name); ?></span>
                </div>
                <div class="col-md-4">
                    <span class="info-item-label"><i class="bi bi-telephone-fill me-1"></i>ژمارا تەلەفۆنێ:</span>
                    <span class="info-item-value"><?php echo sanitize($patient_phone); ?></span>
                </div>
                <div class="col-md-3">
                    <span class="info-item-label"><i class="bi bi-gender-ambiguous me-1"></i>عەمر / ڕەگەز:</span>
                    <span class="info-item-value"><?php echo sanitize($patient_age); ?> ساڵ (<?php echo $gender_text; ?>)</span>
                </div>
            </div>
        </div>

        <!-- Sessions Table Title -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold clinic-brand mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span>خشتەیێن تە مامیا جەلسە و چارەسەریان</span>
            </h6>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table custom-table align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th >جەلسە</th>
                        <th  class="text-start">چارەسەریا هاتیە کرن</th>
                        <th  class="text-start">تێبینیێن دکتۆری</th>
                        <th >بەروار</th>
                        <th >پارێ جەلسێ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sessions) > 0): ?>
                        <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><span class="badge-session"><?php echo sanitize($s['session_number'] ?? ''); ?></span></td>
                            <td class="fw-bold text-start text-dark"><?php echo nl2br(sanitize($s['treatment_done'] ?? '')); ?></td>
                            <td class="small text-muted text-start"><?php echo !empty($s['notes']) ? nl2br(sanitize($s['notes'])) : '-'; ?></td>
                            <td class="small text-secondary"><?php echo date('Y-m-d', strtotime($s['created_at'])); ?></td>
                            <td><span class="cost-tag">$<?php echo number_format((float)($s['cost'] ?? 0), 2); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">هیچ جەلسەیەک تۆمار نەکراوە.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Signature -->
        <div class="row mt-5 pt-5 text-center">
            <div class="col-6">
                <p class="fw-bold text-dark mb-4">مۆرا کلینیکێ</p>
                <p class="text-muted mb-0">_______________________</p>
            </div>
            <div class="col-6">
                <p class="fw-bold text-dark mb-4">ئیمزا و ناڤێ دکتۆری</p>
                <p class="text-muted mb-0">_______________________</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>