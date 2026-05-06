<?php
// report.php - بداية الملف
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['fullname'];
$college = $_SESSION['college'];

// جلب المعامل الخاصة بالكلية
$labs = [];
if ($conn && $conn->connect_errno === 0) {
    $lab_sql = "SELECT * FROM labs WHERE college = ? ORDER BY lab_code";
    $stmt = $conn->prepare($lab_sql);
    $stmt->bind_param("s", $college);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $labs[] = $row;
    }
    $stmt->close();
}

// معالجة الإبلاغ
$report_success = false;
$report_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_id = $_POST['lab_id'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($lab_id) || empty($description)) {
        $report_error = 'الرجاء تعبئة جميع الحقول المطلوبة';
    } else {
        // التعامل مع الصورة المرفوعة
        $image_path = null;
        
        if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] === 0) {
            $upload_dir = 'uploads/reports/';
            
            // إنشاء المجلد إذا لم يكن موجوداً
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['issue_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            // التحقق من نوع الملف
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($imageFileType, $allowed_types)) {
                if (move_uploaded_file($_FILES['issue_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
        
        // حفظ الإبلاغ في قاعدة البيانات
        $insert_sql = "INSERT INTO reports (user_id, lab_id, issue_description, issue_type, image_path) 
                      VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iisss", $user_id, $lab_id, $description, $issue_type, $image_path);
        
        if ($stmt->execute()) {
            $report_success = true;
            
            // إرسال إشعار للمسؤولين (محاكاة)
            // في الواقع، هنا سيكون كود إرسال إيميل أو إشعار
            
            // إعادة تعيين النموذج
            $_POST = [];
        } else {
            $report_error = 'حدث خطأ في حفظ التقرير: ' . $conn->error;
        }
        $stmt->close();
    }
}

// جبل تقارير المستخدم السابقة
$user_reports = [];
if ($conn && $conn->connect_errno === 0) {
    $reports_sql = "SELECT r.*, l.lab_code, l.lab_name 
                   FROM reports r 
                   JOIN labs l ON r.lab_id = l.id 
                   WHERE r.user_id = ? 
                   ORDER BY r.report_date DESC 
                   LIMIT 10";
    
    $stmt = $conn->prepare($reports_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $user_reports[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إبلاغ عن عطل - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
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
        <div class="report-container">
            <!-- عنوان الصفحة -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-tools"></i>
                    إبلاغ عن عطل في المعامل
                </h1>
                <p class="page-subtitle">ساعدنا في الحفاظ على جاهزية المعامل بالإبلاغ عن أي عطل</p>
            </div>

            <!-- رسائل النجاح/الخطأ -->
            <?php if ($report_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h4>تم الإبلاغ بنجاح!</h4>
                    <p>شكراً لك على مساعدتنا في تحسين جودة الخدمة. سيتم معالجة التقرير خلال 24 ساعة.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($report_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <h4>حدث خطأ</h4>
                    <p><?php echo $report_error; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="report-content">
                <!-- نموذج الإبلاغ -->
                <div class="report-form-section">
                    <div class="form-card">
                        <div class="form-header">
                            <h3>
                                <i class="fas fa-edit"></i>
                                نموذج الإبلاغ
                            </h3>
                            <p class="form-description">املأ النموذج أدناه للإبلاغ عن العطل</p>
                        </div>

                        <form class="report-form" method="POST" action="" enctype="multipart/form-data">
                            <!-- اختيار المعمل -->
                            <div class="form-group">
                                <label for="lab_id">
                                    <i class="fas fa-flask"></i>
                                    اختيار المعمل
                                </label>
                                <select id="lab_id" name="lab_id" required class="modern-select">
                                    <option value="" disabled selected>اختر المعمل</option>
                                    <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo $lab['id']; ?>" 
                                            <?php echo isset($_POST['lab_id']) && $_POST['lab_id'] == $lab['id'] ? 'selected' : ''; ?>>
                                        <?php echo $lab['lab_code']; ?> - <?php echo $lab['lab_name']; ?> (المبنى <?php echo $lab['building']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">اختر المعمل الذي يوجد به العطل</small>
                            </div>

                            <!-- نوع العطل -->
                            <div class="form-group">
                                <label for="issue_type">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    نوع العطل
                                </label>
                                <select id="issue_type" name="issue_type" class="modern-select">
                                    <option value="" selected>اختر نوع العطل (اختياري)</option>
                                    <option value="كهرباء" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'كهرباء' ? 'selected' : ''; ?>>مشكلة كهربائية</option>
                                    <option value="شبكة" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'شبكة' ? 'selected' : ''; ?>>مشكلة شبكة</option>
                                    <option value="جهاز" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'جهاز' ? 'selected' : ''; ?>>عطل في جهاز</option>
                                    <option value="أثاث" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'أثاث' ? 'selected' : ''; ?>>أثاث أو تجهيزات</option>
                                    <option value="برمجيات" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'برمجيات' ? 'selected' : ''; ?>>مشكلة برمجية</option>
                                    <option value="أخرى" <?php echo isset($_POST['issue_type']) && $_POST['issue_type'] == 'أخرى' ? 'selected' : ''; ?>>أخرى</option>
                                </select>
                            </div>

                            <!-- وصف العطل -->
                            <div class="form-group">
                                <label for="description">
                                    <i class="fas fa-file-alt"></i>
                                    وصف العطل
                                </label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    rows="5" 
                                    required 
                                    placeholder="صف العطل بالتفصيل (متى حدث، ما هي الأعراض، أي جهاز بالضبط...)"
                                ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <small class="form-hint">كلما كان الوصف دقيقاً، كلما كانت المعالجة أسرع</small>
                            </div>

                            <!-- رفع صورة -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-camera"></i>
                                    رفع صورة (اختياري)
                                </label>
                                
                                <div class="image-upload">
                                    <div class="upload-preview" id="uploadPreview">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>اسحب وأفلت الصورة هنا أو انقر للاختيار</p>
                                        <input type="file" id="issue_image" name="issue_image" 
                                               accept="image/*" capture="environment"
                                               style="display: none;" 
                                               onchange="previewImage(this)">
                                        <button type="button" class="btn-select" onclick="document.getElementById('issue_image').click()">
                                            اختر صورة
                                        </button>
                                    </div>
                                    
                                    <div class="image-preview" id="imagePreviewContainer" style="display: none;">
                                        <img id="previewImage" alt="معاينة الصورة">
                                        <button type="button" class="btn-remove" onclick="removePreview()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <small class="form-hint">الصور تساعد الفنيين في تحديد المشكلة بدقة</small>
                            </div>

                            <!-- معلومات إضافية -->
                            <div class="form-group">
                                <div class="info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <h4>معلومات مهمة:</h4>
                                        <ul>
                                            <li>سيتم معالجة البلاغ خلال 24 ساعة عمل</li>
                                            <li>حافظ على سلامتك ولا تحاول إصلاح العطل بنفسك</li>
                                            <li>سيتم إشعارك عند معالجة البلاغ</li>
                                            <li>لحالات الطوارئ، اتصل بالدعم الفني مباشرة</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار الإرسال -->
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i>
                                    إرسال البلاغ
                                </button>
                                <button type="reset" class="btn-reset" onclick="resetForm()">
                                    <i class="fas fa-redo"></i>
                                    إعادة تعيين
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- تقاريري السابقة -->
                <div class="reports-history">
                    <div class="history-card">
                        <div class="history-header">
                            <h3>
                                <i class="fas fa-history"></i>
                                تقاريري السابقة
                            </h3>
                            <span class="badge"><?php echo count($user_reports); ?></span>
                        </div>
                        
                        <?php if (empty($user_reports)): ?>
                        <div class="no-reports">
                            <i class="far fa-clipboard"></i>
                            <h4>لا توجد تقارير سابقة</h4>
                            <p>سيتم عرض التقارير التي تبلغ عنها هنا</p>
                        </div>
                        <?php else: ?>
                        <div class="reports-list">
                            <?php foreach ($user_reports as $report): ?>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($report['status']) {
                                case 'reported':
                                    $status_class = 'reported';
                                    $status_text = 'تم الإبلاغ';
                                    break;
                                case 'in_progress':
                                    $status_class = 'progress';
                                    $status_text = 'قيد المعالجة';
                                    break;
                                case 'resolved':
                                    $status_class = 'resolved';
                                    $status_text = 'تم الإصلاح';
                                    break;
                                default:
                                    $status_class = 'reported';
                                    $status_text = 'تم الإبلاغ';
                            }
                            
                            $report_date = date("Y-m-d", strtotime($report['report_date']));
                            ?>
                            <div class="report-item">
                                <div class="report-header">
                                    <div class="report-lab">
                                        <i class="fas fa-flask"></i>
                                        <span><?php echo $report['lab_code']; ?></span>
                                    </div>
                                    <div class="report-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </div>
                                </div>
                                
                                <div class="report-body">
                                    <p class="report-description">
                                        <?php echo htmlspecialchars($report['issue_description']); ?>
                                    </p>
                                    
                                    <?php if ($report['issue_type']): ?>
                                    <div class="report-tags">
                                        <span class="tag">
                                            <i class="fas fa-tag"></i>
                                            <?php echo $report['issue_type']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($report['image_path'] && file_exists($report['image_path'])): ?>
                                    <div class="report-image">
                                        <button class="btn-view-image" onclick="viewReportImage('<?php echo $report['image_path']; ?>')">
                                            <i class="fas fa-image"></i>
                                            عرض الصورة المرفوعة
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="report-footer">
                                    <div class="report-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo $report_date; ?>
                                    </div>
                                    
                                    <?php if ($report['resolved_date']): ?>
                                    <div class="report-resolved">
                                        <i class="fas fa-check"></i>
                                        تم الإصلاح: <?php echo date("Y-m-d", strtotime($report['resolved_date'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- إرشادات الإبلاغ -->
            <div class="guidelines">
                <h3>
                    <i class="fas fa-lightbulb"></i>
                    إرشادات للإبلاغ الفعال
                </h3>
                
                <div class="guidelines-grid">
                    <div class="guideline">
                        <div class="guideline-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h4>التصوير</h4>
                        <p>التقط صوراً واضحة للعطل من زوايا مختلفة</p>
                    </div>
                    
                    <div class="guideline">
                        <div class="guideline-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>التفاصيل</h4>
                        <p>اذكر رقم الجهاز وموقعه في المعمل</p>
                    </div>
                    
                    <div class="guideline">
                        <div class="guideline-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>التوقيت</h4>
                        <p>حدد وقت حدوث المشكلة</p>
                    </div>
                    
                    <div class="guideline">
                        <div class="guideline-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>السلامة</h4>
                        <p>لا تتعامل مع الأعطال الكهربائية بنفسك</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- نافذة عرض صورة التقرير -->
    <div class="modal" id="imageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>الصورة المرفوعة</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <img id="modalImage" alt="صورة التقرير" style="max-width: 100%;">
            </div>
        </div>
    </div>

    <script>
        // معاينة الصورة قبل الرفع
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const preview = document.getElementById('imagePreviewContainer');
                const previewImg = document.getElementById('previewImage');
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('uploadPreview').style.display = 'none';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // إزالة المعاينة
        function removePreview() {
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('uploadPreview').style.display = 'block';
            document.getElementById('issue_image').value = '';
        }
        
        // إعادة تعيين النموذج
        function resetForm() {
            removePreview();
        }
        
        // عرض صورة التقرير
        function viewReportImage(imagePath) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modalImg.src = imagePath;
            modal.style.display = 'block';
        }
        
        // إغلاق النافذة
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // سحب وإفلات الصور
        const uploadPreview = document.getElementById('uploadPreview');
        
        uploadPreview.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = 'rgba(124, 152, 148, 0.1)';
        });
        
        uploadPreview.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
        });
        
        uploadPreview.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('issue_image').files = files;
                previewImage(document.getElementById('issue_image'));
            }
        });
        
        // عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // إغلاق النافذة عند الضغط خارجها
            window.onclick = function(event) {
                const modal = document.getElementById('imageModal');
                if (event.target === modal) {
                    closeModal();
                }
            }
        });
    </script>

    <style>
        /* تنسيقات صفحة الإبلاغ */
        .report-container {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* الرسائل */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border-radius: var(--radius-md);
            margin-bottom: 30px;
        }
        
        .alert i {
            font-size: 24px;
            margin-top: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-success i {
            color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-error i {
            color: #dc3545;
        }
        
        .alert h4 {
            margin-bottom: 8px;
        }
        
        .alert p {
            margin: 0;
            line-height: 1.5;
        }
        
        /* المحتوى الرئيسي */
        .report-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 992px) {
            .report-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* نموذج الإبلاغ */
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .form-header h3 {
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .form-description {
            color: var(--text-secondary);
            font-size: 15px;
        }
        
        /* حقل النموذج */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 15px;
        }
        
        .form-group label i {
            color: var(--primary-green);
        }
        
        .modern-select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .modern-select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-hint {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        /* رفع الصور */
        .image-upload {
            margin-top: 10px;
        }
        
        .upload-preview {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-preview:hover {
            border-color: var(--primary-green);
            background: var(--light-gray);
        }
        
        .upload-preview i {
            font-size: 48px;
            color: var(--primary-green);
            margin-bottom: 15px;
            display: block;
        }
        
        .btn-select {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
        }
        
        .image-preview {
            position: relative;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
        }
        
        .btn-remove {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--error-color);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
        }
        
        /* معلومات إضافية */
        .info-box {
            background: var(--light-gray);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            gap: 15px;
        }
        
        .info-box i {
            color: var(--primary-green);
            font-size: 20px;
            margin-top: 3px;
        }
        
        .info-box h4 {
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin: 0;
            padding-right: 20px;
            color: var(--text-primary);
        }
        
        .info-box li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        /* أزرار الإرسال */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn-submit, .btn-reset {
            padding: 15px 30px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            flex: 2;
        }
        
        .btn-reset {
            background: var(--medium-gray);
            color: var(--text-primary);
            flex: 1;
        }
        
        .btn-submit:hover, .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        /* تقارير سابقة */
        .history-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .history-header h3 {
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .badge {
            background: var(--primary-green);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .no-reports {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .no-reports i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            opacity: 0.5;
        }
        
        .no-reports h4 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        /* قائمة التقارير */
        .reports-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .report-item {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .report-lab {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--dark-green);
        }
        
        .report-lab i {
            color: var(--primary-green);
        }
        
        .report-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }
        
        .report-status.reported {
            background: #fff3cd;
            color: #856404;
        }
        
        .report-status.progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .report-status.resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .report-description {
            color: var(--text-primary);
            line-height: 1.5;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .report-tags {
            margin-bottom: 15px;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--light-gray);
            color: var(--text-primary);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 12px;
        }
        
        .tag i {
            color: var(--primary-green);
        }
        
        .report-image {
            margin-bottom: 15px;
        }
        
        .btn-view-image {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .report-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .report-date, .report-resolved {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* الإرشادات */
        .guidelines {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .guidelines h3 {
            color: var(--dark-green);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .guidelines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .guideline {
            text-align: center;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
        }
        
        .guideline:hover {
            border-color: var(--primary-green);
            transform: translateY(-5px);
        }
        
        .guideline-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .guideline-icon i {
            font-size: 24px;
        }
        
        .guideline h4 {
            color: var(--dark-green);
            margin-bottom: 8px;
        }
        
        .guideline p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* نافذة عرض الصورة */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--primary-green);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
        }
        
        .modal-body {
            padding: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .guidelines-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>