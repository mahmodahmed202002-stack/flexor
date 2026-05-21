<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// إحصائيات سريعة
$cat_count = $conn->query("SELECT id FROM categories")->num_rows;
$movie_count = $conn->query("SELECT id FROM movies")->num_rows; 
$series_count = $conn->query("SELECT id FROM series")->num_rows;
$yt_count = $conn->query("SELECT id FROM yt_channels")->num_rows;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <title>لوحة التحكم - Flexor</title>
    <style>
        :root {
            --main-color: #d4ff00;
            --bg-dark: #0a0a0a;
            --sidebar-bg: #111111;
            --card-bg: #1a1a1a;
            --text-gray: #a0a0a0;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            margin: 0;
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-dark);
            color: #fff;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-left: 1px solid #222;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar h2 {
            padding: 30px 20px;
            font-weight: 900;
            color: var(--main-color);
            letter-spacing: 2px;
            text-align: center;
            border-bottom: 1px solid #222;
            margin: 0;
        }

        .side-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex-grow: 1;
        }

        .side-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: var(--text-gray);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 600;
            gap: 15px;
        }

        .side-menu li a svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        .side-menu li a:hover, .side-menu li a.active {
            color: var(--main-color);
            background: rgba(212, 255, 0, 0.05);
            border-right: 4px solid var(--main-color);
        }

        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #dynamic-header {
            padding: 30px 40px;
            background: linear-gradient(to bottom, #111, transparent);
        }

        #dynamic-header h1 {
            font-size: 24px;
            margin-bottom: 25px;
            font-weight: 700;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #222;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .stat-card:hover {
            border-color: var(--main-color);
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(212, 255, 0, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--main-color);
        }

        .stat-info h3 {
            font-size: 14px;
            color: var(--text-gray);
            margin: 0;
        }

        .stat-info p {
            font-size: 22px;
            font-weight: 900;
            margin: 5px 0 0 0;
        }

        /* Iframe */
        .content-frame {
            width: 100%;
            flex-grow: 1;
            border: none;
            background: transparent;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>FLEXOR</h2>
    <ul class="side-menu">
        <li>
            <a href="stats_summary.php" target="contentFrame" class="active">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                الرئيسية
            </a>
        </li>
        <li>
            <a href="manage-categories.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                إدارة الأقسام
            </a>
        </li>
        <li>
            <a href="manage-movies.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                إدارة الأفلام
            </a>
        </li>
        <li>
            <a href="manage-series.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
                إدارة المسلسلات
            </a>
        </li>
        <li>
    <a href="manage-live-tv.php" target="contentFrame">
        <svg viewBox="0 0 24 24"><path d="M21 6h-7.59l3.29-3.29L16 2l-4 4-4-4-1.29 1.29L10 6H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 14H3V8h18v12zM9 10v8l7-4z"/></svg>
        إدارة البث المباشر
    </a>
</li>
        
        <!-- قسم منصات يوتيوب الجديد -->
<li>
    <a href="manage-yt-channels.php" target="contentFrame">
        <svg viewBox="0 0 24 24"><path d="M10,15L15.19,12L10,9V15M21.56,7.17C21.69,7.64 21.82,8.27 21.88,9.07C21.95,9.87 22,10.56 22,11.17V12.83C22,13.44 21.95,14.13 21.88,14.93C21.82,15.73 21.69,16.36 21.56,16.83C21.43,17.3 21.11,17.76 20.6,18.2C20.09,18.64 19.5,18.91 18.81,19C17.91,19.14 15.64,19.21 12,19.21C8.36,19.21 6.09,19.14 5.19,19C4.5,18.91 3.91,18.64 3.4,18.2C2.89,17.76 2.57,17.3 2.44,16.83C2.31,16.36 2.18,15.73 2.12,14.93C2.05,14.13 2,13.44 2,12.83V11.17C2,10.56 2.05,9.87 2.12,9.07C2.18,8.27 2.31,7.64 2.44,7.17C2.57,6.7 2.89,6.24 3.4,5.8C3.91,5.36 4.5,5.09 5.19,5C6.09,4.86 8.36,4.79 12,4.79C15.64,4.79 17.91,4.86 18.81,5C19.5,5.09 20.09,5.36 20.6,5.8C21.11,6.24 21.43,6.7 21.56,7.17Z"/></svg>
        إدارة منصات يوتيوب
    </a>
</li>
        
        
        <li style="margin-top: 10px; padding: 0 20px; font-size: 11px; color: #444; font-weight: bold;">إضافات سريعة</li>
        <li>
            <a href="add-movie.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                إضافة فيلم
            </a>
        </li>
        <li>
            <a href="add-series.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                إضافة مسلسل
            </a>
        </li>
        
        
<li>
    <a href="add-live-channel.php" target="contentFrame">
        <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4zM14 13h-3v3H9v-3H6v-2h3V8h2v3h3v2z"/></svg>
        إضافة قناة بث
    </a>
</li>
        
        <li style="border-top: 1px solid #222; margin-top: auto;">
            <a href="settings.php" target="contentFrame">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                الإعدادات
            </a>
        </li>
        <li>
            <a href="logout.php" style="color:#ff4444;">
                <svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg>
                تسجيل الخروج
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    <div id="dynamic-header">
        <h1>مرحباً بك في فليكسور ✨</h1>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="24"><path fill="currentColor" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                </div>
                <div class="stat-info">
                    <h3>إجمالي الأقسام</h3>
                    <p><?php echo $cat_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="24"><path fill="currentColor" d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                </div>
                <div class="stat-info">
                    <h3>إجمالي الأفلام</h3>
                    <p><?php echo $movie_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="24"><path fill="currentColor" d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
                </div>
                <div class="stat-info">
                    <h3>إجمالي المسلسلات</h3>
                    <p><?php echo $series_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
    <div class="stat-icon" style="color: #ff0000; background: rgba(255, 0, 0, 0.1);">
        <svg viewBox="0 0 24 24" width="24"><path fill="currentColor" d="M10,15L15.19,12L10,9V15M21.56,7.17C21.69,7.64 21.82,8.27 21.88,9.07C21.95,9.87 22,10.56 22,11.17V12.83C22,13.44 21.95,14.13 21.88,14.93C21.82,15.73 21.69,16.36 21.56,16.83C21.43,17.3 21.11,17.76 20.6,18.2C20.09,18.64 19.5,18.91 18.81,19C17.91,19.14 15.64,19.21 12,19.21C8.36,19.21 6.09,19.14 5.19,19C4.5,18.91 3.91,18.64 3.4,18.2C2.89,17.76 2.57,17.3 2.44,16.83C2.31,16.36 2.18,15.73 2.12,14.93C2.05,14.13 2,13.44 2,12.83V11.17C2,10.56 2.05,9.87 2.12,9.07C2.18,8.27 2.31,7.64 2.44,7.17C2.57,6.7 2.89,6.24 3.4,5.8C3.91,5.36 4.5,5.09 5.19,5C6.09,4.86 8.36,4.79 12,4.79C15.64,4.79 17.91,4.86 18.81,5C19.5,5.09 20.09,5.36 20.6,5.8C21.11,6.24 21.43,6.7 21.56,7.17Z"/></svg>
    </div>
    <div class="stat-info">
        <h3>قنوات اليوتيوب</h3>
        <p><?php echo $yt_count; ?></p>
    </div>
</div>
            
        </div>
    </div>

    <iframe name="contentFrame" src="stats_summary.php" class="content-frame"></iframe>
</div>

<script>
    const links = document.querySelectorAll('.side-menu a');
    const header = document.getElementById('dynamic-header');
    const iframe = document.querySelector('.content-frame');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if(this.getAttribute('href') === 'logout.php') return;

            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            const targetHref = this.getAttribute('href');
            
            // إخفاء Header الإحصائيات في حال الانتقال لصفحة أخرى غير الرئيسية
            if(targetHref !== 'stats_summary.php') {
                header.style.display = 'none';
            } else {
                header.style.display = 'block';
            }
        });
    });
</script>

</body>
</html>