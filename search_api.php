<?php
/**
 * ملف البحث الذكي فائق المرونة - Flexor Ultra Logic
 * يعالج الأخطاء الإملائية العنيفة عبر تقنية "الحروف المتفرقة"
 */

ob_start();
require_once 'includes/db.php'; 

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0); 

$query = isset($_GET['q']) ? $_GET['q'] : '';
$results = [];

if (!$conn) {
    ob_clean();
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

if (mb_strlen($query, 'utf-8') >= 2) {
    
    // 1. تنظيف الكلمة
    $q = trim($query);
    
    /* 2. السحر هنا: تحويل الكلمة من "hry" إلى "%h%r%y%"
       هذا يجعل SQL يبحث عن أي عنوان يحتوي على هذه الحروف بنفس الترتيب 
       حتى لو بينها حروف أخرى، مما يحل مشكلة الأخطاء الإملائية الشائعة.
    */
    $fuzzyQuery = '%' . implode('%', mb_str_split($q)) . '%';
    
    // سنضيف أيضاً البحث العادي والبحث الذكي للعربي (الهمزات)
    $smartAr = preg_replace('/[أإآا]/u', '_', $q);
    $smartAr = preg_replace('/[يى]/u', '_', $smartAr);
    $searchTermSmart = "%" . $smartAr . "%";

    // 3. الاستعلام المدمج
    $sql = "SELECT id, title_ar, title_en, poster_path, release_year, content_type 
            FROM movies 
            WHERE title_en LIKE ? 
               OR title_ar LIKE ? 
               OR title_en LIKE ? 
            LIMIT 30";

    if ($stmt = $conn->prepare($sql)) {
        // نمرر: البحث بالحروف المتفرقة، البحث الذكي للعربي، البحث العادي
        $searchTermNormal = "%" . $q . "%";
        $stmt->bind_param("sss", $fuzzyQuery, $searchTermSmart, $searchTermNormal);
        $stmt->execute();
        $result = $stmt->get_result();

        $temp_list = [];
        while ($row = $result->fetch_assoc()) {
            // 4. حساب "درجة التشابه" في PHP لترتيب النتائج
            $d1 = levenshtein(strtolower($q), strtolower($row['title_en']));
            $d2 = levenshtein($q, $row['title_ar']);
            $score = min($d1, $d2);

            // أولوية قصوى إذا كانت الكلمة موجودة كما هي
            if (stripos($row['title_en'], $q) !== false) $score -= 10;
            if (stripos($row['title_ar'], $q) !== false) $score -= 10;

            $temp_list[] = [
                'data' => [
                    'id'     => $row['id'],
                    'title'  => $row['title_ar'] . " (" . $row['title_en'] . ")",
                    'poster' => $row['poster_path'],
                    'year'   => $row['release_year'],
                    'type'   => $row['content_type'],
                    'url'    => "watch/movie/index.php?id=" . $row['id']
                ],
                'score' => $score
            ];
        }

        // ترتيب النتائج حسب الأقرب
        usort($temp_list, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        foreach (array_slice($temp_list, 0, 10) as $item) {
            $results[] = $item['data'];
        }
        $stmt->close();
    }
}

ob_clean();
echo json_encode($results, JSON_UNESCAPED_UNICODE);