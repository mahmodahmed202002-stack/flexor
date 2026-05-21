<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// التعامل مع عملية الحذف بأمان
if(isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM movies WHERE id = $id");
    header("Location: manage-movies.php?deleted=1");
    exit();
}

// جلب عدد الأفلام للإحصائيات
$total_movies = $conn->query("SELECT COUNT(*) as total FROM movies")->fetch_assoc()['total'];

// جلب الأفلام
$result = $conn->query("SELECT * FROM movies ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>إدارة الأفلام - Flexor Pro</title>
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

        /* حاوية الإحصائيات */
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

        /* نظام البحث */
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
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .add-new:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(212,255,0,0.3); }

        /* الجدول المطور */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { 
            background: #000; 
            color: #888; 
            padding: 15px; 
            text-align: right; 
            font-size: 13px; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: rgba(255,255,255,0.02); }

        .movie-cell { display: flex; align-items: center; gap: 15px; }
        .poster-sm { 
            width: 50px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 6px; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.5);
            transition: 0.3s;
        }
        tr:hover .poster-sm { transform: scale(1.1); }
        
        .movie-title { font-weight: 700; font-size: 16px; margin: 0; }
        .movie-title small { color: #666; display: block; margin-top: 3px; font-weight: 400; }

        .badge { background: #222; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .rating { color: var(--primary); font-weight: 900; }

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
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="stats-bar">
    <div class="stat-item">
        <span>إجمالي الأفلام</span>
        <h3><?php echo $total_movies; ?> فيلم</h3>
    </div>
    <div class="stat-item">
        <span>أحدث إضافة</span>
        <h3 style="font-size: 16px; color: var(--secondary);">قاعدة البيانات محدثة</h3>
    </div>
</div>

<div class="manage-container">
    <div class="header-flex">
        <h2 style="margin:0">🎬 إدارة المكتبة</h2>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="movieSearch" placeholder="ابحث عن فيلم بالاسم العربي أو الإنجليزي...">
        </div>

        <a href="add-movie.php" class="add-new">
            <i class="fas fa-plus"></i> إضافة فيلم
        </a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert" style="background: rgba(212,255,0,0.1); border: 1px solid var(--primary); color: var(--primary);">
            <i class="fas fa-check-circle"></i> تم حفظ التعديلات بنجاح
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table id="moviesTable">
            <thead>
                <tr>
                    <th>الفيلم</th>
                    <th>سنة الإنتاج</th>
                    <th>التقييم</th>
                    <th>التحكم</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="movie-cell">
                            <img src="<?php echo $row['poster_path']; ?>" class="poster-sm" alt="Poster">
                            <div class="movie-title">
                                <?php echo $row['title_ar']; ?>
                                <small><?php echo $row['title_en']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge"><?php echo $row['release_year']; ?></span></td>
                    <td><span class="rating"><i class="fas fa-star"></i> <?php echo number_format($row['rating'], 1); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="edit-movie.php?id=<?php echo $row['id']; ?>" class="btn btn-edit" title="تعديل">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                            <a href="manage-movies.php?delete_id=<?php echo $row['id']; ?>" 
                               class="btn btn-del" title="حذف"
                               onclick="return confirm('هل تريد حذف فيلم (<?php echo $row['title_ar']; ?>) نهائياً؟')">
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
    // كود البحث الفوري في الجدول
    document.getElementById('movieSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelector("#moviesTable tbody").rows;
        
        for (let i = 0; i < rows.length; i++) {
            let titleAr = rows[i].cells[0].textContent.toLowerCase();
            let titleEn = rows[i].cells[0].querySelector('small').textContent.toLowerCase();
            if (titleAr.includes(filter) || titleEn.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    });

    // إخفاء التنبيهات بعد 3 ثواني
    setTimeout(() => {
        let alert = document.querySelector('.alert');
        if(alert) alert.style.display = 'none';
    }, 3000);
</script>

</body>
</html>