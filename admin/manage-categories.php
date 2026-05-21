<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// 1. معالجة الترتيب الجديد عبر AJAX
if(isset($_POST['update_order'])) {
    $order = $_POST['order_data'];
    foreach($order as $index => $id) {
        $id = intval($id);
        $index = intval($index);
        $conn->query("UPDATE categories SET sort_order = $index WHERE id = $id");
    }
    exit('success');
}

// 2. عملية الإضافة (دعم العربية في الـ Slug)
if(isset($_POST['add_cat'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $parent = intval($_POST['parent_id']);
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($name));
    $slug = mb_strtolower($slug, 'UTF-8');

    $conn->query("INSERT INTO categories (name, parent_id, slug) VALUES ('$name', '$parent', '$slug')");
    header("Location: manage-categories.php");
    exit();
}

// 3. عملية الحذف
$error_msg = "";
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check_sub = $conn->query("SELECT id FROM categories WHERE parent_id = $id");
    if($check_sub->num_rows > 0) {
        $error_msg = "❌ لا يمكن حذف هذا القسم لأنه يحتوي على أقسام فرعية!";
    } else {
        $conn->query("DELETE FROM categories WHERE id = $id");
        header("Location: manage-categories.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-color: #d4ff00;
            --bg-dark: #0a0a0a;
            --card-bg: #141414;
            --accent-red: #ff4444;
            --transition: all 0.3s ease;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-dark);
            color: #fff;
        }

        h1 { font-size: 24px; font-weight: 900; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }

        /* إضافة قسم جديد */
        .add-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #222;
            margin-bottom: 30px;
        }

        .form-row { display: flex; gap: 15px; flex-wrap: wrap; }
        
        input, select {
            background: #000;
            border: 1px solid #333;
            color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            font-family: 'Cairo';
            outline: none;
            transition: var(--transition);
        }

        input:focus, select:focus { border-color: var(--main-color); }

        .btn-add {
            background: var(--main-color);
            color: #000;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 900;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-add:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(212, 255, 0, 0.3); }

        /* هيكل القوائم */
        .category-item {
            background: var(--card-bg);
            border: 1px solid #222;
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
        }

        .main-cat-header {
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.02);
        }

        .cat-info { display: flex; align-items: center; gap: 15px; }

        .handle {
            cursor: grab;
            color: var(--main-color);
            display: flex;
            align-items: center;
        }

        .sub-cats-container {
            padding: 10px 50px 20px 25px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid #1a1a1a;
        }

        .sub-cat-item {
            background: #0d0d0d;
            padding: 12px 20px;
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 3px solid var(--main-color);
        }

        /* أزرار الحذف */
        .btn-delete {
            background: rgba(255, 68, 68, 0.1);
            color: var(--accent-red);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .btn-delete:hover {
            background: var(--accent-red);
            color: #fff;
        }

        .error-banner {
            background: rgba(255, 68, 68, 0.2);
            color: var(--accent-red);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--accent-red);
            margin-bottom: 20px;
            font-weight: bold;
        }

        svg { width: 18px; height: 18px; fill: currentColor; }
    </style>
</head>
<body>

    <h1>
        <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
        إدارة هيكل القوائم (Drag & Drop)
    </h1>

    <?php if($error_msg != ""): ?>
        <div class="error-banner"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <div class="add-card">
        <form method="POST" class="form-row">
            <input type="text" name="name" placeholder="اسم القسم الجديد..." required style="flex: 2;">
            <select name="parent_id" style="flex: 1;">
                <option value="0">--- قسم رئيسي جديد ---</option>
                <?php 
                $parents = $conn->query("SELECT id, name FROM categories WHERE parent_id = 0");
                while($p = $parents->fetch_assoc()) echo "<option value='{$p['id']}'>تابع لـ: {$p['name']}</option>";
                ?>
            </select>
            <button type="submit" name="add_cat" class="btn-add">إضافة الآن</button>
        </form>
    </div>

    <div id="main-sortable">
        <?php 
        $main_cats = $conn->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC");
        while($m = $main_cats->fetch_assoc()):
        ?>
        <div class="category-item" data-id="<?php echo $m['id']; ?>">
            <div class="main-cat-header">
                <div class="cat-info">
                    <span class="handle">
                        <svg viewBox="0 0 24 24"><path d="M11 18c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm-2-8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                    </span>
                    <strong><?php echo $m['name']; ?></strong>
                </div>
                <a href="?delete=<?php echo $m['id']; ?>" class="btn-delete" onclick="return confirm('حذف القسم الرئيسي سيعطل الترتيب، هل أنت متأكد؟')">
                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    حذف
                </a>
            </div>
            
            <div class="sub-cats-container sub-sortable" data-parent="<?php echo $m['id']; ?>">
                <?php 
                $subs = $conn->query("SELECT * FROM categories WHERE parent_id = {$m['id']} ORDER BY sort_order ASC");
                if($subs->num_rows == 0) echo "<small style='color:#555;'>لا يوجد أقسام فرعية حالياً</small>";
                while($s = $subs->fetch_assoc()):
                ?>
                <div class="sub-cat-item" data-id="<?php echo $s['id']; ?>">
                    <div class="cat-info">
                        <span class="handle">
                             <svg viewBox="0 0 24 24" width="14"><path d="M11 18c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm-2-8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        </span>
                        <?php echo $s['name']; ?>
                    </div>
                    <a href="?delete=<?php echo $s['id']; ?>" style="color:var(--accent-red); text-decoration:none; font-size:12px; opacity:0.7;">حذف الفرع</a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // تفعيل السحب للأقسام الرئيسية
    new Sortable(document.getElementById('main-sortable'), {
        animation: 150,
        handle: '.handle',
        onEnd: function() { saveOrder('main-sortable'); }
    });

    // تفعيل السحب للأقسام الفرعية
    document.querySelectorAll('.sub-sortable').forEach(el => {
        new Sortable(el, {
            animation: 150,
            handle: '.handle',
            onEnd: function() { saveOrder(null, el); }
        });
    });

    function saveOrder(containerId, subEl = null) {
        let order = [];
        let items = subEl ? subEl.querySelectorAll('.sub-cat-item') : document.querySelectorAll('#main-sortable > .category-item');
        
        items.forEach(item => {
            let id = item.getAttribute('data-id');
            if(id) order.push(id);
        });

        if(order.length === 0) return;

        let formData = new URLSearchParams();
        formData.append('update_order', '1');
        order.forEach(id => formData.append('order_data[]', id));

        fetch('manage-categories.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        }).then(res => res.text()).then(data => {
            console.log("تم تحديث الترتيب بنجاح");
        });
    }
</script>

</body>
</html>