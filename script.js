// script.js

document.addEventListener('DOMContentLoaded', function () {

    /* ============ تسجيل الخروج ============ */
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('هل تريد تسجيل الخروج؟')) {
                window.location.href = 'logout.php';
            }
        });
    }

    /* ============ التنبيهات ============ */
    const notificationBtn = document.getElementById('notificationBtn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const dropdown = this.closest('.dropdown');
            if (!dropdown) return;
            const menu = dropdown.querySelector('.dropdown-menu');
            if (!menu) return;
            menu.style.display =
                menu.style.display === 'block' ? 'none' : 'block';
        });
    }

    /* ============ التقويم ============ */
    const monthSelect   = document.getElementById('monthSelect');
    const yearSelect    = document.getElementById('yearSelect');
    const calendarGrid  = document.getElementById('calendarGrid');

    if (!monthSelect || !yearSelect || !calendarGrid) {
        console.log("calendar elements not found");
    } else {

        /* توليد السنوات (مثلاً من 2024 إلى 2030) */
        yearSelect.innerHTML = '';
        for (let y = 2026; y <= 2035; y++) {

            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }

        /* السنة الافتراضية 2026 */
        yearSelect.value = "2026";

        /* ✅ تأكيد أن الشهر له قيمة عند أول تحميل */
        if (monthSelect.value === "") {
            monthSelect.value = String(new Date().getMonth());
        }

        /*
          هذه المصفوفة لاحقًا نربطها من PHP
          الآن فاضية = لا تظهر دوائر خضراء
        */
        const bookedDates = [];

        function renderCalendar() {

            // ✅ الشهر من value (عندك values 0..11 في home.php)
            const month = parseInt(monthSelect.value, 10);
            const year  = parseInt(yearSelect.value, 10);

            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDay = new Date(year, month, 1).getDay();

            let html = '';

            html += `<div class="calendar-week">`;
            const names = ['ح','ن','ث','ر','خ','ج','س'];
            names.forEach(n => {
                html += `<div class="week-day">${n}</div>`;
            });
            html += `</div>`;

            html += `<div class="calendar-days">`;

            for (let i = 0; i < firstDay; i++) {
                html += `<div class="day empty"></div>`;
            }

            for (let d = 1; d <= daysInMonth; d++) {

                const dateStr =
                    year + '-' +
                    String(month + 1).padStart(2, '0') + '-' +
                    String(d).padStart(2, '0');

                const isBooked = bookedDates.includes(dateStr);
                const canBook = (year === 2026);

                html += `
                    <div class="day ${isBooked ? 'booked' : ''} ${canBook ? '' : 'disabled'}"
                         data-date="${dateStr}">
                        <span class="day-number">${d}</span>
                    </div>
                `;
            }

            html += `</div>`;

            calendarGrid.innerHTML = html;
        }

        monthSelect.addEventListener('change', renderCalendar);
        yearSelect.addEventListener('change', renderCalendar);

        renderCalendar();

        /* أزرار التنقل بين الشهور */
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function () {
                let m = parseInt(monthSelect.value, 10);
                if (m > 0) {
                    monthSelect.value = String(m - 1);
                } else {
                    monthSelect.value = "11";
                    yearSelect.value = String(parseInt(yearSelect.value, 10) - 1);
                }
                renderCalendar();
            });
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function () {
                let m = parseInt(monthSelect.value, 10);
                if (m < 11) {
                    monthSelect.value = String(m + 1);
                } else {
                    monthSelect.value = "0";
                    yearSelect.value = String(parseInt(yearSelect.value, 10) + 1);
                }
                renderCalendar();
            });
        }

    } // ✅ يقفل else حق التقويم

    /* إغلاق الدروب داون */
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

}); // ✅ يقفل DOMContentLoaded
