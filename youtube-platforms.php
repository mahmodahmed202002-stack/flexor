<?php
include('includes/db.php');

// ✅ SEO VARIABLES
$page_title = "كورسات وفيديوهات تعليمية | Flexor";
$page_desc  = "شاهد أفضل الكورسات والفيديوهات التعليمية المجمعة من يوتيوب في مكان واحد";
$page_img   = "https://flexor.gt.tc/public/logo.png";

// ✅ HEADER (فيه <html> و <head>)
include('includes/header.php'); 

// 1. استدعاء ملف الكارت
$yt_card_file = 'includes/components/youtube-card.php';
if (file_exists($yt_card_file)) {
    include($yt_card_file);
}

// 2. جلب القنوات
$channels = $conn->query("SELECT * FROM yt_channels ORDER BY sort_order ASC");
?>

<!-- ✅ STRUCTURED DATA -->
<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "CollectionPage",
 "name": "كورسات وفيديوهات تعليمية",
 "description": "أفضل الكورسات والفيديوهات التعليمية من مصادر موثوقة",
 "url": "https://flexor.gt.tc/youtube-platforms.php"
}
</script>
<style>
/* نفس التصميم بدون أي تغيير */
.yt-container {
    padding: 120px 4% 50px;
    background: #050505;
    min-height: 100vh;
}

.section-title {
    font-size: 28px;
    font-weight: 900;
    margin-bottom: 40px;
    border-right: 5px solid #d4ff00;
    padding-right: 15px;
    color: #fff;
}

.channels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

@media (max-width: 768px) {
    .yt-container { padding-top: 100px; }
    .channels-grid { 
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); 
        gap: 15px; 
    }
}
</style>

<div class="yt-container">
    <h1 class="section-title">منصات يوتيوب المختارة</h1>

    <div class="channels-grid">
        <?php 
        if($channels && $channels->num_rows > 0):
            while($row = $channels->fetch_assoc()): 
                if (function_exists('renderYoutubeCard')) {
                    renderYoutubeCard($row, $conn); 
                }
            endwhile; 
        endif;
        ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>