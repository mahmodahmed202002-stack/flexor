<?php
include('../includes/db.php');

if(isset($_POST['import'])) {
    $m3u_url = $_POST['m3u_url'];
    $m3u_content = file_get_contents($m3u_url); // قراءة محتوى الرابط
    
    // تقسيم المحتوى بناءً على كلمة #EXTINF
    $lines = explode('#EXTINF:', $m3u_content);
    $count = 0;

    foreach($lines as $line) {
        if(empty($line) || strpos($line, '#EXTM3U') !== false) continue;

        // استخراج اللوجو
        preg_match('/tvg-logo="([^"]*)"/', $line, $logo_match);
        $logo = $logo_match[1] ?? '';

        // استخراج اسم القناة
        preg_match('/,(.*)\n/', $line, $name_match);
        $name = trim($name_match[1] ?? 'Unknown Channel');

        // استخراج الرابط (السطر الذي يليه)
        $url_lines = explode("\n", $line);
        $url = trim($url_lines[count($url_lines)-1]);

        if(!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $stmt = $conn->prepare("INSERT INTO live_channels (channel_name, stream_url, logo_url) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $url, $logo);
            $stmt->execute();
            $count++;
        }
    }
    echo "تم استيراد $count قناة بنجاح!";
}
?>

<form method="post" style="padding: 20px; color: #fff; background: #111;">
    <h3>استيراد قنوات من رابط M3U</h3>
    <input type="text" name="m3u_url" placeholder="ضع رابط الـ M3U هنا" style="width: 80%; padding: 10px;">
    <button type="submit" name="import" style="padding: 10px 20px; background: #d4ff00; border: none; cursor: pointer;">بدء الاستيراد</button>
</form>