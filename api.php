<?php
/**
 * api.php - النسخة الاحترافية (توقيت عالمي + ترتيب ذكي)
 */
header('X-Debug-API: loaded');
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
date_default_timezone_set('UTC'); // نستخدم التوقيت العالمي هنا لضمان الدقة

if (!is_dir('api_cache')) mkdir('api_cache', 0777, true);
if (!is_dir('data')) mkdir('data', 0777, true);

$settingsFile = 'data/settings.json';
$defaultSettings = ['keys' => [], 'cache_time' => 60, 'current_key_index' => 0, 'social' => ['instagram' => '', 'facebook' => '', 'twitter' => '']];
if (!file_exists($settingsFile)) file_put_contents($settingsFile, json_encode($defaultSettings));
$settings = json_decode(file_get_contents($settingsFile), true);

function getCurrentKeyIndex() {
    global $settings;
    return empty($settings['keys']) ? 0 : ($settings['current_key_index'] % count($settings['keys']));
}

function switchToNextKey() {
    global $settings, $settingsFile;
    if (empty($settings['keys'])) return;
    $settings['current_key_index'] = (getCurrentKeyIndex() + 1) % count($settings['keys']);
    file_put_contents($settingsFile, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$translations = json_decode(file_get_contents('teams_ar.json'), true) ?: [];

function translateStatus($code, $elapsed = 0) {
    if (!$code || $code === 'NS') return 'لم تبدأ بعد';
    $map = [
        '1H' => 'مباشر الآن', 'HT' => 'بين الشوطين', '2H' => 'مباشر الآن',
        'ET' => 'مباشر الآن', 'P' => 'ركلات ترجيح', 'FT' => 'انتهت المباراة',
        'AET' => 'انتهت المباراة', 'PEN' => 'انتهت المباراة', 'LIVE' => 'مباشر الآن',
    ];
    $text = $map[$code] ?? 'مباشر الآن';
    if (in_array($code, ['1H', '2H', 'ET', 'LIVE']) && $elapsed > 0) $text .= " - $elapsed'";
    return $text;
}

function smartSort(&$matches) {
    usort($matches, function($a, $b) {
        $priority = ['live' => 1, 'upcoming' => 2, 'finished' => 3];
        $statusA = $a['status'] ?? 'upcoming'; $statusB = $b['status'] ?? 'upcoming';
        if ($priority[$statusA] != $priority[$statusB]) return $priority[$statusA] - $priority[$statusB];
        // الترتيب بالبصمة الزمنية (Timestamp)
        $timeA = $a['timestamp'] ?? 0; $timeB = $b['timestamp'] ?? 0;
        return $timeA - $timeB;
    });
}

function fetchWithCache($apiDate, $isToday = true) {
    $cachePath = "api_cache/fixtures_$apiDate.json";
    $expireTime = $isToday ? 600 : (23 * 3600);
    if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $expireTime) {
        return ['data' => json_decode(file_get_contents($cachePath), true)];
    }
    // جلب البيانات بجرينتش (UTC)
    $result = fetchFromApi("date=$apiDate&timezone=UTC");
    if (!isset($result['error'])) file_put_contents($cachePath, json_encode($result['data'], JSON_UNESCAPED_UNICODE));
    return $result;
}

function mapToUserFormat($m) {
    global $translations;
    $statusType = 'upcoming';
    $rawStatus = $m['event_status'] ?? 'NS';
    if (in_array($rawStatus, ['FT', 'AET', 'PEN'])) $statusType = 'finished';
    else if (in_array($rawStatus, ['1H', '2H', 'HT', 'ET', 'P', 'LIVE'])) $statusType = 'live';
    $statusText = translateStatus($rawStatus, $m['event_elapsed'] ?? 0);
    
    $home = $m['event_home_team_ar'] ?? $m['event_home_team'];
    $away = $m['event_away_team_ar'] ?? $m['event_away_team'];
    if (isset($translations[$home])) $home = $translations[$home];
    if (isset($translations[$away])) $away = $translations[$away];
    
    return [
        'id' => $m['id'], 'status' => $statusType, 'status_text' => $statusText,
        'homeTeam' => $home, 'awayTeam' => $away,
        'homeLogo' => $m['event_home_team_logo'] ?? '', 'awayLogo' => $m['event_away_team_logo'] ?? '',
        'homeScore' => $m['event_home_team_score'] ?? 0, 'awayScore' => $m['event_away_team_score'] ?? 0,
        'time' => $m['event_time'], 
        'timestamp' => $m['timestamp'] ?? 0, // نرسل البصمة الزمنية للتحويل المحلي
        'day' => $m['day'] ?? 'today', 'league' => $m['league_name'] ?? '',
        'commentator' => $m['commentator'] ?? 'غير معروف', 'channel' => $m['channel'] ?? 'غير معروف', 'streamUrl' => $m['streamUrl'] ?? ''
    ];
}

function fetchFromApi($query) {
    global $settings;
    if (empty($settings['keys'])) return ['error' => 'No Keys'];
    $keys = $settings['keys']; $maxAttempts = count($keys); $attempt = 0;
    while ($attempt < $maxAttempts) {
        $idx = getCurrentKeyIndex();
        $ch = curl_init("https://v3.football.api-sports.io/fixtures?$query");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: " . $keys[$idx]]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $data = json_decode($response, true);
        if ($httpCode === 200 && empty($data['errors'])) return ['data' => $data['response'] ?? []];
        switchToNextKey(); $attempt++;
    }
    return ['error' => 'All keys failed'];
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'matches') {
        $matches = json_decode(file_get_contents('data/matches.json'), true) ?: [];
        $res = array_map('mapToUserFormat', $matches);
        smartSort($res); echo json_encode($res, JSON_UNESCAPED_UNICODE); exit;
    }

    if ($action === 'delete-match') {
        $matches = json_decode(file_get_contents('data/matches.json'), true) ?: [];
        $newMatches = array_filter($matches, function($m) use ($input) { return (string)$m['id'] !== (string)$input['matchId']; });
        file_put_contents('data/matches.json', json_encode(array_values($newMatches), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'save-stream') {
        $matches = json_decode(file_get_contents('data/matches.json'), true) ?: [];
        $found = false;
        foreach ($matches as &$m) {
            if ((string)$m['id'] === (string)$input['matchId']) {
                $m['streamUrl'] = $input['streamUrl'];
                $m['channel'] = $input['channel'] ?? $m['channel'];
                $found = true; break;
            }
        }
        if (!$found) {
            $matches[] = [
                'id' => $input['matchId'], 'event_key' => $input['matchId'],
                'event_home_team' => $input['homeTeam'], 'event_away_team' => $input['awayTeam'],
                'event_home_team_logo' => $input['homeLogo'] ?? '', 'event_away_team_logo' => $input['awayLogo'] ?? '',
                'league_name' => $input['league'] ?? '', 'event_time' => $input['time'],
                'timestamp' => $input['timestamp'] ?? 0,
                'day' => $input['day'] ?? 'today', 'event_status' => $input['status'] ?? 'upcoming',
                'event_elapsed' => 0, 'streamUrl' => $input['streamUrl'] ?? '', 'channel' => $input['channel'] ?? 'غير معروف'
            ];
        }
        file_put_contents('data/matches.json', json_encode($matches, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'api-fixtures') {
        $dateStr = $_GET['date'] ?? 'today'; $apiDate = date('Y-m-d'); $isToday = true;
        if ($dateStr === 'tomorrow') { $apiDate = date('Y-m-d', strtotime('+1 day')); $isToday = false; }
        if ($dateStr === 'yesterday') { $apiDate = date('Y-m-d', strtotime('-1 day')); $isToday = false; }
        $fixturesResult = fetchWithCache($apiDate, $isToday);
        if (isset($fixturesResult['error'])) echo json_encode(['status' => 'error', 'message' => $fixturesResult['error']]);
        else {
            $res = [];
            foreach ($fixturesResult['data'] as $f) {
                $rawStatus = $f['fixture']['status']['short']; $statusType = 'upcoming';
                if (in_array($rawStatus, ['FT', 'AET', 'PEN'])) $statusType = 'finished';
                else if (in_array($rawStatus, ['1H', '2H', 'HT', 'ET', 'P', 'LIVE'])) $statusType = 'live';
                
                $res[] = [
                    'id' => $f['fixture']['id'], 'homeTeam' => $f['teams']['home']['name'], 'awayTeam' => $f['teams']['away']['name'], 
                    'homeLogo' => $f['teams']['home']['logo'], 'awayLogo' => $f['teams']['away']['logo'], 
                    'league' => $f['league']['name'], 
                    'time' => date('H:i', $f['fixture']['timestamp']), // وقت بنظام 24 ساعة للتحويل
                    'timestamp' => $f['fixture']['timestamp'],
                    'status' => $statusType, 
                    'score' => ($f['goals']['home'] ?? '0') . ' - ' . ($f['goals']['away'] ?? '0')
                ];
            }
            smartSort($res); echo json_encode($res);
        }
        exit;
    }

    if ($action === 'admin-login') {
        if ($input['username'] === 'admin' && $input['password'] === 'admin2024') echo json_encode(['success' => true]);
        else echo json_encode(['success' => false]); exit;
    }

    if ($action === 'get_settings') { echo json_encode($settings); exit; }
    if ($action === 'save_settings') {
        $settings['keys'] = $input['keys']; $settings['cache_time'] = (int)$input['cache_time'];
        if (isset($input['social'])) $settings['social'] = $input['social'];
        file_put_contents($settingsFile, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']); exit;
    }

    // --- نظام الأخبار ---
    $newsFile = 'data/news.json';
    if (!is_dir('data')) @mkdir('data', 0777, true);
    
    function getNews() { 
        global $newsFile; 
        if (!file_exists($newsFile)) return [];
        $content = @file_get_contents($newsFile);
        if (empty($content)) return [];
        return json_decode($content, true) ?: []; 
    }

    // وظيفة ذكية لحذف الصورة من السيرفر فقط إذا لم تكن مستخدمة في أي مقال آخر
    function deleteImageIfUnused($imgUrl, $excludeId = null) {
        if (strpos($imgUrl, 'uploads/') === false) return;
        $news = getNews();
        foreach ($news as $n) {
            if ($n['id'] !== $excludeId && $n['image'] === $imgUrl) {
                return; // الصورة لا تزال مستخدمة، لا تحذفها
            }
        }
        // إذا وصلنا هنا، الصورة غير مستخدمة
        $parts = explode('uploads/', $imgUrl);
        $path = 'uploads/' . end($parts);
        if (file_exists($path)) @unlink($path);
    }

    if ($action === 'news') {
        $news = getNews();
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            foreach ($news as $n) if ($n['id'] == $id) { echo json_encode($n); exit; }
            echo json_encode(['error' => 'Not found']); exit;
        }
        echo json_encode(array_reverse($news)); // إظهار الأحدث أولاً
        exit;
    }

    if ($action === 'delete-image') {
        $url = $input['url'];
        if (strpos($url, 'uploads/') !== false) {
            $parts = explode('uploads/', $url);
            $path = 'uploads/' . end($parts);
            if (file_exists($path)) {
                unlink($path);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'File not found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Not a local file']);
        }
        exit;
    }

    if ($action === 'save-news') {
        $news = getNews();
        $article = $input;
        if (!isset($article['id']) || empty($article['id'])) {
            // نظام Auto-increment: البحث عن أكبر ID موجود وإضافة 1
            $maxId = 0;
            foreach ($news as $n) {
                if (is_numeric($n['id']) && $n['id'] > $maxId) {
                    $maxId = (int)$n['id'];
                }
            }
            $article['id'] = $maxId + 1;
            $news[] = $article;
        } else {
            // التعديل: التأكد من تحويل الـ ID لرقم إذا كان رقمياً
            $article['id'] = is_numeric($article['id']) ? (int)$article['id'] : $article['id'];
            foreach ($news as &$n) {
                if ($n['id'] == $article['id']) {
                    if ($n['image'] !== $article['image']) {
                        deleteImageIfUnused($n['image'], $article['id']);
                    }
                    $n = $article; 
                    break; 
                }
            }
        }
        file_put_contents($newsFile, json_encode($news, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'delete-news') {
        $news = getNews(); $id = $input['id'];
        // البحث عن الخبر لحذف صورته إذا كانت غير مستخدمة
        foreach ($news as $n) {
            if ($n['id'] === $id) {
                deleteImageIfUnused($n['image'], $id);
                break;
            }
        }
        $news = array_values(array_filter($news, function($n) use ($id) { return $n['id'] !== $id; }));
        file_put_contents($newsFile, json_encode($news, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'clean-orphans') {
        $news = getNews();
        $matches = json_decode(file_get_contents('data/matches.json'), true) ?: [];
        
        // جمع كل الروابط المستخدمة في الأخبار والمباريات
        $usedImages = [];
        foreach ($news as $n) if (!empty($n['image'])) $usedImages[] = basename($n['image']);
        foreach ($matches as $m) {
            if (!empty($m['homeLogo'])) $usedImages[] = basename($m['homeLogo']);
            if (!empty($m['awayLogo'])) $usedImages[] = basename($m['awayLogo']);
        }

        $files = glob('uploads/*');
        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                if (!in_array($filename, $usedImages)) {
                    @unlink($file);
                    $deletedCount++;
                }
            }
        }
        echo json_encode(['success' => true, 'deleted' => $deletedCount]);
        exit;
    }

    if ($action === 'upload-image') {
        if (!isset($_FILES['image'])) { echo json_encode(['success' => false, 'error' => 'No image']); exit; }
        
        // إنشاء المجلد فقط عند الحاجة (إذا لم يكن موجوداً)
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $file = $_FILES['image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        $target = 'uploads/' . $fileName;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']);
            echo json_encode(['success' => true, 'url' => rtrim($baseUrl, '/') . '/' . $target]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
        exit;
    }
}

// تحديث النتائج الحية
$matchesData = json_decode(file_get_contents('data/matches.json'), true) ?: [];
$GLOBAL_CACHE = 'api_cache/live_status.json';
if ((!file_exists($GLOBAL_CACHE) || (time() - filemtime($GLOBAL_CACHE)) >= $settings['cache_time']) && !empty($settings['keys']) && !empty($matchesData)) {
    $ids = array_column($matchesData, 'id');
    $apiMatches = fetchFromApi("ids=" . implode(',', $ids));
    if ($apiMatches && isset($apiMatches['data'])) {
        foreach ($matchesData as &$m) {
            foreach ($apiMatches['data'] as $am) {
                if ((int)$m['id'] === (int)$am['fixture']['id']) {
                    $m['event_status'] = $am['fixture']['status']['short']; $m['event_elapsed'] = $am['fixture']['status']['elapsed'] ?? 0;
                    $m['event_home_team_score'] = $am['goals']['home'] ?? '0'; $m['event_away_team_score'] = $am['goals']['away'] ?? '0';
                    break;
                }
            }
        }
        file_put_contents('data/matches.json', json_encode($matchesData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        file_put_contents($GLOBAL_CACHE, json_encode(['time' => time()]));
    }
}
$finalRes = array_map('mapToUserFormat', $matchesData);
smartSort($finalRes); if (!isset($_GET['action'])) echo json_encode($finalRes, JSON_UNESCAPED_UNICODE);
