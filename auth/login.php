<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال قاعدة البيانات
require_once '../includes/db.php'; 

$error = "";

// 🔐 إنشاء CSRF Token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// عند إرسال الفورم
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 🔒 تحقق من CSRF
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("CSRF ERROR");
    }

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        if (password_verify($password, $user['password'])) {

            // 🔐 حماية السيشن
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../index.php");
            exit();

        } else {
            $error = "كلمة المرور غير صحيحة!";
        }

    } else {
        $error = "البريد الإلكتروني غير مسجل!";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>تسجيل الدخول | Flexor</title>

    <!-- 🔥 منع الأرشفة -->
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        :root {
            --main-color: #d4ff00;
            --bg-dark: #0b0b0b;
            --card-bg: #151515;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px;
        }

        .auth-container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #222;
            border-top: 4px solid var(--main-color);
            box-shadow: 0 15px 50px rgba(0,0,0,0.8);
        }

        .auth-container h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 900;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
        }

        input {
            width: 100%;
            padding: 14px;
            background: #000;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 10px rgba(212, 255, 0, 0.2);
        }

        .auth-btn {
            width: 100%;
            padding: 15px;
            background: var(--main-color);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 900;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .auth-btn:hover {
            background: #e6ff00;
            transform: translateY(-3px);
        }

        .error-msg {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 71, 87, 0.2);
        }

        .switch-auth {
            text-align: center;
            margin-top: 20px;
            color: #888;
        }

        .switch-auth a {
            color: var(--main-color);
            text-decoration: none;
            font-weight: bold;
        }

        /* 🔒 Trust Signal */
        .secure-note {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-top: 15px;
        }
    </style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="login-page-wrapper">
    <div class="auth-container">

        <h2>تسجيل الدخول</h2>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <!-- 🔐 CSRF -->
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" required placeholder="example@mail.com">
            </div>

            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="auth-btn">دخول</button>

        </form>

        <!-- 🔒 رسالة أمان -->
        <p class="secure-note">🔒 بياناتك محمية ومشفرة بالكامل</p>

        <div class="switch-auth">
            ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
        </div>

    </div>
</div>

<?php include '../footer.php'; ?>

</body>
</html>