<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['doctor', 'admin']);

$type = sanitize($_GET['type'] ?? 'daily');

if ($type === 'monthly') {
    $title = "ڕاپۆرتا هەیڤانە (" . date('Y-m') . ")";
    $where = "WHERE DATE_FORMAT(sp.session_date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m')";
    $app_where = "WHERE DATE_FORMAT(appointment_date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m')";
} elseif ($type === 'yearly') {
    $title = "ڕاپۆرتا ساڵانە (" . date('Y') . ")";
    $where = "WHERE YEAR(sp.session_date) = YEAR(CURRENT_DATE())";
    $app_where = "WHERE YEAR(appointment_date) = YEAR(CURRENT_DATE())";
} else {
    $type = 'daily';
    $title = "ڕاپۆرتا ڕۆژانە (" . date('Y-m-d') . ")";
    $where = "WHERE sp.session_date = CURRENT_DATE()";
    $app_where = "WHERE appointment_date = CURRENT_DATE()";
}

// Total Patients Count
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments $app_where");
$total_patients = $stmt->fetchColumn();

// Financial Summary
$stmt = $pdo->query("SELECT SUM(amount_paid) as total_income, SUM(remaining_balance) as total_remaining FROM sessions_payments sp $where");
$totals = $stmt->fetch();

// Details List
$stmt = $pdo->query("
    SELECT sp.*, p.name as patient_name 
    FROM sessions_payments sp 
    JOIN patients p ON sp.patient_id = p.id 
    $where 
    ORDER BY sp.id DESC
");
$details = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ ڕاپۆرتەکان و ڕێکخستنی دیزاینی چاپکردن -->
<style>
    .reports-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .btn-filter-group .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 18px;
        font-size: 0.9rem;
        transition: all 0.25s ease;
    }

    .btn-print-custom {
        background-color: #0f172a;
        color: #ffffff;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 20px;
        transition: all 0.25s ease;
        border: none;
    }

    .btn-print-custom:hover {
        background-color: #1e293b;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
    }

    .report-main-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 32px;
    }

    .stat-box {
        border-radius: 14px;
        padding: 20px;
        transition: all 0.25s ease;
        border: 1px solid transparent;
        height: 100%;
    }

    .stat-box-patients {
        background-color: #f0f9ff;
        border-color: #bae6fd;
    }

    .stat-box-income {
        background-color: #ecfdf5;
        border-color: #a7f3d0;
    }

    .stat-box-remaining {
        background-color: #fef2f2;
        border-color: #fecaca;
    }

    .stat-box .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .stat-box-patients .stat-label { color: #0369a1; }
    .stat-box-income .stat-label { color: #047857; }
    .stat-box-remaining .stat-label { color: #b91c1c; }

    .stat-box .stat-value {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0;
    }

    .stat-box-patients .stat-value { color: #0284c7; }
    .stat-box-income .stat-value { color: #10b981; }
    .stat-box-remaining .stat-value { color: #ef4444; }

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

    .badge-date {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
    }

    .badge-paid {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        border: 1px solid #a7f3d0;
    }

    .badge-rem {
        background-color: #fef2f2;
        color: #b91c1c;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        border: 1px solid #fecaca;
    }

    /* ڕێکخستنی ستایلی چاپکردن (Print Mode) */
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background-color: #ffffff !important;
        }
        .report-main-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        .stat-box {
            border: 1px solid #cbd5e1 !important;
        }
    }
</style>

<div class="container-fluid py-4">
    <!-- بەشی کۆنترۆڵ و فلتەرەکان -->
    <div class="reports-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
        <div>
            <h3 class="fw-bold mb-1 text-dark d-flex align-items-center">
                <i class="bi bi-bar-chart-line-fill text-primary me-2 fs-3"></i> سیستەمێ ڕاپۆرتان
            </h3>
            <p class="text-muted small mb-0">بینین و شیکارکرنا داهات و ئامارێن کلینیکێ</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-filter-group" role="group">
                <a href="?type=daily" class="btn <?php echo $type==='daily' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="bi bi-calendar-day me-1"></i> ڕۆژانە
                </a>
                <a href="?type=monthly" class="btn <?php echo $type==='monthly' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="bi bi-calendar-month me-1"></i> هەیڤانە
                </a>
                <a href="?type=yearly" class="btn <?php echo $type==='yearly' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="bi bi-calendar-range me-1"></i> ساڵانە
                </a>
            </div>
            <button onclick="window.print()" class="btn btn-print-custom d-flex align-items-center gap-2">
                <i class="bi bi-printer-fill fs-6"></i>
                <span>چاپکرن</span>
            </button>
        </div>
    </div>

    <!-- کارتی سەرەکی ڕاپۆرت -->
    <div class="report-main-card">
        <!-- سەردێڕی ناو ڕاپۆرت -->
        <div class="text-center mb-4 pb-3 border-bottom">
            <h2 class="fw-bold text-dark mb-1"><?php echo sanitize($title); ?></h2>
            <p class="text-muted mb-0">
                <i class="bi bi-hospital me-1"></i> کلینیکا دکتورێ ددانان - ڕاپۆرتا دارایی و نەخۆشان
            </p>
        </div>

        <!-- ئامارە سەرەکییەکان -->
        <div class="row text-center mb-4 g-3">
            <div class="col-md-4">
                <div class="stat-box stat-box-patients">
                    <div class="stat-label">
                        <i class="bi bi-people-fill fs-5"></i>
                        <span>کۆمبۆنا نەخۆشان</span>
                    </div>
                    <div class="stat-value"><?php echo (int)$total_patients; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box stat-box-income">
                    <div class="stat-label">
                        <i class="bi bi-cash-stack fs-5"></i>
                        <span>کۆمبۆنا داهاتێ وەرهاتی</span>
                    </div>
                    <div class="stat-value">$<?php echo number_format($totals['total_income'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box stat-box-remaining">
                    <div class="stat-label">
                        <i class="bi bi-wallet2 fs-5"></i>
                        <span>کۆمبۆنا قستێن مایی</span>
                    </div>
                    <div class="stat-value">$<?php echo number_format($totals['total_remaining'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- خشتەی وردەکارییەکان -->
        <h5 class="fw-bold text-dark mb-3 d-flex align-items-center">
            <i class="bi bi-list-task text-primary me-2"></i> وردوکاریێن قست و جەلسەیان:
        </h5>
        
        <div class="table-responsive rounded-3 border">
            <table class="table custom-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>بەروار</th>
                        <th>ناڤێ نەخۆشی</th>
                        <th>چارەسەری</th>
                        <th>بڕێ وەرهاتی</th>
                        <th>بڕێ مایی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($details)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-5 text-secondary opacity-50 d-block mb-2"></i>
                                <span class="fw-semibold">هیچ داتایەک دیار نینە.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($details as $d): ?>
                        <tr>
                            <td>
                                <span class="badge-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo sanitize($d['session_date'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark">
                                <i class="bi bi-person-circle text-primary me-1 fs-6"></i>
                                <?php echo sanitize($d['patient_name'] ?? ''); ?>
                            </td>
                            <td class="text-secondary">
                                <?php echo sanitize($d['treatment_done'] ?? ''); ?>
                            </td>
                            <td>
                                <span class="badge-paid">
                                    $<?php echo number_format($d['amount_paid'] ?? 0, 2); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-rem">
                                    $<?php echo number_format($d['remaining_balance'] ?? 0, 2); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>