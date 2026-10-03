<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireRole(['secretary', 'doctor']);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $patient_id        = (int)($_POST['patient_id'] ?? 0);
    $session_date      = sanitize($_POST['session_date'] ?? '');
    $treatment_done    = sanitize($_POST['treatment_done'] ?? '');
    $amount_paid       = (float)($_POST['amount_paid'] ?? 0.00);
    $remaining_balance = (float)($_POST['remaining_balance'] ?? 0.00);
    $notes             = sanitize($_POST['notes'] ?? '');

    if ($patient_id > 0 && !empty($session_date)) {
        $stmt = $pdo->prepare("INSERT INTO sessions_payments (patient_id, session_date, treatment_done, amount_paid, remaining_balance, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $session_date, $treatment_done, $amount_paid, $remaining_balance, $notes]);
        $msg = "جەلسە و قست ب سەرکەفتیانە تۆمار بوو!";
    }
}

$stmt = $pdo->query("
    SELECT sp.*, COALESCE(p.full_name, p.name, 'نەناسراو') as patient_name 
    FROM sessions_payments sp 
    JOIN patients p ON sp.patient_id = p.id 
    ORDER BY sp.id DESC
");
$payments = $stmt->fetchAll();

$patients = $pdo->query("SELECT id, COALESCE(full_name, name, 'نەناسراو') as name FROM patients ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- CSSی تایبەت بۆ بەرزکردنەوەی ئاستی UI ی لاپەڕەی جەلسە و قستەکان -->
<style>
    .payments-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .btn-add-payment {
        background-color: #0E6BA8;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 20px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
    }

    .btn-add-payment:hover {
        background-color: #0a5282;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(14, 107, 168, 0.3);
    }

    .btn-currency-tool {
        background-color: #f0f9ff;
        border: 1.5px solid #0E6BA8;
        color: #0E6BA8;
        border-radius: 12px;
        font-weight: 600;
        padding: 12px;
        transition: all 0.25s ease;
    }

    .btn-currency-tool:hover {
        background-color: #0E6BA8;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.2);
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

    .badge-paid {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        border: 1px solid #a7f3d0;
        display: inline-flex;
        align-items: center;
    }

    .badge-remaining {
        background-color: #fef2f2;
        color: #b91c1c;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        border: 1px solid #fecaca;
        display: inline-flex;
        align-items: center;
    }

    .badge-remaining-zero {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
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

    .date-badge {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 0.85rem;
    }
</style>

<!-- سەردێڕ و دوگمەی زێدەکرنا جەلسەیا نوو -->
<div class="payments-header-card d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 d-flex align-items-center text-dark">
            <i class="bi bi-wallet2 text-primary me-2 fs-3"></i> ڕێڤەبرنا جەلسە و قستان
        </h3>
        <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i> تۆمارکرن و بەدواداچوونی پێدانی پارە و بڕی ماوەی جەلسەکانی نەخۆش
        </p>
    </div>
    <button class="btn btn-add-payment d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
        <i class="bi bi-plus-circle-fill fs-5"></i>
        <span>تۆمارکرنا قست/جەلسەیا نوو</span>
    </button>
</div>

<!-- پەیاما سەرکەوتنی تۆمارکردن -->
<?php if ($msg): ?>
    <div class="alert alert-custom-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div><?php echo $msg; ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- دوگمەی کەرستەی گۆڕینی دراو -->


<!-- خشتەی جەلسە و قستەکان -->
<div class="table-card p-0 mb-4">
    <div class="table-responsive">
        <table class="table custom-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>بەروار</th>
                    <th>ناڤێ نەخۆشی</th>
                    <th>کارێ هاتیەکرن</th>
                    <th>بڕێ وەرگرتی ($)</th>
                    <th>بڕێ مایی ($)</th>
                    <th>تێبینی</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td>
                            <span class="date-badge">
                                <i class="bi bi-calendar3 me-1 opacity-75"></i>
                                <?php echo sanitize($pay['session_date'] ?? '-'); ?>
                            </span>
                        </td>
                        <td class="fw-bold text-dark">
                            <i class="bi bi-person me-1 text-secondary"></i>
                            <?php echo sanitize($pay['patient_name'] ?? 'نەناسراو'); ?>
                        </td>
                        <td>
                            <span class="fw-semibold text-secondary">
                                <?php echo sanitize($pay['treatment_done'] ?? '-'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-paid">
                                <i class="bi bi-arrow-down-left-circle me-1"></i>
                                $<?php echo number_format((float)($pay['amount_paid'] ?? 0), 2); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $rem = (float)($pay['remaining_balance'] ?? 0);
                            if ($rem > 0): 
                            ?>
                                <span class="badge-remaining">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    $<?php echo number_format($rem, 2); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-remaining-zero">
                                    <i class="bi bi-check2-all me-1"></i>
                                    $0.00
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-muted small">
                                <?php echo !empty($pay['notes']) ? sanitize($pay['notes']) : '-'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt display-5 text-secondary opacity-50 d-block mb-2"></i>
                            <span class="fw-semibold">هیچ قست و جەلسەیەک تۆمار نەکراوە.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Payment -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
            <i class="bi bi-plus-circle text-primary me-2"></i> تۆمارکرنا جەلسە و قستێ نوو
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body modal-body-custom">
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">هەڵبژارتنا نەخۆشی</label>
            <select name="patient_id" class="form-select form-select-custom" required>
                <option value="">-- دیاربکە --</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">بەروار</label>
            <input type="date" name="session_date" class="form-control form-control-custom" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">کارێ دڤێ جەلسەیێ دا هاتیەکرن</label>
            <input type="text" name="treatment_done" class="form-control form-control-custom" placeholder="نموونە: پاڕزین، قاڵبگرتن..." required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold text-secondary">پارەیێ دایی ($ Paid)</label>
                <input type="number" step="0.01" name="amount_paid" class="form-control form-control-custom text-success fw-bold" value="0.00">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold text-secondary">پارەیێ مایی ($ Remaining)</label>
                <input type="number" step="0.01" name="remaining_balance" class="form-control form-control-custom text-danger fw-bold" value="0.00">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">تێبینی</label>
            <textarea name="notes" class="form-control form-control-custom" rows="3" placeholder="تێبینیێن زێدە..."></textarea>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom">
        <button type="submit" name="add_payment" class="btn btn-add-payment w-100 py-2 fs-6">
            <i class="bi bi-check-circle me-1"></i> تۆمارکرن
        </button>
      </div>
    </form>
  </div>
</div>

<!-- مۆدالی گۆڕینی دراو -->
<div class="modal fade" id="currencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="bi bi-calculator text-primary me-2"></i> کۆنڤێرتەری نرخ و دراو
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-custom">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">نرخی سەرفکردن (100 $ بە دینار):</label>
                    <div class="input-group">
                        <input type="number" id="exchange_rate" class="form-control form-control-custom" value="150000" step="500">
                        <span class="input-group-text bg-light border-1">IQD</span>
                    </div>
                    <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i> دەتوانیت نرخەکە بگۆڕیت بەپێی بازاڕ</small>
                </div>
                <hr class="my-3 opacity-25">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">بڕ بە دۆلار ($):</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-1">$</span>
                        <input type="number" id="calc_usd" class="form-control form-control-custom fw-bold text-primary" placeholder="0.00" step="0.01">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">بڕ بە دیناری عێراقی (IQD):</label>
                    <div class="input-group">
                        <input type="text" id="calc_iqd" class="form-control form-control-custom fw-bold text-success" placeholder="0">
                        <span class="input-group-text bg-light border-1">IQD</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-secondary w-100 rounded-3 py-2" data-bs-dismiss="modal">داخستن</button>
            </div>
        </div>
    </div>
</div>

<!-- جاڤاسکریپتی کاراکردنی حاسبەی گۆڕینی دراو بە شێوەی زووبەزوو (Live Converter) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rateInput = document.getElementById('exchange_rate');
    const usdInput = document.getElementById('calc_usd');
    const iqdInput = document.getElementById('calc_iqd');

    if (rateInput && usdInput && iqdInput) {
        function updateFromUsd() {
            const usd = parseFloat(usdInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 150000;
            const iqd = (usd * rate) / 100;
            iqdInput.value = iqd ? Math.round(iqd).toLocaleString('en-US') : '';
        }

        function updateFromIqd() {
            const iqdClean = iqdInput.value.replace(/[^0-9.]/g, '');
            const iqd = parseFloat(iqdClean) || 0;
            const rate = parseFloat(rateInput.value) || 150000;
            const usd = (iqd / rate) * 100;
            usdInput.value = usd ? usd.toFixed(2) : '';
        }

        usdInput.addEventListener('input', updateFromUsd);
        iqdInput.addEventListener('input', updateFromIqd);
        rateInput.addEventListener('input', updateFromUsd);
    }
});
</script>

<?php include '../includes/footer.php'; ?>