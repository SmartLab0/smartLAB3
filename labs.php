<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز المعامل - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* نافذة تأكيد مخصصة */
        .custom-confirm {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .confirm-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }

        .confirm-content i {
            font-size: 50px;
            color: #ffc107;
            margin-bottom: 15px;
        }

        .confirm-content h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .confirm-content p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .confirm-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .confirm-yes, .confirm-no {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .confirm-yes {
            background: #dc3545;
            color: white;
        }

        .confirm-yes:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .confirm-no {
            background: #6c757d;
            color: white;
        }

        .confirm-no:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $college = $_SESSION['college'];
    $specialization = $_SESSION['specialization'] ?? '';
    
    require_once 'config.php';
    
    // جلب المعامل المتاحة حسب الكلية
    $labs = [];
    if ($conn && $conn->connect_errno === 0) {
        $lab_sql = "SELECT * FROM labs WHERE college = ? AND status = 'active'";
        $stmt = $conn->prepare($lab_sql);
        $stmt->bind_param("s", $college);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $labs[] = $row;
        }
        $stmt->close();
    }
    
    // معالجة الحجز
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_lab'])) {
        $lab_id = $_POST['lab_id'];
        $booking_date = $_POST['booking_date'];
        $time_slot = $_POST['time_slot'];
        
        // تقسيم الوقت بطريقة آمنة
        if (strpos($time_slot, '-') !== false) {
            list($start_time, $end_time) = explode('-', $time_slot);
        } else {
            $booking_error = "صيغة الوقت غير صحيحة";
            $start_time = null;
            $end_time = null;
        }
        
        if ($start_time && $end_time) {
            // التحقق من عدم التعارض
            $check_sql = "SELECT COUNT(*) as count FROM bookings 
                         WHERE lab_id = ? 
                         AND booking_date = ? 
                         AND (
                             (start_time < ? AND end_time > ?) OR
                             (start_time >= ? AND start_time < ?)
                         )";
            
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("isssss", $lab_id, $booking_date, $end_time, $start_time, $start_time, $end_time);
            $stmt->execute();
            $check_result = $stmt->get_result();
            $row = $check_result->fetch_assoc();
            
            if ($row['count'] == 0) {
                // الحجز متاح
                $insert_sql = "INSERT INTO bookings (user_id, lab_id, booking_date, start_time, end_time, student_count) 
                              VALUES (?, ?, ?, ?, ?, 6)";
                
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iisss", $user_id, $lab_id, $booking_date, $start_time, $end_time);
                
                if ($stmt->execute()) {
                    $booking_success = "تم الحجز بنجاح!";
                    
                    // إنشاء تذكرة
                    $booking_id = $stmt->insert_id;
                    $ticket_code = "TICKET-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
                    
                    $ticket_sql = "INSERT INTO tickets (user_id, booking_id, ticket_type, ticket_code) 
                                  VALUES (?, ?, 'current', ?)";
                    $ticket_stmt = $conn->prepare($ticket_sql);
                    $ticket_stmt->bind_param("iis", $user_id, $booking_id, $ticket_code);
                    $ticket_stmt->execute();
                    $ticket_stmt->close();
                }
                $stmt->close();
            } else {
                $booking_error = "هذا الوقت محجوز مسبقاً!";
            }
        }
    }
    
    // جلب محاضرات المستخدم
    $user_lectures = [];
    if ($conn && $conn->connect_errno === 0) {
        $lecture_sql = "SELECT day_of_week, start_time, end_time FROM lectures WHERE user_id = ?";
        $stmt = $conn->prepare($lecture_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $lecture_result = $stmt->get_result();
        
        while ($row = $lecture_result->fetch_assoc()) {
            $day = $row['day_of_week'];
            if (!isset($user_lectures[$day])) {
                $user_lectures[$day] = [];
            }
            $user_lectures[$day][] = [
                'start' => $row['start_time'],
                'end' => $row['end_time']
            ];
        }
        $stmt->close();
    }
    ?>
    
    <!-- الهيدر -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="logo.jpg" alt="شعار الجامعة" class="header-logo">
            </div>
            
            <div class="controls-section">
                <button class="icon-btn" onclick="window.location.href='home.php'">
                    <i class="fas fa-home"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <div class="labs-container">
            <!-- عنوان الصفحة -->
            <div class="page-header">
                <h1 class="page-title">
                    حجز المعامل الدراسية
                </h1>
                <p class="page-subtitle">احجز معمل دراسي في وقت مناسب خارج أوقات المحاضرات</p>
            </div>

            <!-- رسائل النجاح/الخطأ -->
            <?php if (isset($booking_success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $booking_success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($booking_error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $booking_error; ?>
            </div>
            <?php endif; ?>

            <!-- رسائل إلغاء الحجز -->
            <?php if (isset($_GET['cancel_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                تم إلغاء الحجز بنجاح
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['cancel_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                حدث خطأ في إلغاء الحجز
            </div>
            <?php endif; ?>

            <!-- تقويم الحجز -->
            <div class="booking-calendar">
                <div class="calendar-header">
                    <h3>
                        <i class="far fa-calendar-alt"></i>
                        تقويم الحجز
                    </h3>
                    <div class="date-controls">
                        <button id="prevDate" class="nav-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <input type="date" id="selectedDate" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                        <button id="nextDate" class="nav-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>
                </div>

                <!-- الأوقات المتاحة -->
                <div class="time-slots-section">
                    <h4>الأوقات المتاحة (8:00 ص - 3:00 م)</h4>
                    <div class="time-slots" id="timeSlots"></div>
                </div>
            </div>

            <!-- المعامل المتاحة -->
            <div class="available-labs">
                <h3>
                    <i class="fas fa-flask"></i>
                    المعامل المتاحة - <?php echo $college; ?>
                </h3>
                
                <?php if (empty($labs)): ?>
                <div class="no-labs">
                    <i class="fas fa-info-circle"></i>
                    <p>لا توجد معامل متاحة حالياً لكليتك</p>
                </div>
                <?php else: ?>
                <div class="labs-grid">
                    <?php foreach ($labs as $lab): ?>
                    <div class="lab-card" data-lab-id="<?php echo $lab['id']; ?>">
                        <div class="lab-header">
                            <div class="lab-code"><?php echo $lab['lab_code']; ?></div>
                            <div class="lab-status available">متاح</div>
                        </div>
                        
                        <h4 class="lab-name"><?php echo $lab['lab_name']; ?></h4>
                        
                        <div class="lab-details">
                            <div class="detail">
                                <i class="fas fa-building"></i>
                                <span>المبنى: <?php echo $lab['building']; ?></span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-layer-group"></i>
                                <span>الدور: <?php echo $lab['floor']; ?></span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-users"></i>
                                <span>السعة: <?php echo $lab['capacity']; ?> طالب</span>
                            </div>
                        </div>
                        
                        <div class="lab-equipment">
                            <strong>التجهيزات:</strong>
                            <p><?php echo $lab['equipment'] ?: 'غير محدد'; ?></p>
                        </div>
                        
                        <button class="btn-select-lab" data-lab-id="<?php echo $lab['id']; ?>">
                            <i class="fas fa-check"></i>
                            اختيار هذا المعمل
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- نموذج الحجز -->
            <div class="booking-form-container" id="bookingForm" style="display: none;">
                <h3>
                    <i class="fas fa-calendar-check"></i>
                    تأكيد الحجز
                </h3>
                
                <form class="booking-form" method="POST">
                    <input type="hidden" id="selectedLabId" name="lab_id">
                    <input type="hidden" id="selectedBookingDate" name="booking_date">
                    <input type="hidden" id="selectedTimeSlot" name="time_slot">
                    
                    <div class="booking-summary">
                        <div class="summary-item">
                            <strong>المعمل:</strong>
                            <span id="summaryLabName">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>التاريخ:</strong>
                            <span id="summaryDate">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>الوقت:</strong>
                            <span id="summaryTime">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>المدة:</strong>
                            <span>30 دقيقة</span>
                        </div>
                        <div class="summary-item">
                            <strong>عدد الطلاب:</strong>
                            <span>6 طلاب</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="book_lab" class="btn-confirm">
                            <i class="fas fa-check-circle"></i>
                            تأكيد الحجز
                        </button>
                        <button type="button" class="btn-cancel" onclick="hideBookingForm()">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>

            <!-- جدول الحجوزات الحالية -->
            <div class="current-bookings">
                <h3>
                    <i class="fas fa-history"></i>
                    حجوزاتي الحالية
                </h3>
                
                <div class="bookings-table">
                    <table>
                        <thead>
                            <tr>
                                <th>المعمل</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($conn && $conn->connect_errno === 0) {
                                $my_bookings_sql = "SELECT b.*, l.lab_name, l.lab_code 
                                                  FROM bookings b 
                                                  JOIN labs l ON b.lab_id = l.id 
                                                  WHERE b.user_id = ? 
                                                  AND b.booking_date >= CURDATE() 
                                                  ORDER BY b.booking_date, b.start_time";
                                
                                $stmt = $conn->prepare($my_bookings_sql);
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $my_bookings = $stmt->get_result();
                                
                                if ($my_bookings->num_rows > 0) {
                                    while ($booking = $my_bookings->fetch_assoc()) {
                                        $start_time = date("g:i", strtotime($booking['start_time']));
                                        $end_time = date("g:i", strtotime($booking['end_time']));
                                        ?>
                                        <tr>
                                            <td><?php echo $booking['lab_code']; ?> - <?php echo $booking['lab_name']; ?></td>
                                            <td><?php echo $booking['booking_date']; ?></td>
                                            <td><?php echo $start_time; ?> - <?php echo $end_time; ?></td>
                                            <td><span class="status confirmed">مؤكد</span></td>
                                            <td>
                                                <button class="btn-action" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                    إلغاء
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="no-data">لا توجد حجوزات حالية</td></tr>';
                                }
                                $stmt->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        const userLectures = <?php echo json_encode($user_lectures); ?>;
        
        function generateTimeSlots() {
            const slotsContainer = document.getElementById('timeSlots');
            slotsContainer.innerHTML = '';
            
            const selectedDate = document.getElementById('selectedDate').value;
            const dateObj = new Date(selectedDate + 'T12:00:00');
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const selectedDay = dayNames[dateObj.getDay()];
            
            for (let hour = 8; hour < 15; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    const startTime = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                    const endHour = minute === 30 ? hour + 1 : hour;
                    const endMinute = minute === 30 ? '00' : '30';
                    const endTime = `${endHour.toString().padStart(2, '0')}:${endMinute}`;
                    
                    let isLecture = false;
                    if (userLectures[selectedDay]) {
                        const timeMinutes = hour * 60 + minute;
                        for (const lecture of userLectures[selectedDay]) {
                            const [lStartHour, lStartMin] = lecture.start.split(':').map(Number);
                            const [lEndHour, lEndMin] = lecture.end.split(':').map(Number);
                            const lectureStart = lStartHour * 60 + lStartMin;
                            const lectureEnd = lEndHour * 60 + lEndMin;
                            
                            if (timeMinutes >= lectureStart && timeMinutes < lectureEnd) {
                                isLecture = true;
                                break;
                            }
                        }
                    }
                    
                    if (isLecture) continue;
                    
                    const timeSlot = `${startTime}-${endTime}`;
                    const displayHour = hour > 12 ? hour - 12 : hour;
                    const displayPeriod = hour >= 12 ? 'م' : 'ص';
                    
                    const slotElement = document.createElement('div');
                    slotElement.className = 'time-slot';
                    slotElement.dataset.time = timeSlot;
                    slotElement.innerHTML = `
                        <span>${displayHour}:${minute === 0 ? '00' : '30'} ${displayPeriod}</span>
                        <small>30 دقيقة</small>
                    `;
                    
                    slotElement.onclick = function() { selectTimeSlot(this); };
                    slotsContainer.appendChild(slotElement);
                }
            }
            
            if (slotsContainer.children.length === 0) {
                slotsContainer.innerHTML = '<div class="no-slots">لا توجد أوقات متاحة في هذا اليوم</div>';
            }
        }
        
        let selectedTimeSlot = null;
        let selectedLabId = null;
        
        function selectTimeSlot(element) {
            document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
            element.classList.add('selected');
            selectedTimeSlot = element.dataset.time;
            if (selectedLabId) showBookingForm();
        }
        
        document.querySelectorAll('.btn-select-lab').forEach(btn => {
            btn.addEventListener('click', function() {
                selectedLabId = this.dataset.labId;
                const labCard = this.closest('.lab-card');
                const labName = labCard.querySelector('.lab-name').textContent;
                const labCode = labCard.querySelector('.lab-code').textContent;
                
                document.getElementById('selectedLabId').value = selectedLabId;
                document.getElementById('summaryLabName').textContent = `${labCode} - ${labName}`;
                
                if (selectedTimeSlot) showBookingForm();
            });
        });
        
        function showBookingForm() {
            const selectedDate = document.getElementById('selectedDate').value;
            const dateParts = selectedDate.split('-');
            
            if (dateParts.length === 3) {
                const year = dateParts[0];
                const month = dateParts[1];
                const day = dateParts[2];
                
                document.getElementById('selectedBookingDate').value = `${year}-${month}-${day}`;
                
                const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                const monthIndex = parseInt(month) - 1;
                const displayDate = `${day} ${months[monthIndex]} ${year}`;
                
                const [startTime, endTime] = selectedTimeSlot.split('-');
                const startHour = parseInt(startTime.split(':')[0]);
                const endHour = parseInt(endTime.split(':')[0]);
                
                const startDisplay = startHour >= 12 ? `${startHour - 12}:${startTime.split(':')[1]} م` : `${startHour}:${startTime.split(':')[1]} ص`;
                const endDisplay = endHour >= 12 ? `${endHour - 12}:${endTime.split(':')[1]} م` : `${endHour}:${endTime.split(':')[1]} ص`;
                
                document.getElementById('summaryDate').textContent = displayDate;
                document.getElementById('summaryTime').textContent = `${startDisplay} - ${endDisplay}`;
                document.getElementById('selectedTimeSlot').value = selectedTimeSlot;
                
                document.getElementById('bookingForm').style.display = 'block';
                window.scrollTo({ top: document.getElementById('bookingForm').offsetTop, behavior: 'smooth' });
            }
        }
        
        function hideBookingForm() {
            document.getElementById('bookingForm').style.display = 'none';
            selectedTimeSlot = null;
            selectedLabId = null;
            document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
        }
        
        document.getElementById('prevDate').addEventListener('click', function() {
            const input = document.getElementById('selectedDate');
            const date = new Date(input.value + 'T12:00:00');
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            input.value = `${year}-${month}-${day}`;
            hideBookingForm();
            generateTimeSlots();
        });
        
        document.getElementById('nextDate').addEventListener('click', function() {
            const input = document.getElementById('selectedDate');
            const date = new Date(input.value + 'T12:00:00');
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            input.value = `${year}-${month}-${day}`;
            hideBookingForm();
            generateTimeSlots();
        });
        
        document.getElementById('selectedDate').addEventListener('change', function() {
            hideBookingForm();
            generateTimeSlots();
        });
        
        // دوال إلغاء الحجز
       function cancelBooking(bookingId) {
    const confirmDiv = document.createElement('div');
    confirmDiv.className = 'custom-confirm';
    confirmDiv.innerHTML = `
        <div class="confirm-content">
            <h3>تأكيد الإلغاء</h3>
            <p>هل أنت متأكد من إلغاء هذا الحجز؟</p>
            <div class="confirm-actions">
                <button class="confirm-yes" onclick="proceedCancel(${bookingId})">نعم، إلغاء</button>
                <button class="confirm-no" onclick="closeConfirm()">لا، تراجع</button>
            </div>
        </div>
    `;
    document.body.appendChild(confirmDiv);
}

        function proceedCancel(bookingId) {
            closeConfirm();
            window.location.href = `cancel_booking.php?id=${bookingId}`;
        }

        function closeConfirm() {
            const confirmDiv = document.querySelector('.custom-confirm');
            if (confirmDiv) confirmDiv.remove();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            generateTimeSlots();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('selectedDate').min = today;
        });
    </script>

    <style>
        .no-slots {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            background: #f8d7da;
            color: #721c24;
            border-radius: var(--radius-md);
            font-weight: 600;
        }
        
        .labs-container { max-width: 1200px; margin: 0 auto; }
        .page-header { text-align: center; margin-bottom: 40px; }
        .page-title { color: var(--dark-green); font-size: 32px; margin-bottom: 10px; }
        .page-subtitle { color: var(--text-secondary); font-size: 16px; }
        
        .alert { padding: 15px; border-radius: var(--radius-md); margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .booking-calendar { background: white; border-radius: var(--radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-md); }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .date-controls { display: flex; align-items: center; gap: 10px; }
        #selectedDate { padding: 8px 12px; border: 2px solid var(--border-color); border-radius: var(--radius-md); font-family: 'Cairo', sans-serif; font-size: 16px; }
        .nav-btn { background: var(--primary-green); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        
        .time-slots-section { margin-top: 20px; }
        .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
        .time-slot { background: var(--light-gray); border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .time-slot:hover { border-color: var(--primary-green); }
        .time-slot.selected { background: var(--primary-green); color: white; border-color: var(--dark-green); }
        
        .available-labs { background: white; border-radius: var(--radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-md); }
        .labs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .lab-card { border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; transition: all 0.3s ease; }
        .lab-card:hover { border-color: var(--primary-green); transform: translateY(-5px); }
        
        .lab-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .lab-code { background: var(--primary-green); color: white; padding: 5px 10px; border-radius: var(--radius-sm); font-weight: 600; }
        .lab-status { font-size: 12px; padding: 3px 8px; border-radius: var(--radius-sm); }
        .lab-status.available { background: #d4edda; color: #155724; }
        .lab-name { color: var(--dark-green); margin-bottom: 15px; }
        
        .lab-details { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .lab-details .detail { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 14px; }
        .lab-details .detail i { color: var(--primary-green); }
        
        .lab-equipment { font-size: 14px; color: var(--text-primary); margin-bottom: 15px; }
        .btn-select-lab { width: 100%; background: var(--primary-green); color: white; border: none; padding: 10px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; }
        
        .booking-form-container { background: white; border-radius: var(--radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-md); border: 2px solid var(--primary-green); }
        .booking-summary { background: var(--light-gray); padding: 20px; border-radius: var(--radius-md); margin: 20px 0; }
        .summary-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
        .summary-item:last-child { border-bottom: none; }
        
        .current-bookings { background: white; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow-md); }
        .bookings-table { overflow-x: auto; margin-top: 20px; }
        .bookings-table table { width: 100%; border-collapse: collapse; }
        .bookings-table th { background: var(--primary-green); color: white; padding: 12px; text-align: right; }
        .bookings-table td { padding: 12px; border-bottom: 1px solid var(--border-color); }
        .bookings-table tr:hover { background: var(--light-gray); }
        
        .status { padding: 4px 8px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; }
        .status.confirmed { background: #d4edda; color: #155724; }
        .btn-action { background: var(--error-color); color: white; border: none; padding: 6px 12px; border-radius: var(--radius-sm); cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 5px; }
        
        .btn-confirm, .btn-cancel { padding: 12px 24px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-confirm { background: var(--success-color); color: white; }
        .btn-cancel { background: var(--medium-gray); color: var(--text-primary); }
        .form-actions { display: flex; gap: 15px; justify-content: center; }
        
        @media (max-width: 768px) {
            .calendar-header { flex-direction: column; gap: 15px; }
            .labs-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
            .bookings-table { font-size: 14px; }
        }
    </style>
</body>
</html>