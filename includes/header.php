<?php 
// تأكد من بدء الجلسة في أول الملف تماماً
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// المسار هنا نسبي لأن db.php غالباً في نفس مجلد includes
include('db.php'); 

// جلب الأقسام الرئيسية فقط
$main_sql = "SELECT * FROM categories WHERE parent_id = 0 AND status = 'active' ORDER BY sort_order ASC";
$main_result = $conn->query($main_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php
$page_title = $page_title ?? "Flexor - مشاهدة أفلام ومسلسلات وبث مباشر";
$page_desc  = $page_desc ?? "شاهد أحدث الأفلام والمسلسلات والبث المباشر والمحتوى التعليمي والرياضي بجودة عالية على Flexor";
$page_img   = $page_img ?? "https://flexor.gt.tc/public/logo.png";
$page_url = "https://flexor.gt.tc" . strtok($_SERVER["REQUEST_URI"], '?');
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta itemprop="ratingValue" content="8.5">
<meta itemprop="ratingCount" content="120">
<title><?= htmlspecialchars($page_title) ?></title>

<meta name="description" content="<?= htmlspecialchars($page_desc) ?>">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#d4ff00">
<meta name="author" content="Flexor">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($page_desc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($page_img) ?>">
<link rel="canonical" href="<?= $page_url ?>">
<link rel="alternate" href="<?= $page_url ?>" hreflang="ar">
<!-- Open Graph -->
<meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($page_desc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($page_img) ?>">
<meta property="og:url" content="<?= htmlspecialchars($page_url) ?>">
    
    <meta name="google-site-verification" content="g06oYPT0IXyzHHCqmwkc_aA8ukCuWYzwsL-EKldD1hA" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="icon" type="image/png" href="/public/logo.png">
	<link rel="shortcut icon" href="/public/logo.png">
	<link rel="apple-touch-icon" href="/public/logo.png">
    <style>
        /* التنسيقات الخاصة بالهيدر والمستخدم */
        .nav-item-user {
            border-left: 2px solid var(--main-yellow);
            box-shadow: -5px 0 10px rgba(212, 255, 0, 0.2);
            margin-left: 20px;
            padding-left: 20px;
        }

        .auth-btns {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-right: 15px;
        }

        .btn-login {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-register {
            background: #ff4757; 
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 8px 18px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(255, 71, 87, 0.4);
            transition: 0.3s;
        }
        .btn-register:hover {
            background: #ff6b81;
            box-shadow: 0 0 20px rgba(255, 71, 87, 0.6);
        }
        .user-welcome {
            color: #ff4757;
            font-weight: bold;
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .fav-link {
            color: var(--main-yellow) !important;
            font-weight: bold;
        }
        .logout-link {
            color: #aaa;
            font-size: 0.8rem;
            text-decoration: none;
        }
        .logout-link:hover { color: #fff; }

        @media (max-width: 768px) {
            .nav-links {
                display: none; 
                flex-direction: column;
                position: fixed;
                top: 0;
                right: 0;
                width: 280px;
                height: 100vh !important;
                background: rgba(10, 10, 10, 0.98);
                backdrop-filter: blur(15px);
                padding: 80px 20px 100px 20px !important;
                overflow-y: auto !important;
                z-index: 9999;
                box-shadow: -10px 0 30px rgba(0,0,0,0.5);
            }
            
            .nav-links.active {
                display: flex !important;
            }
            
            .auth-btns {
                flex-direction: column;
                width: 100%;
                padding: 20px;
                margin-top: 10px;
            }
            
            .nav-links li {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .btn-register, .btn-login { width: 100%; text-align: center; }

            .nav-links li.dropdown .dropdown-menu {
                display: none;
                opacity: 0;
                position: static;
                width: 100%;
                background: rgba(255, 255, 255, 0.05);
            }

            .nav-links li.dropdown.active > .dropdown-menu {
                display: block !important;
                opacity: 1;
                transition: 0.3s ease;
                margin-top: 10px;
                padding: 10px;
                border-radius: 8px;
            }
        }
        
        /* نظام البحث المطور */
        .search-trigger {
            color: var(--main-yellow);
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 15px;
            transition: 0.3s;
            display: flex;
            align-items: center;
        }
        .search-trigger:hover {
            text-shadow: 0 0 10px var(--main-yellow);
            transform: scale(1.1);
        }

        .search-overlay {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            height: 0;
            overflow: visible;
            visibility: hidden;
            opacity: 0;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .search-overlay.active {
            height: 80px;
            visibility: visible;
            opacity: 1;
        }

        .search-container {
            max-width: 800px;
            margin: 15px auto;
            padding: 0 20px;
            position: relative;
        }

        #search-input {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 2px solid rgba(212, 255, 0, 0.3);
            color: #fff;
            font-size: 1.2rem;
            padding: 10px;
            font-family: 'Cairo', sans-serif;
        }

        #search-input:focus {
            outline: none;
            border-color: var(--main-yellow);
        }

        #search-results {
            position: absolute;
            top: 100%;
            left: 20px;
            right: 20px;
            background: rgba(15, 15, 15, 0.98);
            border: 1px solid #d4ff00;
            border-radius: 0 0 15px 15px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 10001 !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
            display: none;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #222;
            transition: background 0.3s ease;
            text-decoration: none !important;
            gap: 15px;
        }
        .search-result-item:hover {
            background: rgba(212, 255, 0, 0.1);
        }
        .result-poster {
            width: 45px;
            height: 65px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #333;
        }
        .result-title {
            color: #d4ff00;
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .result-meta {
            color: #888;
            font-size: 0.85rem;
        }

        /* إصلاح القوائم المنسدلة والـ z-index */
        .nav-links li.dropdown { position: relative; }
        .dropdown-menu { z-index: 10002 !important; }
        .user-dropdown-list { z-index: 10002 !important; }
    </style>
    
</head>
<body>

<header class="header-container">
    <nav class="navbar-capsule">
        <div class="nav-logo-item">
            <a href="/index.php" class="logo-link">Flexor</a>
        </div>

        <div class="mobile-menu-btn" id="mobile-btn">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <ul class="nav-links" id="nav-menu">
            <?php if ($main_result && $main_result->num_rows > 0): ?>
    <?php while($main_cat = $main_result->fetch_assoc()): 
        $parent_id = $main_cat['id'];
        $cat_slug = $main_cat['slug'];
        
        // التعديل الجوهري هنا:
        // إذا كان القسم هو بث مباشر، نضع الرابط الذي طلبته بالضبط
        // وإلا نستخدم الرابط الطبيعي للقسم
// التعديل الجوهري المطور لدعم اليوتيوب والبث المباشر
        // التعديل الجوهري المطور لدعم الصفحات المخصصة

if ($cat_slug == 'بث-مباشر') {

    $current_link = "/flexor-tv.php?i=1";

}
elseif ($cat_slug == 'يوتيوب') {

    $current_link = "/youtube-platforms.php";

}
elseif (

    $cat_slug == 'رياضة'

    ||

    $cat_slug == 'رياضه'

) {

    $current_link = "/sports/";

}
else {

    $current_link =
    "/category.php?slug=" .
    $cat_slug;
}

        $sub_sql = "SELECT * FROM categories WHERE parent_id = $parent_id AND status = 'active' ORDER BY sort_order ASC";
        $sub_result = $conn->query($sub_sql);
    ?>
        <?php if ($sub_result && $sub_result->num_rows > 0): ?>
            <li class="dropdown">
                <a href="javascript:void(0);" data-href="<?php echo $current_link; ?>" class="dropdown-toggle mobile-toggle">
                    <?php echo $main_cat['name']; ?>
                </a>
                <ul class="dropdown-menu">
                    <?php while($sub_cat = $sub_result->fetch_assoc()): ?>
                        <li><a href="/category.php?slug=<?php echo $sub_cat['slug']; ?>"><?php echo $sub_cat['name']; ?></a></li>
                    <?php endwhile; ?>
                </ul>
            </li>
        <?php else: ?>
            <li><a href="<?php echo $current_link; ?>"><?php echo $main_cat['name']; ?></a></li>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>

            <li class="auth-wrapper">
                <div class="auth-btns">
                    <div class="search-trigger" id="search-btn" title="بحث">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="user-custom-dropdown">
                            <div class="user-main-trigger">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 5px;"></i>
                            </div>
                            
                            <ul class="user-dropdown-list">
                                <li><a href="/favorites.php" class="fav-link"><i class="fas fa-heart"></i> مفضلتي</a></li>
                                <li><a href="/auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> خروج</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/auth/login.php" class="btn-login">دخول</a>
                        <a href="/auth/register.php" class="btn-register">حساب جديد</a>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </nav>

    <div class="search-overlay" id="search-bar">
        <div class="search-container">
            <input type="text" id="search-input" placeholder="ابحث عن فيلم، مسلسل، أو ممثل..." autocomplete="off">
            <div id="search-results"></div>
        </div>
    </div>
</header>

<script src="/assets/js/main.js"></script>

<script>
    // --- نظام القائمة المطور ---
    const mobileBtn = document.getElementById('mobile-btn');
    const navMenu = document.getElementById('nav-menu');

    mobileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        navMenu.classList.toggle('active');
        this.classList.toggle('open');
        
        if (navMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    });

    const mobileToggles = document.querySelectorAll('.mobile-toggle');
    
    mobileToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();
                
                const parentLi = this.parentElement;
                
                if (parentLi.classList.contains('active')) {
                    window.location.href = this.getAttribute('data-href');
                    return;
                }

                document.querySelectorAll('.nav-links li.dropdown').forEach(li => {
                    if (li !== parentLi) li.classList.remove('active');
                });

                parentLi.classList.toggle('active');
            } else {
                window.location.href = this.getAttribute('data-href');
            }
        });
    });

    // --- نظام البحث المطور ---
    const searchBtn = document.getElementById('search-btn');
    const searchBar = document.getElementById('search-bar');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    searchBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        searchBar.classList.toggle('active');
        if (searchBar.classList.contains('active')) {
            setTimeout(() => searchInput.focus(), 300);
        } else {
            searchResults.style.display = 'none';
            searchInput.value = '';
        }
    });

    document.addEventListener('click', function(e) {
        if (!searchBar.contains(e.target) && e.target !== searchBtn && !searchBtn.contains(e.target)) {
            searchBar.classList.remove('active');
            searchResults.style.display = 'none';
        }
        if (!navMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
            navMenu.classList.remove('active');
            mobileBtn.classList.remove('open');
            document.body.style.overflow = 'auto';
        }
    });

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            let query = this.value.trim();
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            fetch(`/search_api.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            const link = document.createElement('a');
                            link.href = `/${item.url}`;
                            link.className = 'search-result-item';
                            let posterImg = item.poster || '/assets/img/no-poster.jpg';
                            link.innerHTML = `
                                <img src="${posterImg}" class="result-poster">
                                <div>
                                    <div class="result-title">${item.title}</div>
                                    <div class="result-meta">${item.year} | ${item.type === 'movie' ? 'فيلم' : 'مسلسل'}</div>
                                </div>
                            `;
                            searchResults.appendChild(link);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div style="padding:15px; color:#aaa; text-align:center;">لا توجد نتائج مطابقة</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(err => console.error("Error:", err));
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchBar.classList.remove('active');
            searchResults.style.display = 'none';
        }
    });
</script>
