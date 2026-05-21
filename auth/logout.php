<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index.php"); // العودة للرئيسية بعد الخروج
exit();
?>