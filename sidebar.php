<?php
$role = $_SESSION['role'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'بکارهێنەر';
$current_page = basename($_SERVER['PHP_SELF']);

// دیاریکردنی ناوی ڕۆڵ بە شێوەیەکی دروست بۆ سەرجەم دەسەڵاتەکان
$role_display = 'سکرتێر';
if ($role === 'doctor') {
    $role_display = 'دکتۆر';
} elseif ($role === 'admin') {
    $role_display = 'بەڕێوەبەر';
}
?>

<!-- CSSی تایبەت بە Sidebar بۆ دیزاینێکی مۆدێرن و پرۆفێشوناڵ -->
<style>
    :root {
        --sidebar-bg: #0f172a;
        --sidebar-card-bg: #1e293b;
        --sidebar-text: #94a3b8;
        --sidebar-hover-bg: #334155;
        --sidebar-active-bg: #0E6BA8;
        --sidebar-active-text: #ffffff;
        --border-color: rgba(255, 255, 255, 0.08);
    }

    .sidebar {
        background-color: var(--sidebar-bg) !important;
        min-height: 100vh;
        color: #f8fafc;
        border-left: 1px solid var(--border-color);
        box-shadow: 4px 0 25px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .clinic-brand-box {
        background: linear-gradient(135deg, rgba(14, 107, 168, 0.15) 0%, rgba(14, 107, 168, 0.05) 100%);
        border: 1px solid rgba(14, 107, 168, 0.2);
        border-radius: 16px;
        padding: 16px 12px;
    }

    .clinic-brand-icon {
        width: 48px;
        height: 48px;
        background: #0E6BA8;
        color: #fff;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.4);
    }

    .user-profile-card {
        background: var(--sidebar-card-bg);
        border-radius: 12px;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
    }

    .user-avatar-initial {
        width: 36px;
        height: 36px;
        background: #38bdf8;
        color: #0f172a;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
    }

    .nav-section-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 700;
        margin-top: 15px;
        margin-bottom: 8px;
        padding-right: 12px;
    }

    .sidebar nav a {
        display: flex;
        align-items: center;
        padding: 11px 14px;
        color: var(--sidebar-text);
        text-decoration: none;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.93rem;
        margin-bottom: 5px;
        transition: all 0.25s ease;
    }

    .sidebar nav a i {
        font-size: 1.15rem;
        transition: transform 0.2s ease;
    }

    .sidebar nav a:hover {
        background-color: var(--sidebar-hover-bg);
        color: #f8fafc;
        transform: translateX(-3px);
    }

    .sidebar nav a:hover i {
        transform: scale(1.1);
    }

    .sidebar nav a.active {
        background-color: var(--sidebar-active-bg);
        color: var(--sidebar-active-text) !important;
        box-shadow: 0 4px 12px rgba(14, 107, 168, 0.35);
        font-weight: 600;
    }

    .sidebar nav a.active i {
        color: #ffffff !important;
    }

    .logout-btn {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171 !important;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .logout-btn:hover {
        background: #ef4444 !important;
        color: #ffffff !important;
    }

    .logout-btn:hover i {
        color: #ffffff !important;
    }

    .sidebar-divider {
        border-top: 1px solid var(--border-color);
        margin: 15px 0;
    }
</style>

<div class="col-md-3 col-lg-2 sidebar p-3 no-print">
    <!-- بەشی ناونیشان و لۆگۆی کلینیک -->
    <div class="clinic-brand-box text-center mb-3">
        <div class="clinic-brand-icon mb-2">
            <i class="bi bi-hospital"></i>
        </div>
        <h6 class="fw-bold mb-0 text-white">کلینیکا ددانان</h6>
    </div>

    <!-- بەشی ناوی بەکارهێنەر و ڕۆڵ -->
    <div class="user-profile-card mb-3 d-flex align-items-center gap-2">
        <div class="user-avatar-initial">
            <?php echo mb_substr(sanitize($full_name), 0, 1, 'UTF-8'); ?>
        </div>
        <div class="overflow-hidden">
            <div class="fw-bold text-white text-truncate style-name" style="font-size: 0.88rem;">
                <?php echo sanitize($full_name); ?>
            </div>
            <small class="text-info d-block" style="font-size: 0.75rem;">
                <i class="bi bi-shield-check me-1"></i><?php echo $role_display; ?>
            </small>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <!-- لیستەی بەشەکان -->
    <nav>
        <?php if ($role === 'secretary' || $role === 'doctor' || $role === 'admin'): ?>
            <div class="nav-section-title">بەشێن سەرەکی</div>

            <a href="../secretary/reminders.php" class="<?php echo $current_page === 'reminders.php' ? 'active' : ''; ?>">
                <i class="bi bi-bell-fill me-2 text-warning"></i> <span>ئاگەهداریێن ئەڤرۆ</span>
            </a>
            <a href="../secretary/appointments.php" class="<?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check me-2 text-primary"></i> <span>ژڤانێن ئەڤرۆ</span>
            </a>
            <a href="../secretary/patients.php" class="<?php echo ($current_page === 'patients.php' || $current_page === 'patient_profile.php') ? 'active' : ''; ?>">
                <i class="bi bi-people me-2 text-info"></i> <span>تۆمارا نەخۆشان</span>
            </a>
            <a href="../secretary/payments.php" class="<?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack me-2 text-success"></i> <span>قست و جەلسە</span>
            </a>
            <a href="../secretary/prescriptions.php" class="<?php echo $current_page === 'prescriptions.php' ? 'active' : ''; ?>">
                <i class="bi bi-capsule me-2 text-info"></i> <span>نۆسخە و دەرمان</span>
            </a>
        <?php endif; ?>

        <?php if ($role === 'doctor' || $role === 'admin'): ?>
            <div class="sidebar-divider"></div>
            <div class="nav-section-title">بەڕێوەبەری</div>

            <a href="../doctor/reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph me-2 text-warning"></i> <span>ڕاپۆرتێن دارایی</span>
            </a>
            <a href="../doctor/users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock me-2 text-info"></i> <span>بەشێ بکارهێنەران</span>
            </a>
            
        <?php endif; ?>

        <div class="sidebar-divider"></div>
        <a href="../logout.php" class="logout-btn mt-2">
            <i class="bi bi-box-arrow-right me-2"></i> <span>دەردەکەڤتن</span>
        </a>
    </nav>
</div>

<!-- کلاسی سەرەکی ناوەڕۆک -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">