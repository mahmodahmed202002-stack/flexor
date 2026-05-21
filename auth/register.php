<?php
// 1. بدء الجلسة
session_start();

// 2. تفعيل إظهار الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. استدعاء ملف الاتصال (المسار النسبي هنا صحيح لأننا نخرج من auth لـ includes)
if (!file_exists('../includes/db.php')) {
    die("خطأ: ملف db.php غير موجود في مسار includes.");
}
require_once '../includes/db.php'; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $user);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $error = "اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $email, $pass);
        
        if ($stmt->execute()) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "حدث خطأ أثناء إنشاء الحساب، حاول مجدداً.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد | Flexor</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        :root { 
            --main-yellow: #d4ff00; 
            --main-color: #d4ff00; /* ضروري جداً لعمل الفوتر والنافبار */
            --main-red: #ff4757;    
            --bg-dark: #0b0b0b; 
            --card-bg: #151515;
        }

        body { 
            background-color: var(--bg-dark); 
            color: #fff; 
            font-family: 'Cairo', sans-serif; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            margin: 0; 
        }

        /* حاوية الفورم لضمان التوسيط وعدم التداخل مع النافبار */
        .auth-wrapper-flex {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px; /* مساحة علوية للنافبار */
        }

        .auth-container { 
            background: var(--card-bg); 
            padding: 35px; 
            border-radius: 15px; 
            border-top: 4px solid var(--main-yellow); 
            width: 100%;
            max-width: 380px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.7); 
            border: 1px solid #222;
        }

        h2 { text-align: center; margin-bottom: 25px; color: #fff; font-weight: 900; }

        input { 
            width: 100%; 
            padding: 13px; 
            margin-bottom: 15px; 
            border-radius: 8px; 
            border: 1px solid #333; 
            background: #000; 
            color: #fff; 
            box-sizing: border-box; 
            outline: none; 
            font-family: 'Cairo', sans-serif;
            transition: 0.3s;
        }

        input:focus { border-color: var(--main-yellow); box-shadow: 0 0 10px rgba(212, 255, 0, 0.2); }

        button { 
            width: 100%; 
            padding: 14px; 
            background: var(--main-yellow); 
            border: none; 
            color: #000; 
            font-weight: 900; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: 0.3s; 
            font-size: 16px;
            font-family: 'Cairo', sans-serif;
        }

        button:hover { 
            background: #e6ff00; 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(212, 255, 0, 0.3); 
        }

        .msg { 
            background: rgba(255, 71, 87, 0.1); 
            color: var(--main-red); 
            padding: 10px; 
            border-radius: 5px; 
            text-align: center; 
            font-size: 14px; 
            margin-bottom: 20px; 
            border: 1px solid rgba(255, 71, 87, 0.3); 
        }

        .footer-link { text-align: center; margin-top: 25px; font-size: 14px; color: #888; }
        .footer-link a { color: var(--main-yellow); text-decoration: none; font-weight: bold; }
        .back-home { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; font-size: 13px; }

        .main-footer { margin-top: auto !important; width: 100%; }
    </style>
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <div class="auth-wrapper-flex">
        <div class="auth-container">
            <h2>عضوية جديدة</h2>
            
            <?php if($error !== "") echo "<p class='msg'>$error</p>"; ?>
            
            <form method="POST">
                <input type="text" name="username" placeholder="اسم المستخدم" required>
                <input type="email" name="email" placeholder="البريد الإلكتروني" required>
                <input type="password" name="password" placeholder="كلمة المرور" required>
                <button type="submit">إنشاء الحساب الآن</button>
            </form>

            <div class="footer-link">
                لديك حساب بالفعل؟ <a href="login.php">سجل دخولك من هنا</a>
            </div>
            <a href="/index.php" class="back-home">← العودة للرئيسية</a>
        </div>
    </div>

    <?php include '../footer.php'; ?>

</body>
</html>