<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// التعامل مع عملية الحذف بأمان (حذف المسلسل والحلقات والمواسم)
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // حذف الحلقات والمواسم أولاً لضمان سلامة قاعدة البيانات
    $conn->query("DELETE FROM episodes WHERE series_id = $id");
    $conn->query("DELETE FROM seasons WHERE series_id = $id");
    $conn->query("DELETE FROM series WHERE id = $id");
    header("Location: manage-series.php?msg=deleted");
    exit();
}

// جلب إحصائيات المسلسلات
$total_series = $conn->query("SELECT COUNT(*) as total FROM series")->fetch_assoc()['total'];

// جلب المسلسلات مع اسم القسم من جدول categories
$query = "SELECT series.*, categories.name as cat_name 
          FROM series 
          LEFT JOIN categories ON series.main_cat_id = categories.id 
          ORDER BY series.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>إدارة المسلسلات - Flexor Pro</title>
    <style>
        :root {
            --primary: #d4ff00;
            --secondary: #00aaff;
            --danger: #ff4444;
            --bg-dark: #0a0a0a;
            --card-bg: #141414;
            --border: #222;
        }

        body { 
            font-family: 'Cairo', sans-serif; 
            background: var(--bg-dark); 
            color: #fff; 
            margin: 0; 
            padding: 20px; 
        }

        /* شريط الإحصائيات العلوي */
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-item {
            background: var(--card-bg);
            padding: 15px 25px;
            border-radius: 12px;
            border: 1px solid var(--border);
            flex: 1;
        }
        .stat-item span { color: #888; font-size: 14px; }
        .stat-item h3 { margin: 5px 0 0; font-size: 24px; color: var(--primary); }

        .manage-container { 
            background: var(--card-bg); 
            padding: 25px; 
            border-radius: 15px; 
            border: 1px solid var(--border);
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        /* مربع البحث الفوري */
        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        .search-box input {
            width: 100%;
            background: #000;
            border: 1px solid var(--border);
            padding: 12px 40px 12px 15px;
            border-radius: 8px;
            color: #fff;
            font-family: 'Cairo';
            outline: none;
            transition: 0.3s;
        }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 10px rgba(212,255,0,0.1); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #555; }

        .add-new { 
            background: var(--primary); 
            color: #000; 
            padding: 12px 25px; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: 900; 
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }
        .add-new:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(212,255,0,0.3); }

        /* تنسيق الجدول */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #000; color: #888; padding: 15px; text-align: right; font-size: 13px; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: rgba(255,255,255,0.02); }

        .series-cell { display: flex; align-items: center; gap: 15px; }
        .poster-sm { 
            width: 50px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 6px; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.5);
            transition: 0.3s;
        }
        tr:hover .poster-sm { transform: scale(1.1); }
        
        .series-title { font-weight: 700; font-size: 16px; margin: 0; }
        .series-title small { color: #666; display: block; margin-top: 3px; font-weight: 400; }

        .badge { background: #222; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .cat-badge { color: var(--secondary); border: 1px solid rgba(0,170,255,0.3); background: rgba(0,170,255,0.05); }

        /* أزرار العمليات */
        .action-btns { display: flex; gap: 10px; }
        .btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-edit { background: rgba(0,170,255,0.1); color: var(--secondary); border: 1px solid var(--secondary); }
        .btn-edit:hover { background: var(--secondary); color: #fff; }
        .btn-del { background: rgba(255,68,68,0.1); color: var(--danger); border: 1px solid var(--danger); }
        .btn-del:hover { background: var(--danger); color: #fff; }

        .alert { 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            background: rgba(212,255,0,0.1); 
            border: 1px solid var(--primary); 
            color: var(--primary);
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="stats-bar">
    <div class="stat-item">
        <span>إجمالي المسلسلات</span>
        <h3><?php echo $total_series; ?> مسلسل</h3>
    </div>
    <div class="stat-item">
        <span>إدارة المحتوى</span>
        <h3 style="font-size: 16px; color: var(--secondary);">المسلسلات والمواسم</h3>
    </div>
</div>

<div class="manage-container">
    <div class="header-flex">
        <h2 style="margin:0">🗂️ إدارة المسلسلات</h2>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="seriesSearch" placeholder="ابحث عن مسلسل بالاسم...">
        </div>

        <a href="add-series.php" class="add-new">
            <i class="fas fa-plus"></i> إضافة مسلسل
        </a>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert" id="success-alert">
            <i class="fas fa-check-circle"></i> تم حذف المسلسل وكافة ملحقاته بنجاح
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table id="seriesTable">
            <thead>
                <tr>
                    <th>المسلسل</th>
                    <th>القسم</th>
                    <th>سنة الإنتاج</th>
                    <th>التحكم</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="series-cell">
                            <img src="<?php echo $row['poster_path']; ?>" class="poster-sm" alt="Poster">
                            <div class="series-title">
                                <?php echo $row['title_ar']; ?>
                                <small><?php echo $row['title_en']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge cat-badge"><?php echo $row['cat_name']; ?></span></td>
                    <td><span class="badge"><?php echo $row['release_year']; ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="edit-series.php?id=<?php echo $row['id']; ?>" class="btn btn-edit" title="تعديل">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                            <a href="manage-series.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-del" title="حذف"
                               onclick="return confirm('هل تريد حذف مسلسل (<?php echo $row['title_ar']; ?>) وكل حلقاته نهائياً؟')">
                                <i class="fas fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // وظيفة البحث الفوري
    document.getElementById('seriesSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelector("#seriesTable tbody").rows;
        
        for (let i = 0; i < rows.length; i++) {
            let title = rows[i].cells[0].textContent.toLowerCase();
            if (title.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    });

    // إخفاء التنبيه تلقائياً
    setTimeout(() => {
        let alert = document.getElementById('success-alert');
        if(alert) alert.style.display = 'none';
    }, 3000);
</script>

</body>
</html>