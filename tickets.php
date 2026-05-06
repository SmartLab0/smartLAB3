<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التذاكر - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['fullname'];
    $student_id = $_SESSION['student_id'];
    
    // بيانات التذاكر
    $current_tickets = [];
    $past_tickets = [];
    
    @include 'config.php';
    
    if (isset($conn) && $conn && $conn->connect_errno === 0) {
        // التذاكر الحالية (لم يحن وقتها بعد)
        $current_sql = "SELECT t.*, b.booking_date, b.start_time, b.end_time, l.lab_code, l.lab_name 
                       FROM tickets t 
                       LEFT JOIN bookings b ON t.booking_id = b.id 
                       LEFT JOIN labs l ON b.lab_id = l.id 
                       WHERE t.user_id = ? 
                       AND t.ticket_type = 'current'
                       AND (b.booking_date > CURDATE() OR (b.booking_date = CURDATE() AND b.start_time > CURTIME()))
                       AND t.status = 'pending'
                       ORDER BY b.booking_date, b.start_time";
        
        $stmt = $conn->prepare($current_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $current_result = $stmt->get_result();
        
        while ($row = $current_result->fetch_assoc()) {
            $current_tickets[] = $row;
        }
        $stmt->close();
        
        // التذاكر الفائتة (اللي مضى وقتها)
        $past_sql = "SELECT t.*, b.booking_date, b.start_time, b.end_time, l.lab_code, l.lab_name 
                    FROM tickets t 
                    LEFT JOIN bookings b ON t.booking_id = b.id 
                    LEFT JOIN labs l ON b.lab_id = l.id 
                    WHERE t.user_id = ? 
                    AND (
                        t.ticket_type = 'past' 
                        OR (b.booking_date < CURDATE())
                        OR (b.booking_date = CURDATE() AND b.start_time < CURTIME())
                        OR t.status != 'pending'
                    )
                    ORDER BY b.booking_date DESC, b.start_time DESC";
        
        $stmt = $conn->prepare($past_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $past_result = $stmt->get_result();
        
        while ($row = $past_result->fetch_assoc()) {
            $past_tickets[] = $row;
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
        <div class="tickets-container">
            <!-- عنوان الصفحة (بدون إيموجي) -->
            <div class="page-header">
                <h1 class="page-title">
                    تذاكر الحجوزات
                </h1>
                <p class="page-subtitle">عرض تذاكر الحجز الحالية والفائتة وحالة الحضور</p>
            </div>

            <!-- بطاقة المستخدم -->
            <div class="user-badge">
                <div class="user-info">
                    <i class="far fa-user-circle"></i>
                    <div>
                        <h3><?php echo htmlspecialchars($full_name); ?></h3>
                        <p>الرقم الجامعي: <?php echo htmlspecialchars($student_id); ?></p>
                    </div>
                </div>
                <div class="ticket-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo count($current_tickets); ?></span>
                        <span class="stat-label">حالية</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo count($past_tickets); ?></span>
                        <span class="stat-label">فائتة</span>
                    </div>
                </div>
            </div>

            <!-- التذاكر الحالية -->
            <section class="tickets-section current-tickets">
                <div class="section-header">
                    <h2>التذاكر الحالية</h2>
                    <span class="badge"><?php echo count($current_tickets); ?></span>
                </div>
                
                <?php if (empty($current_tickets)): ?>
                <div class="no-tickets">
                    <i class="far fa-calendar-times"></i>
                    <h3>لا توجد تذاكر حالية</h3>
                    <p>يمكنك حجز معمل من صفحة <a href="labs.php">حجز المعامل</a></p>
                </div>
                <?php else: ?>
                <div class="tickets-grid">
                    <?php foreach ($current_tickets as $ticket): ?>
                    <div class="ticket-card current" id="ticket-<?php echo $ticket['id']; ?>">
                        <div class="ticket-header">
                            <div class="ticket-id">#<?php echo $ticket['ticket_code']; ?></div>
                            <div class="ticket-actions">
                                <button class="btn-cancel" onclick="cancelTicket(<?php echo $ticket['id']; ?>, this)">
                                    <i class="fas fa-times"></i>
                                    إلغاء
                                </button>
                            </div>
                        </div>
                        
                        <div class="ticket-body">
                            <?php if ($ticket['lab_code']): ?>
                            <div class="ticket-info">
                                <i class="fas fa-flask"></i>
                                <div>
                                    <span class="label">المعمل</span>
                                    <span class="value"><?php echo $ticket['lab_code']; ?> - <?php echo $ticket['lab_name']; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ticket['booking_date']): ?>
                            <div class="ticket-info">
                                <i class="far fa-calendar"></i>
                                <div>
                                    <span class="label">التاريخ</span>
                                    <span class="value"><?php echo $ticket['booking_date']; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ticket['start_time'] && $ticket['end_time']): ?>
                            <div class="ticket-info">
                                <i class="far fa-clock"></i>
                                <div>
                                    <span class="label">الوقت</span>
                                    <?php
                                    $start_time = date("g:i", strtotime($ticket['start_time']));
                                    $end_time = date("g:i", strtotime($ticket['end_time']));
                                    ?>
                                    <span class="value"><?php echo $start_time; ?> - <?php echo $end_time; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="ticket-info">
                                <i class="fas fa-qrcode"></i>
                                <div>
                                    <span class="label">كود التذكرة</span>
                                    <span class="value code"><?php echo $ticket['ticket_code']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ticket-footer">
                            <div class="instructions">
                                <p><i class="fas fa-info-circle"></i> اعرض هذا الكود للمشرف قبل دخول المعمل</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- التذاكر الفائتة -->
            <section class="tickets-section past-tickets">
                <div class="section-header">
                    <h2>التذاكر الفائتة</h2>
                    <span class="badge"><?php echo count($past_tickets); ?></span>
                </div>
                
                <?php if (empty($past_tickets)): ?>
                <div class="no-tickets">
                    <i class="far fa-history"></i>
                    <h3>لا توجد تذاكر فائتة</h3>
                </div>
                <?php else: ?>
                <div class="tickets-table">
                    <table>
                        <thead>
                            <tr>
                                <th>التذكرة</th>
                                <th>المعمل</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحضور</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past_tickets as $ticket): ?>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($ticket['status']) {
                                case 'attended':
                                    $status_class = 'attended';
                                    $status_text = 'حاضر';
                                    break;
                                case 'absent':
                                    $status_class = 'absent';
                                    $status_text = 'غائب';
                                    break;
                                default:
                                    $status_class = 'pending';
                                    $status_text = 'لم يتم التحقق';
                            }
                            
                            $start_time = $ticket['start_time'] ? date("g:i", strtotime($ticket['start_time'])) : '-';
                            $end_time = $ticket['end_time'] ? date("g:i", strtotime($ticket['end_time'])) : '-';
                            ?>
                            <tr>
                                <td class="ticket-code">#<?php echo $ticket['ticket_code']; ?></td>
                                <td>
                                    <?php if ($ticket['lab_code']): ?>
                                    <?php echo $ticket['lab_code']; ?>
                                    <?php else: ?>
                                    <span class="unknown">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $ticket['booking_date'] ?: '-'; ?></td>
                                <td><?php echo $start_time; ?> - <?php echo $end_time; ?></td>
                                <td>
                                    <span class="status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>

            <!-- كيفية الاستخدام -->
            <section class="instructions-section">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    كيفية استخدام التذاكر
                </h2>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>احصل على التذكرة</h4>
                            <p>بعد الحجز الناجح، يتم إنشاء تذكرة تلقائياً</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>احفظ كود التذكرة</h4>
                            <p>الكود فريد ويتكون من أحرف وأرقام</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>اعرض الكود للمشرف</h4>
                            <p>قبل دخول المعمل، اعرض الكود للمشرف</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>التأكيد والحضور</h4>
                            <p>سيتم تسجيل حضورك تلقائياً بعد التحقق</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        // إلغاء التذكرة
        function cancelTicket(ticketId, element) {
            if (!confirm('هل تريد إلغاء هذه التذكرة؟')) {
                return;
            }
            
            fetch(`cancel_ticket.php?id=${ticketId}`)
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        const ticketCard = element.closest('.ticket-card');
                        ticketCard.style.transition = 'all 0.3s';
                        ticketCard.style.opacity = '0';
                        ticketCard.style.height = '0';
                        ticketCard.style.padding = '0';
                        ticketCard.style.margin = '0';
                        
                        setTimeout(() => {
                            ticketCard.remove();
                            updateStats();
                        }, 300);
                        
                        alert('تم إلغاء التذكرة بنجاح');
                    } else {
                        alert('حدث خطأ في الإلغاء');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('فشل الاتصال بالخادم');
                });
        }

        // تحديث الإحصائيات
        function updateStats() {
            const currentTickets = document.querySelectorAll('.current-tickets .ticket-card').length;
            const pastTickets = document.querySelectorAll('.past-tickets tbody tr').length;
            
            document.querySelector('.current-tickets .badge').textContent = currentTickets;
            document.querySelector('.past-tickets .badge').textContent = pastTickets;
            document.querySelector('.user-badge .stat-number:first-child').textContent = currentTickets;
            document.querySelector('.user-badge .stat-number:last-child').textContent = pastTickets;
        }
    </script>

    <style>
        /* تنسيقات صفحة التذاكر */
        .tickets-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title {
            color: var(--dark-green);
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* بطاقة المستخدم */
        .user-badge {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info i {
            font-size: 48px;
        }
        
        .user-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .ticket-stats {
            display: flex;
            gap: 30px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 32px;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* أقسام التذاكر */
        .tickets-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-header h2 {
            color: var(--dark-green);
        }
        
        .section-header .badge {
            background: var(--primary-green);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* لا توجد تذاكر */
        .no-tickets {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .no-tickets i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            opacity: 0.5;
        }
        
        .no-tickets h3 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .no-tickets a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* بطاقات التذاكر الحالية */
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .ticket-card {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .ticket-card.current {
            border-color: var(--primary-green);
        }
        
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .ticket-header {
            background: var(--light-gray);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .ticket-id {
            font-weight: 700;
            color: var(--dark-green);
            font-size: 18px;
        }
        
        .ticket-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #c82333;
        }
        
        .ticket-body {
            padding: 20px;
        }
        
        .ticket-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .ticket-info i {
            color: var(--primary-green);
            font-size: 18px;
            width: 24px;
        }
        
        .ticket-info .label {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .ticket-info .value {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .ticket-info .value.code {
            font-family: monospace;
            letter-spacing: 1px;
            background: var(--light-gray);
            padding: 5px 10px;
            border-radius: var(--radius-sm);
        }
        
        .ticket-footer {
            background: var(--light-gray);
            padding: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .instructions {
            margin-bottom: 15px;
        }
        
        .instructions p {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* جدول التذاكر الفائتة */
        .tickets-table {
            overflow-x: auto;
        }
        
        .tickets-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tickets-table th {
            background: var(--primary-green);
            color: white;
            padding: 12px;
            text-align: right;
            font-weight: 600;
        }
        
        .tickets-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tickets-table tr:hover {
            background: var(--light-gray);
        }
        
        .ticket-code {
            font-family: monospace;
            font-weight: 600;
            color: var(--dark-green);
        }
        
        .status {
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
        }
        
        .status.attended {
            background: #d4edda;
            color: #155724;
        }
        
        .status.absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .unknown, .not-checked {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        /* كيفية الاستخدام */
        .instructions-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-md);
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .step {
            background: var(--light-gray);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        
        .step-number {
            background: var(--primary-green);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-content h4 {
            color: var(--dark-green);
            margin-bottom: 8px;
        }
        
        .step-content p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .user-badge {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .ticket-stats {
                justify-content: center;
            }
            
            .tickets-grid {
                grid-template-columns: 1fr;
            }
            
            .steps {
                grid-template-columns: 1fr;
            }
            
            .ticket-header {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</body>
</html>