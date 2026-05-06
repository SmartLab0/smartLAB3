<?php
// بداية الملف - جلب الإعدادات
require_once 'config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['fullname'];
$student_id = $_SESSION['student_id'];
$college = $_SESSION['college'];
$email = $_SESSION['email'];
$phone = $_SESSION['phone'];
$specialization = isset($_SESSION['specialization']) ? $_SESSION['specialization'] : '';

$notification_count = 0;
$notification_details = [];

// جلب حجوزات المستخدم للتقويم
$user_bookings = [];

if (isset($conn) && $conn && $conn->connect_errno === 0) {
    // جلب حجوزات المستخدم
    $bookings_sql = "SELECT 
                        DATE(b.booking_date) as booking_date,
                        COUNT(*) as booking_count,
                        GROUP_CONCAT(l.lab_name SEPARATOR ', ') as labs
                    FROM bookings b
                    JOIN labs l ON b.lab_id = l.id
                    WHERE b.user_id = ?
                    GROUP BY DATE(b.booking_date)
                    ORDER BY b.booking_date";
    
    if ($stmt = $conn->prepare($bookings_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
        
        while ($row = $bookings_result->fetch_assoc()) {
            $user_bookings[$row['booking_date']] = [
                'count' => $row['booking_count'],
                'labs' => $row['labs']
            ];
        }
        $stmt->close();
    }
    
    // جلب تفاصيل التنبيهات
    $notification_sql = "SELECT b.id, b.start_time, l.lab_name 
                        FROM bookings b 
                        JOIN labs l ON b.lab_id = l.id 
                        WHERE b.user_id = ? 
                        AND b.booking_date = CURDATE() 
                        AND b.start_time BETWEEN CURTIME() AND ADDTIME(CURTIME(), '00:30:00')
                        ORDER BY b.start_time";
    
    if ($stmt = $conn->prepare($notification_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notification_result = $stmt->get_result();
        
        $notification_count = $notification_result->num_rows;
        while ($row = $notification_result->fetch_assoc()) {
            $notification_details[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصفحة الرئيسية - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* نافذة من نحن المنبثقة */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            animation: slideDown 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            color: #017d75;
            font-size: 22px;
            margin: 0;
        }
        
        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 30px 20px;
            text-align: center;
        }
        
        .modal-body p {
            color: #495057;
            font-size: 18px;
            line-height: 1.8;
            margin: 0;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        
        .modal-btn {
            background: #017d75;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .modal-btn:hover {
            background: #005f59;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* باقي التنسيقات */
        .calendar-section {
            margin-top: 40px;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .section-title {
            color: #017d75;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
        }
        
        .calendar-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .calendar-current-month {
            font-size: 22px;
            font-weight: 600;
            color: #005f59;
        }
        
        .calendar-nav-buttons {
            display: flex;
            gap: 10px;
        }
        
        .calendar-nav-btn {
            background: white;
            border: 2px solid #e9ecef;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #495057;
            transition: all 0.3s ease;
        }
        
        .calendar-nav-btn:hover {
            background: #017d75;
            color: white;
            border-color: #017d75;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 25px;
        }
        
        .calendar-day-header {
            text-align: center;
            padding: 12px 5px;
            background: #e9ecef;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .calendar-day {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px 5px;
            text-align: center;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }
        
        .calendar-day.past {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        
        .calendar-day.past:hover {
            transform: none;
            box-shadow: none;
        }
        
        .calendar-day:not(.past):hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        
        .calendar-day-number {
            font-size: 18px;
            font-weight: 600;
            color: #495057;
        }
        
        .calendar-day.booked .calendar-day-number {
            color: #017d75;
        }
        
        .calendar-day.booked {
            background: #e8f4f3;
            border-color: #017d75;
        }
        
        .calendar-day.today {
            background: #fff3cd;
            border-color: #ffc107;
        }
        
        .calendar-day.today .calendar-day-number {
            color: #856404;
        }
        
        .calendar-day.other-month {
            opacity: 0.5;
            background: #f8f9fa;
        }
        
        .calendar-day.empty {
            background: transparent;
            border-color: transparent;
            cursor: default;
        }
        
        .booking-count {
            background: #017d75;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 5px;
        }
        
        .legend-color.booked {
            background: #e8f4f3;
            border: 2px solid #017d75;
        }
        
        .legend-color.today {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        
        .legend-color.available {
            background: white;
            border: 2px solid #e9ecef;
        }
        
        .legend-color.past {
            background: #f5f5f5;
            border: 2px solid #cccccc;
        }
        
        @media (max-width: 768px) {
            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
            }
            
            .calendar-day {
                min-height: 60px;
                padding: 10px 3px;
            }
            
            .calendar-day-number {
                font-size: 16px;
            }
            
            .calendar-day-content {
                font-size: 10px;
            }
            
            .calendar-legend {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
        }
    </style>
</head>
<body>
<!-- الهيدر -->
<header class="main-header">
    <div class="header-container">
        <div class="logo-section">
            <img src="logo.jpg" alt="شعار الجامعة" class="header-logo">
        </div>

        <div class="controls-section">
            <!-- أيقونة التنبيهات - معدلة: دائمة التفعيل -->
            <div class="dropdown">
                <button class="icon-btn" id="notificationBtn" onclick="toggleNotificationDropdown()">
                    <i class="far fa-bell"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu" id="notificationDropdown">
                    <div class="dropdown-header">التنبيهات</div>
                    <?php if ($notification_count > 0): ?>
                        <?php foreach ($notification_details as $detail): 
                            $time = date("g:i", strtotime($detail['start_time']));
                        ?>
                            <div class="dropdown-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <div>حجز معمل <?php echo $detail['lab_name']; ?> الساعة <?php echo $time; ?></div>
                                    <small>بعد 30 دقيقة</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dropdown-item no-notifications">
                            لا توجد تنبيهات حالياً
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- الإعدادات -->
            <div class="dropdown">
                <button class="icon-btn" id="settingsBtn" onclick="toggleSettingsDropdown()">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <div class="dropdown-menu" id="settingsDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="far fa-user-circle"></i>
                        الملف الشخصي
                    </a>
                    <a href="javascript:void(0)" class="dropdown-item" onclick="openAboutModal()">
                        <i class="far fa-info-circle"></i>
                        من نحن
                    </a>
                    <div class="dropdown-header"></div>
                    <a href="logout.php" class="dropdown-item logout" id="logoutBtn">
                        <i class="far fa-sign-out-alt"></i>
                        تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="main-content">
    <!-- بطاقة معلومات الطالب -->
    <section class="student-card-section">
        <div class="student-profile-card">
            <div class="student-avatar">
                <a href="profile.php" class="avatar-link">
                    <div class="avatar-container">
                        <img src="user2.png" alt="صورة الطالب" class="avatar-image">
                    </div>
                </a>
            </div>

            <div class="student-info">
                <h2 class="student-name"><?php echo htmlspecialchars($full_name); ?></h2>
                <div class="student-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-id-card"></i>
                        <div class="detail-content">
                            <span class="detail-label">الرقم الجامعي</span>
                            <span class="detail-value"><?php echo htmlspecialchars($student_id); ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <div class="detail-content">
                            <span class="detail-label">الكلية</span>
                            <span class="detail-value"><?php echo htmlspecialchars($college); ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <i class="fa-solid fa-book"></i>
                        <div class="detail-content">
                            <span class="detail-label">التخصص</span>
                            <span class="detail-value"><?php echo htmlspecialchars($specialization ?: 'غير محدد'); ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <i class="fa-solid fa-envelope"></i>
                        <div class="detail-content">
                            <span class="detail-label">البريد الإلكتروني</span>
                            <span class="detail-value"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- الخدمات -->
    <section class="services-section">
        <h2 class="section-title">
            <i class="fa-solid fa-table-cells"></i>
            الخدمات المتاحة
        </h2>

        <div class="services-grid">
            <a href="labs.php" class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-laptop"></i>
                </div>
                <h3 class="service-title">حجز المعامل</h3>
                <p class="service-description">
                    احجز معمل دراسي في وقت مناسب خارج أوقات المحاضرات
                </p>
            </a>

            <a href="tickets.php" class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3 class="service-title">التذاكر</h3>
                <p class="service-description">
                    عرض تذاكر الحجز الحالية والفائتة وحالة الحضور
                </p>
            </a>

            <a href="chatbot.php" class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h3 class="service-title">المساعد الذكي</h3>
                <p class="service-description">
                    اسأل عن التجارب أو الأكواد واحصل على إجابات فورية
                </p>
            </a>

            <a href="report.php" class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <h3 class="service-title">إبلاغ عن عطل</h3>
                <p class="service-description">
                    بلغ عن أي عطل في المعامل لسرعة الصيانة
                </p>
            </a>
        </div>
    </section>

    <!-- التقويم -->
    <section class="calendar-section">
        <h2 class="section-title">
            <i class="far fa-calendar-alt"></i>
            تقويم الحجوزات
        </h2>

        <div class="calendar-container">
            <!-- رأس التقويم -->
            <div class="calendar-header">
                <div class="calendar-nav-buttons">
                    <button class="calendar-nav-btn" id="prevMonth">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="calendar-nav-btn" id="todayBtn">
                        <i class="fas fa-calendar-day"></i>
                    </button>
                    <button class="calendar-nav-btn" id="nextMonth">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
                
                <div class="calendar-current-month" id="currentMonth">
                </div>
                
                <div class="calendar-nav-buttons">
                    <button class="calendar-nav-btn" onclick="window.location.href='labs.php'">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="calendar-nav-btn" onclick="window.location.href='tickets.php'">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>

            <!-- أيام الأسبوع -->
            <div class="calendar-grid" id="calendarDaysHeader">
                <div class="calendar-day-header">السبت</div>
                <div class="calendar-day-header">الأحد</div>
                <div class="calendar-day-header">الاثنين</div>
                <div class="calendar-day-header">الثلاثاء</div>
                <div class="calendar-day-header">الأربعاء</div>
                <div class="calendar-day-header">الخميس</div>
                <div class="calendar-day-header">الجمعة</div>
            </div>

            <!-- أيام الشهر -->
            <div class="calendar-grid" id="calendarGrid">
            </div>

            <!-- وسيلة الإيضاح -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <span class="legend-color today"></span>
                    <span>اليوم</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color booked"></span>
                    <span>يوم بحجز</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color available"></span>
                    <span>متاح للحجز</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color past"></span>
                    <span>غير متاح (تاريخ سابق)</span>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- نافذة من نحن المنبثقة -->
<div id="aboutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>منصة سمارت لاب</h3>
            <span class="close-modal" onclick="closeAboutModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>منصة ذكية صممت خصيصاً لطلاب جامعة الأمير سطام بن عبدالعزيز لتسهيل إجراءات حجز المعامل الدراسية. نوفر لك مساعداً ذكياً يجيب عن أسئلتك البرمجية، وتذاكر إلكترونية لمتابعة حجوزاتك، ونظاماً سريعاً للإبلاغ عن الأعطال. كل هذا في بيئة سلسة وآمنة.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn" onclick="closeAboutModal()">إغلاق</button>
        </div>
    </div>
</div>

<script>
// ==================== المتغيرات العامة ====================
let currentDate = new Date();
let userBookings = <?php echo json_encode($user_bookings); ?>;
let monthNames = [
    "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو",
    "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"
];

// ==================== دوال القوائم المنسدلة ====================
function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    // إغلاق القوائم الأخرى
    document.getElementById('settingsDropdown').style.display = 'none';
    // تبديل حالة القائمة الحالية
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function toggleSettingsDropdown() {
    const dropdown = document.getElementById('settingsDropdown');
    // إغلاق القوائم الأخرى
    document.getElementById('notificationDropdown').style.display = 'none';
    // تبديل حالة القائمة الحالية
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// إغلاق القوائم عند النقر خارجها
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.getElementById('notificationDropdown').style.display = 'none';
        document.getElementById('settingsDropdown').style.display = 'none';
    }
});

// ==================== دوال نافذة من نحن ====================
function openAboutModal() {
    document.getElementById('aboutModal').style.display = 'flex';
    // إغلاق القوائم المنسدلة
    document.getElementById('notificationDropdown').style.display = 'none';
    document.getElementById('settingsDropdown').style.display = 'none';
}

function closeAboutModal() {
    document.getElementById('aboutModal').style.display = 'none';
}

// إغلاق النافذة إذا ضغط خارجها
window.onclick = function(event) {
    const modal = document.getElementById('aboutModal');
    if (event.target === modal) {
        closeAboutModal();
    }
}

// ==================== التحقق إذا كان التاريخ سابق ====================
function isPastDate(date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return date < today;
}

// ==================== عرض التقويم ====================
function renderCalendar() {
    const calendarGrid = document.getElementById('calendarGrid');
    const currentMonthElement = document.getElementById('currentMonth');
    
    // تحديث عنوان الشهر
    const currentMonth = monthNames[currentDate.getMonth()];
    const currentYear = currentDate.getFullYear();
    currentMonthElement.textContent = `${currentMonth} ${currentYear}`;
    
    // حساب اليوم الأول من الشهر
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();
    
    // إعادة حساب ليتناسب مع التقويم العربي
    let startingDayAdjusted = startingDay === 6 ? 0 : startingDay + 1;
    
    // حساب عدد الأيام من الشهر السابق
    const prevMonthLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
    
    // مسح التقويم القديم
    calendarGrid.innerHTML = '';
    
    // إضافة أيام الشهر السابق
    for (let i = startingDayAdjusted; i > 0; i--) {
        const dayNumber = prevMonthLastDay - i + 1;
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month empty';
        dayElement.innerHTML = `<div class="calendar-day-number">${dayNumber}</div>`;
        calendarGrid.appendChild(dayElement);
    }
    
    // إضافة أيام الشهر الحالي
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
        const dateString = formatDate(currentDay);
        
        let dayClasses = 'calendar-day';
        let dayContent = '';
        
        // التحقق إذا كان اليوم هو اليوم الحالي
        const isToday = currentDay.toDateString() === new Date().toDateString();
        if (isToday) {
            dayClasses += ' today';
        }
        
        // التحقق إذا كان هذا التاريخ سابق
        const isPast = isPastDate(currentDay);
        if (isPast && !isToday) {
            dayClasses += ' past';
        }
        
        // التحقق إذا كان هناك حجز في هذا اليوم
        const hasBooking = userBookings.hasOwnProperty(dateString);
        if (hasBooking) {
            dayClasses += ' booked';
            
            const bookingInfo = userBookings[dateString];
            dayContent = `<div class="booking-count">${bookingInfo.count} حجز</div>`;
            
            if (bookingInfo.count <= 3) {
                const labs = bookingInfo.labs.split(', ');
                dayContent += `<div class="calendar-day-content">${labs.slice(0, 2).join(', ')}</div>`;
            }
        }
        
        const dayElement = document.createElement('div');
        dayElement.className = dayClasses;
        dayElement.innerHTML = `
            <div class="calendar-day-number">${day}</div>
            ${dayContent}
        `;
        
        dayElement.onclick = function() {
            if (isPast && !isToday) return;
            
            if (hasBooking) {
                window.location.href = `tickets.php?date=${dateString}`;
            } else {
                window.location.href = `labs.php?date=${dateString}`;
            }
        };
        
        calendarGrid.appendChild(dayElement);
    }
    
    // إضافة أيام الشهر التالي
    const totalCells = 42;
    const currentCells = startingDayAdjusted + daysInMonth;
    const remainingCells = totalCells - currentCells;
    
    for (let i = 1; i <= remainingCells; i++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month empty';
        dayElement.innerHTML = `<div class="calendar-day-number">${i}</div>`;
        calendarGrid.appendChild(dayElement);
    }
}

// ==================== وظائف مساعدة ====================
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function changeMonth(offset) {
    currentDate.setMonth(currentDate.getMonth() + offset);
    renderCalendar();
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
}

// ==================== التهيئة ====================
document.addEventListener('DOMContentLoaded', function() {
    // تعيين أزرار التحكم
    document.getElementById('prevMonth').onclick = () => changeMonth(-1);
    document.getElementById('nextMonth').onclick = () => changeMonth(1);
    document.getElementById('todayBtn').onclick = goToToday;
    
    // عرض التقويم الأولي
    renderCalendar();
});
</script>
</body>
</html>