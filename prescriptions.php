<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireLogin();

// هێنانی زانیارییەکان بە شێوازێکی پارێزراو تاوەکو ڕێگری لە هەڵەی Column not found بکات
try {
    $stmt = $pdo->query("
        SELECT rx.*, 
               COALESCE(p.full_name, p.name, 'نەناسراو') AS patient_name, 
               p.patient_code,
               u.full_name AS doctor_name
        FROM prescriptions rx
        LEFT JOIN patients p ON rx.patient_id = p.id
        LEFT JOIN users u ON rx.doctor_id = u.id
        ORDER BY rx.id DESC
    ");
    $prescriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    // ئەگەر patient_code لە داتابەیسدا نەبوو، بڕگەی ژێرەوە جێبەجێ دەبێت
    $stmt = $pdo->query("
        SELECT rx.*, 
               COALESCE(p.full_name, p.name, 'نەناسراو') AS patient_name, 
               p.id AS patient_code,
               u.full_name AS doctor_name
        FROM prescriptions rx
        LEFT JOIN patients p ON rx.patient_id = p.id
        LEFT JOIN users u ON rx.doctor_id = u.id
        ORDER BY rx.id DESC
    ");
    $prescriptions = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ بەرزکردنەوەی ئاستی UIی لاپەڕەی ڕەچەتەکان -->
<style>
    .rx-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .rx-stat-badge {
        background-color: #f0f9ff;
        color: #0E6BA8;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 0.9rem;
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

    .badge-code {
        background-color: #f1f5f9;
        color: #0284c7;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
        font-family: monospace;
    }

    .badge-doctor {
        background-color: #fdf4ff;
        color: #9333ea;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        border: 1px solid #f5d0fe;
        display: inline-flex;
        align-items: center;
    }

    .badge-date {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
    }

    .btn-print-custom {
        background-color: #ffffff;
        border: 1.5px solid #0E6BA8;
        color: #0E6BA8;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 16px;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-print-custom:hover {
        background-color: #0E6BA8;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }
</style>

<div class="container-fluid py-4">
    <!-- سەردێڕی سەرەکی -->
    <div class="rx-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center text-dark">
                <i class="bi bi-capsule text-primary me-2 fs-3"></i> لیستا ڕەچەتەیان (Prescriptions)
            </h3>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i> بڕێڤەبرن و پرێنتکرنا ڕەچەتەیێن دەرمانان بۆ نەخۆشان
            </p>
        </div>
        <div class="rx-stat-badge d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-medical fs-5"></i>
            <span>کۆی گشتی: <?php echo count($prescriptions); ?> ڕەچەتە</span>
        </div>
    </div>

    <!-- خانەی لێگەڕیانی خێرا -->
    <div class="search-card p-3 mb-4">
        <div class="input-search-group">
            <input type="text" id="rxSearchInput" class="form-control form-control-search" placeholder="لێگەڕیانا خێرا ب ناڤێ نەخۆشی، کۆد یان دکتۆری...">
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>

    <!-- خشتەی ڕەچەتەکان -->
    <div class="table-card p-0">
        <div class="table-responsive">
            <table class="table custom-table table-hover align-middle mb-0" id="rxTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">#</th>
                        <th>کۆدی نەخۆش</th>
                        <th>ناڤێ نەخۆشی</th>
                        <th>دکتۆر</th>
                        <th>بەروار</th>
                        <th class="text-end">کریار</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prescriptions)): ?>
                        <?php foreach ($prescriptions as $index => $rx): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $index + 1; ?></td>
                            <td>
                                <span class="badge-code">
                                    <?php echo sanitize($rx['patient_code'] ?? $rx['patient_id'] ?? '-'); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark">
                                <i class="bi bi-person-fill text-primary me-1 fs-5"></i>
                                <?php echo sanitize($rx['patient_name'] ?? 'نەناسراو'); ?>
                            </td>
                            <td>
                                <span class="badge-doctor">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <?php echo sanitize($rx['doctor_name'] ?? 'دکتۆر'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-date">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    <?php echo !empty($rx['created_at']) ? date('Y-m-d', strtotime($rx['created_at'])) : '-'; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="../print_prescription.php?id=<?php echo $rx['id']; ?>" target="_blank" class="btn btn-print-custom">
                                    <i class="bi bi-printer-fill fs-6"></i>
                                    <span>پرێنت</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-x display-5 text-secondary opacity-50 d-block mb-2"></i>
                                <span class="fw-semibold">هیچ ڕەچەتەیەک تۆمار نەکراوە.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- سکریپتی لێگەڕیانی خێرا (Live Filter) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('rxSearchInput');
    const tableRows = document.querySelectorAll('#rxTable tbody tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();

            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>