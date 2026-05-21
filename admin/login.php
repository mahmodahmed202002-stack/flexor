<?php
    
session_start();
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($user === '' || $pass === '') {
        $error = "بيانات الدخول غير صحيحة!";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result ? $result->fetch_assoc() : null;

            $password_ok = false;
            $stored_password = $admin ? (string) $admin['password'] : '';
            $password_info = password_get_info($stored_password);
            $is_hashed = !empty($password_info['algo']);

            if ($admin) {
                $password_ok = $is_hashed
                    ? password_verify($pass, $stored_password)
                    : hash_equals($stored_password, $pass);
            }

            if ($admin && $password_ok) {
                if (!$is_hashed) {
                    $new_hash = password_hash($pass, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");

                    if ($update) {
                        $admin_id = (int) $admin['id'];
                        $update->bind_param("si", $new_hash, $admin_id);
                        $update->execute();
                        $update->close();
                    }
                }

                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "بيانات الدخول غير صحيحة!";
            }

            $stmt->close();
        } else {
            $error = "حدث خطأ مؤقت. حاول مرة أخرى.";
        }
    }
}
?>

<body style="background:#1a1a1a; color:white; font-family:Cairo; display:flex; justify-content:center; align-items:center; height:100vh;">
    <form method="POST" style="background:#242424; padding:30px; border-radius:15px; border:1px solid #ccff00;">
        <h2 style="color:#ccff00; text-align:center;">دخول الإدارة</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <input type="text" name="username" placeholder="اسم المستخدم" required style="display:block; width:100%; margin:10px 0; padding:10px;">
        <input type="password" name="password" placeholder="كلمة المرور" required style="display:block; width:100%; margin:10px 0; padding:10px;">
        <button type="submit" style="width:100%; padding:10px; background:#ccff00; border:none; font-weight:bold; cursor:pointer;">دخول</button>
    </form>
</body>
