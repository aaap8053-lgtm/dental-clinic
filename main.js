/**
 * Dental Clinic Management System - Main JavaScript File
 * تایبەتمەندییەکان: ئەنیمەیشنی UI، گۆڕینی دراو (دۆلار ↔ دینار)، فلتەرکردنی خشتە، و پشتڕاستکردنەوە
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // ==========================================
    // 1. ANIMATIONS & UI ENHANCEMENTS
    // ==========================================
    
    // ئەنیمەیشنی Fade-in بۆ کارتەکان لە کاتی باربوونی لاپەڕەدا
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(15px)';
        card.style.transition = 'all 0.4s ease-out';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 80 * index);
    });

    // سڕینەوەی ئۆتۆماتیکی ئاگادارییەکان (Alerts) پاش 4 چرکە
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'scale(0.95)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ==========================================
    // 2. CURRENCY CONVERTER (USD <-> IQD)
    // ==========================================
    
    const usdInput = document.getElementById('calc_usd');
    const iqdInput = document.getElementById('calc_iqd');
    const exchangeRateInput = document.getElementById('exchange_rate');

    function calculateCurrency(source) {
        if (!usdInput || !iqdInput) return;

        const rate = parseFloat(exchangeRateInput ? exchangeRateInput.value : 1500) || 1500;

        if (source === 'usd') {
            const usdVal = parseFloat(usdInput.value) || 0;
            iqdInput.value = Math.round(usdVal * (rate / 100) * 100).toLocaleString('en-US');
        } else if (source === 'iqd') {
            const rawIqd = iqdInput.value.replace(/,/g, '');
            const iqdVal = parseFloat(rawIqd) || 0;
            usdInput.value = (iqdVal / rate).toFixed(2);
        }
    }

    if (usdInput && iqdInput) {
        usdInput.addEventListener('input', () => calculateCurrency('usd'));
        iqdInput.addEventListener('input', () => calculateCurrency('iqd'));
        if (exchangeRateInput) {
            exchangeRateInput.addEventListener('input', () => calculateCurrency('usd'));
        }
    }

    // ==========================================
    // 3. LIVE TABLE SEARCH FILTER
    // ==========================================
    // بەکارهێنان: زیادکردنی id="tableSearch" بۆ inputی گەڕان لە سەرەوەی هەر خشتەیەک
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // ==========================================
    // 4. CONFIRMATION DIALOG FOR DELETE ACTIONS
    // ==========================================
    // بەکارهێنان: زیادکردنی class="btn-delete" یان data-confirm بۆ دوگمەی سڕینەوە
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // ==========================================
    // 5. PRINT HELPER FUNCTION
    // ==========================================
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });
});