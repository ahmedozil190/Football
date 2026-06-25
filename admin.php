<?php
session_start();
$matchesFile = 'data/matches.json';
$newsFile = 'data/news.json';

if (isset($_POST['login'])) {
    if ($_POST['user'] === 'admin' && $_POST['pass'] === '123456') { $_SESSION['a'] = true; header("Location: /admin"); exit; }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: /admin"); exit; }
$auth = isset($_SESSION['a']);
$sec = isset($_GET['section']) ? $_GET['section'] : 'main';
$news = json_decode(@file_get_contents($newsFile), true);
if(!$news) $news = array();

if ($auth) {
    if (isset($_GET['del_m'])) {
        $ms = json_decode(@file_get_contents($matchesFile), true) ?: [];
        $ms = array_filter($ms, function($v) { return $v['id'] != $_GET['del_m']; });
        file_put_contents($matchesFile, json_encode(array_values($ms), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $back = isset($_GET['section']) ? $_GET['section'] : 'main';
        header("Location: /admin?section=$back"); exit;
    }
    if (isset($_GET['del_n'])) {
        $ns = json_decode(@file_get_contents($newsFile), true) ?: [];
        $ns = array_filter($ns, function($v) { return $v['id'] != $_GET['del_n']; });
        file_put_contents($newsFile, json_encode(array_values($ns), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        header("Location: /admin?section=news"); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_m'])) {
            $d = json_decode(@file_get_contents($matchesFile), true) ?: [];
            $d[] = array('id'=>time(),'homeTeam'=>$_POST['h'],'awayTeam'=>$_POST['a'],'homeLogo'=>$_POST['hl'],'awayLogo'=>$_POST['al'],'league'=>$_POST['l'],'time'=>$_POST['t'],'status'=>$_POST['s'],'status_text'=>$_POST['st'],'day'=>(isset($_POST['d'])?$_POST['d']:'today'),'channel'=>$_POST['c'],'stream_url'=>$_POST['u'],'homeScore'=>0,'awayScore'=>0);
            file_put_contents($matchesFile, json_encode($d, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            header("Location: /admin?section=add_m&success=1"); exit;
        }
        if (isset($_POST['add_n'])) {
            $imgPath = isset($_POST['i']) ? $_POST['i'] : '';
            if (isset($_FILES['img_file']) && $_FILES['img_file']['error'] === 0) {
                $dir = 'uploads/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $ext = pathinfo($_FILES['img_file']['name'], PATHINFO_EXTENSION);
                $newName = time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['img_file']['tmp_name'], $dir . $newName)) {
                    $imgPath = '/' . $dir . $newName;
                }
            }
            $d = json_decode(@file_get_contents($newsFile), true);
            if(!$d) $d = array();
            $d[] = array('id'=>time(),'title'=>$_POST['t'],'image'=>$imgPath,'content'=>$_POST['c'],'date'=>date('Y-m-d'));
            file_put_contents($newsFile, json_encode($d, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            header("Location: /admin?section=news&success=1"); exit;
        }
        if (isset($_POST['save_edit'])) {
            $mid = $_POST['edit_match_id'];
            $ms = json_decode(@file_get_contents($matchesFile), true) ?: [];
            foreach ($ms as &$m) {
                if ($m['id'] == $mid) {
                    $m['status']      = isset($_POST['edit_status']) ? $_POST['edit_status'] : $m['status'];
                    $m['channel']     = isset($_POST['edit_channel']) ? $_POST['edit_channel'] : (isset($m['channel']) ? $m['channel'] : '');
                    $m['commentator'] = isset($_POST['edit_commentator']) ? $_POST['edit_commentator'] : (isset($m['commentator']) ? $m['commentator'] : '');
                    $m['score']       = isset($_POST['edit_score']) ? $_POST['edit_score'] : (isset($m['score']) ? $m['score'] : 'vs');
                    $m['stream_url']  = isset($_POST['edit_stream']) ? $_POST['edit_stream'] : (isset($m['stream_url']) ? $m['stream_url'] : '');
                    $statusMap = array('live'=>'جارية الآن','upcoming'=>'قادمة','finished'=>'انتهت');
                    $m['status_text'] = isset($statusMap[$m['status']]) ? $statusMap[$m['status']] : $m['status'];
                    break;
                }
            }
            file_put_contents($matchesFile, json_encode(array_values($ms), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            header("Location: /admin?section=current"); exit;
        }
        if (isset($_POST['save_news_edit'])) {
            $nid = $_POST['edit_news_id'];
            $ns = json_decode(@file_get_contents($newsFile), true) ?: [];
            foreach ($ns as &$n) {
                if ($n['id'] == $nid) {
                    $n['title']   = $_POST['n_t'];
                    $n['content'] = $_POST['n_c'];
                    if(!empty($_POST['n_i'])) $n['image'] = $_POST['n_i'];
                    if (isset($_FILES['n_img_file']) && $_FILES['n_img_file']['error'] === 0) {
                        $dir = 'uploads/';
                        $ext = pathinfo($_FILES['n_img_file']['name'], PATHINFO_EXTENSION);
                        $newName = time() . '_' . rand(100, 999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['n_img_file']['tmp_name'], $dir . $newName)) {
                            $n['image'] = '/' . $dir . $newName;
                        }
                    }
                    break;
                }
            }
            file_put_contents($newsFile, json_encode(array_values($ns), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            header("Location: /admin?section=news"); exit;
        }
        if (isset($_POST['clean_imgs'])) {
            $files = glob('uploads/*');
            $ns = json_decode(@file_get_contents($newsFile), true) ?: [];
            $ms = json_decode(@file_get_contents($matchesFile), true) ?: [];
            $used = [];
            foreach($ns as $n) if(!empty($n['image'])) $used[] = basename($n['image']);
            foreach($ms as $m) {
                if(!empty($m['homeLogo'])) $used[] = basename($m['homeLogo']);
                if(!empty($m['awayLogo'])) $used[] = basename($m['awayLogo']);
            }
            $count = 0;
            foreach($files as $f) {
                if(!in_array(basename($f), $used)) { unlink($f); $count++; }
            }
            header("Location: /admin?section=news&cleaned=$count"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t);</script>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root[data-theme='dark'] { --bg:#0b0f19; --card:#111827; --text:#f3f4f6; --text-dim:#9ca3af; --border:rgba(255,255,255,0.05); --header:rgba(255,255,255,0.02); }
        :root[data-theme='light'] { --bg:#f3f4f6; --card:#ffffff; --text:#111827; --text-dim:#4b5563; --border:rgba(0,0,0,0.05); --header:rgba(0,0,0,0.02); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Cairo'; display:flex; min-height:100vh; }
        .side { width:260px; background:var(--card); border-left:1px solid var(--border); position:fixed; height:100vh; right:0; z-index:100; display:flex; flex-direction:column; }
        .main { flex:1; margin-right:260px; padding:35px; }
        .nav-item { padding:14px 20px; color:var(--text-dim); text-decoration:none; display:flex; align-items:center; gap:12px; font-weight:700; transition:0.3s; margin:4px 10px; border-radius:10px; }
        .nav-item:hover, .nav-item.active { background:#6366f1; color:#fff; }
        .stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:25px; }
        .stat-card { background:var(--card); padding:35px 25px; border-radius:15px; border:1px solid var(--border); border-bottom-width:4px; position:relative; text-align:center; }
        .stat-card.total { border-bottom-color:#6366f1; } .stat-card.live { border-bottom-color:#10b981; } .stat-card.waiting { border-bottom-color:#f59e0b; } .stat-card.finished { border-bottom-color:#8b5cf6; } 
        .stat-card h3 { font-size:36px; font-weight:800; margin-bottom:8px; } .stat-card p { color:var(--text-dim); font-weight:700; font-size:15px; }
        .stat-card i { position:absolute; left:18px; top:18px; font-size:20px; opacity:0.9; }
        .stat-card.total i { color:#6366f1; } .stat-card.live i { color:#10b981; } .stat-card.waiting i { color:#f59e0b; } .stat-card.finished i { color:#8b5cf6; } 
        .recent-card { background:var(--card); border:1px solid var(--border); border-radius:15px; overflow:hidden; }
        .recent-header { padding:25px 30px; border-bottom:1px solid var(--border); display:flex; justify-content:flex-start; align-items:center; gap:12px; }
        .recent-header h3 { font-size:19px; font-weight:800; margin:0; } .recent-header i { color:#6366f1; font-size:22px; }
        table { width:100%; border-collapse:collapse; }
        thead { background:var(--header); }
        th { padding:18px 20px; color:var(--text-dim); font-size:14px; font-weight:800; text-align:right; border-bottom:1px solid var(--border); }
        td { padding:18px 20px; border-bottom:1px solid var(--border); font-size:14px; color:var(--text); }
        .status-live { color:#10b981; font-weight:800; }
        .btn-del { color:#ef4444; background:rgba(239,68,68,0.08); width:36px; height:36px; border-radius:10px; text-decoration:none !important; display:inline-flex; align-items:center; justify-content:center; font-size:16px; transition:0.3s; border:1px solid rgba(239,68,68,0.1); }
        .btn-del:hover { background:#ef4444; color:#fff; text-decoration:none !important; }
        .btn-edit { color:#6366f1; background:rgba(99,102,241,0.08); width:36px; height:36px; border-radius:10px; border:1px solid rgba(99,102,241,0.1); cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:0.3s; text-decoration:none !important; font-size:16px; }
        .btn-edit:hover { background:#6366f1; color:#fff; text-decoration:none !important; }
        table a { text-decoration: none !important; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display:block; margin-bottom:8px; font-size:13px; font-weight:700; color:var(--text-dim); }
        .form-input { width:100%; padding:12px 15px; background:var(--bg); border:1px solid var(--border); color:var(--text); border-radius:10px; font-family:'Cairo'; font-size:14px; outline:none; transition:0.3s; }
        .form-input:focus { border-color:#6366f1; box-shadow: 0 0 10px rgba(99,102,241,0.1); }
        .image-input-group { display: flex; align-items: center; gap: 8px; }
        .upload-btn-icon { width:45px; height:45px; background:var(--bg); border:1px solid var(--border); border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-dim); cursor:pointer; transition:0.3s; flex-shrink:0; }
        .upload-btn-icon:hover { border-color:#6366f1; color:#6366f1; background:rgba(99,102,241,0.05); }
        .mini-preview { width:45px; height:45px; border-radius:10px; background-size:cover; background-position:center; border:1px solid var(--border); display:none; flex-shrink:0; position:relative; }
        .sidebar-footer { margin-top:auto; padding:20px; border-top:1px solid var(--border); display:flex; justify-content:space-between; }
        .f-icon { width:44px; height:44px; background:rgba(255,255,255,0.03); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--text-dim); cursor:pointer; }
        .day-tabs { display:flex; gap:6px; margin-bottom:20px; background:var(--card); padding:5px; border-radius:12px; width:fit-content; border:1px solid var(--border); }
        .day-tab { padding:7px 18px; border-radius:8px; cursor:pointer; font-weight:700; font-size:13px; color:var(--text-dim); transition:all 0.2s; }
        .day-tab:hover { color:var(--text); } .day-tab.active { background:#6366f1; color:#fff; }
        .badge-live { color:#10b981; background:rgba(16,185,129,0.1); padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; }
        .badge-upcoming { color:#f59e0b; background:rgba(245,158,11,0.1); padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; }
        .badge-finished { color:#9ca3af; background:rgba(100,116,139,0.1); padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:2000; display:none; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:var(--card); width:100%; max-width:600px; border-radius:20px; border:1px solid var(--border); overflow:hidden; }
        .modal-head { padding:20px 25px; border-bottom:1px solid var(--border); font-size:18px; font-weight:800; }
        .modal-body { padding:25px; display:grid; grid-template-columns:1fr 1fr; gap:16px; } .modal-body .full { grid-column:1/-1; }
        .modal-foot { padding:16px 25px; background:rgba(0,0,0,0.1); display:flex; justify-content:flex-end; gap:10px; }
        .modal-body label { display:block; margin-bottom:6px; font-size:12px; font-weight:700; color:var(--text-dim); }
        .modal-body input, .modal-body select { width:100%; padding:10px 14px; background:var(--bg); border:1px solid var(--border); color:var(--text); border-radius:8px; font-family:'Cairo'; font-size:13px; }
        .btn-primary-sm { background:#6366f1; color:#fff; padding:9px 20px; border:none; border-radius:8px; font-weight:800; cursor:pointer; font-family:'Cairo'; font-size:14px; }
        .btn-cancel-sm { background:transparent; color:var(--text-dim); padding:9px 20px; border:1px solid var(--border); border-radius:10px; font-weight:700; cursor:pointer; font-family:'Cairo'; font-size:14px; }
        .btn-purple { background:#6366f1; color:#fff; border:none; padding:8px 16px; border-radius:8px; font-weight:700; font-size:12px; font-family:'Cairo'; cursor:pointer; transition:0.3s; display:flex; align-items:center; gap:8px; text-decoration:none !important; }
        .btn-purple:hover { background:#4f46e5; box-shadow:0 0 15px rgba(99,102,241,0.3); }
        
        /* Toast Notification Styles */
        .toast-container { position:fixed; bottom:25px; left:25px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
        .toast { background:var(--card); color:var(--text); padding:14px 22px; border-radius:12px; border:1px solid var(--border); border-left:4px solid #6366f1; box-shadow:0 10px 25px rgba(0,0,0,0.3); font-weight:700; font-size:14px; transform:translateX(-120%); transition:0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55); display:flex; align-items:center; gap:12px; }
        .toast.show { transform:translateX(0); }
        .toast.success { border-left-color:#10b981; }
    </style>
</head>
<body>
<?php if (!$auth): ?>
    <div style="flex:1; display:flex; align-items:center; justify-content:center;">
        <form method="POST" style="background:var(--card); padding:40px; border-radius:20px; border:1px solid var(--border); width:380px;">
            <h2 style="text-align:center; font-weight:800; margin-bottom:30px;">دخول النظام</h2>
            <input type="text" name="user" style="width:100%; padding:14px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:10px; margin-bottom:15px; box-sizing:border-box;" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" style="width:100%; padding:14px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:10px; margin-bottom:25px; box-sizing:border-box;" placeholder="كلمة المرور" required>
            <button type="submit" name="login" style="width:100%; padding:14px; background:#6366f1; color:#fff; border:none; border-radius:10px; font-weight:800; cursor:pointer;">دخول</button>
        </form>
    </div>
<?php else: ?>
    <aside class="side">
        <div style="padding:30px; font-size:24px; font-weight:800; color:#6366f1; text-align:center; border-bottom:1px solid var(--border);">كورة لايف</div>
        <div style="padding-top:20px;">
            <a href="/admin?section=main" class="nav-item <?php echo $sec=='main'?'active':''; ?>"><i class="fa-solid fa-chart-pie"></i> نظرة عامة</a>
            <a href="/admin?section=current" class="nav-item <?php echo $sec=='current'?'active':''; ?>"><i class="fa-solid fa-list-check"></i> المباريات الحالية</a>
            <a href="/admin?section=add_m" class="nav-item <?php echo $sec=='add_m'?'active':''; ?>"><i class="fa-solid fa-plus-circle"></i> إضافة مباراة</a>
            <a href="/admin?section=news" class="nav-item <?php echo ($sec=='news'||$sec=='add_n')?'active':''; ?>"><i class="fa-solid fa-newspaper"></i> إدارة الأخبار</a>
        </div>
        <div class="sidebar-footer">
            <div id="adm-theme" class="f-icon"><i class="fa-solid fa-moon"></i></div>
            <a href="/admin?logout=1" class="f-icon" style="color:#ef4444;"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </aside>
    <main class="main">
        <div class="toast-container" id="toast-container"></div>
        <?php if($sec == 'main'): ?>
            <h2 style="font-weight:800; margin-bottom:25px;">نظرة عامة</h2>
            <?php 
                $matches = json_decode(@file_get_contents($matchesFile), true) ?: [];
                $total = count($matches); $live = 0; $wait = 0; $done = 0;
                foreach($matches as $m) {
                    $s = isset($m['status']) ? $m['status'] : '';
                    if($s == 'live') $live++; elseif($s == 'finished') $done++; else $wait++;
                }
            ?>
            <div class="stats-grid">
                <div class="stat-card total"><i class="fa-solid fa-futbol"></i><h3><?php echo $total; ?></h3><p>إجمالي المباريات</p></div>
                <div class="stat-card live"><i class="fa-solid fa-tower-broadcast"></i><h3><?php echo $live; ?></h3><p>مباريات جارية</p></div>
                <div class="stat-card waiting"><i class="fa-solid fa-clock"></i><h3><?php echo $wait; ?></h3><p>بانتظار البداية</p></div>
                <div class="stat-card finished"><i class="fa-solid fa-check-double"></i><h3><?php echo $done; ?></h3><p>مباريات منتهية</p></div>
            </div>
            <div class="recent-card">
                <div class="recent-header" style="justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:12px;"><i class="fa-solid fa-futbol"></i><h3>آخر المباريات المضافة</h3></div>
                    <div class="day-tabs" style="margin-bottom:0;">
                        <div class="day-tab" data-day="yesterday" onclick="switchDay(this)">مباريات الأمس</div>
                        <div class="day-tab active" data-day="today" onclick="switchDay(this)">مباريات اليوم</div>
                        <div class="day-tab" data-day="tomorrow" onclick="switchDay(this)">مباريات الغد</div>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>المباراة</th><th>البطولة</th><th>الوقت</th><th>الحالة</th><th>البث</th><th>التحكم</th></tr></thead>
                        <tbody id="ov-tbody">
                        <?php foreach(['today','yesterday','tomorrow'] as $dayKey):
                            $dayM = array_filter($matches, function($m) use ($dayKey) { return (isset($m['day'])?$m['day']:'today') === $dayKey; });
                            $dayM = array_slice(array_reverse($dayM), 0, 5); 
                            $isVisible = $dayKey === 'today' ? '' : ' style="display:none;"';
                        ?>
                        <tr data-day="<?php echo $dayKey; ?>" data-empty="1"<?php echo (!empty($dayM) ? ' style="display:none;"' : $isVisible); ?>>
                            <td colspan="6"><div class="empty-box"><i class="fa-solid fa-futbol"></i><p>لا توجد مباريات مضافة لهذا اليوم</p></div></td>
                        </tr>
                        <?php foreach($dayM as $m): $statusClass = (isset($m['status']) && $m['status'] === 'live') ? 'status-live' : ''; ?>
                         <tr data-day="<?php echo $dayKey; ?>"<?php echo $isVisible; ?>>
                         <tr data-day="<?php echo $dayKey; ?>"<?php echo $isVisible; ?>>
                             <td><?php echo htmlspecialchars($m['homeTeam'] . " vs " . $m['awayTeam']); ?></td>
                             <td><?php echo htmlspecialchars(isset($m['league'])?$m['league']:'--'); ?></td>
                             <td><?php echo date("h:i A", strtotime($m['time'])); ?></td>
                             <td class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars(isset($m['status_text'])?$m['status_text']:'--'); ?></td>
                             <td style="font-size:16px;"><?php echo !empty($m['stream_url']) && $m['stream_url'] !== '#' ? '✅' : '❌'; ?></td>
                             <td>
                                <div style="display:flex; gap:8px;">
                                    <button class="btn-edit" onclick="openEditModal(this)" data-match='<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES); ?>'><i class="fa-solid fa-pen"></i></button>
                                    <a href="/admin?del_m=<?php echo $m['id']; ?>&section=main" class="btn-del" onclick="return confirm('حذف؟')"><i class="fa-solid fa-trash"></i></a>
                                </div>
                             </td>
                         </tr>
                        <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif($sec == 'current'):
            $allM = json_decode(@file_get_contents($matchesFile), true) ?: [];
            $cur_total = count($allM); $cur_live = count(array_filter($allM, function($m) { return (isset($m['status'])?$m['status']:'') === 'live'; }));
            $cur_wait = count(array_filter($allM, function($m) { return (isset($m['status'])?$m['status']:'') === 'upcoming'; }));
            $cur_done = count(array_filter($allM, function($m) { return (isset($m['status'])?$m['status']:'') === 'finished'; }));
        ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                <h2 style="font-weight:800;">المباريات الحالية</h2>
                <input type="text" id="cur-search" placeholder="بحث..." oninput="filterMatches()" style="padding:9px 14px; background:var(--bg); border:1px solid var(--border); color:var(--text); border-radius:8px; font-family:'Cairo'; font-size:13px; width:250px;">
            </div>
            <div class="stats-grid">
                <div class="stat-card total"><i class="fa-solid fa-futbol"></i><h3><?php echo $cur_total; ?></h3><p>إجمالي</p></div>
                <div class="stat-card live"><i class="fa-solid fa-tower-broadcast"></i><h3><?php echo $cur_live; ?></h3><p>جارية</p></div>
                <div class="stat-card waiting"><i class="fa-solid fa-clock"></i><h3><?php echo $cur_wait; ?></h3><p>قادمة</p></div>
                <div class="stat-card finished"><i class="fa-solid fa-check-double"></i><h3><?php echo $cur_done; ?></h3><p>منتهية</p></div>
            </div>
            <div class="recent-card">
                <div class="recent-header" style="justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:12px;"><i class="fa-solid fa-list-check"></i><h3>إدارة المباريات الحالية</h3></div>
                    <div class="day-tabs" style="margin-bottom:0;">
                        <div class="day-tab" data-day="yesterday" onclick="switchDay(this)">مباريات الأمس</div>
                        <div class="day-tab active" data-day="today" onclick="switchDay(this)">مباريات اليوم</div>
                        <div class="day-tab" data-day="tomorrow" onclick="switchDay(this)">مباريات الغد</div>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>المباراة</th><th>البطولة</th><th>الوقت</th><th>الحالة</th><th>البث</th><th>التحكم</th></tr></thead>
                        <tbody id="cur-tbody">
                        <?php foreach(['today','yesterday','tomorrow'] as $dayKey):
                            $dayM = array_values(array_filter($allM, function($m) use ($dayKey) { return (isset($m['day'])?$m['day']:'today') === $dayKey; }));
                            $isVisible = $dayKey === 'today' ? '' : ' style="display:none;"';
                        ?>
                        <tr data-day="<?php echo $dayKey; ?>" data-empty="1"<?php echo (!empty($dayM) ? ' style="display:none;"' : $isVisible); ?>>
                            <td colspan="6"><div class="empty-box"><i class="fa-solid fa-calendar-day"></i><p>لا توجد مباريات</p></div></td>
                        </tr>
                        <?php foreach($dayM as $m):
                            $badgeClass = (isset($m['status']) && $m['status'] === 'live') ? 'badge-live' : ((isset($m['status']) && $m['status'] === 'finished') ? 'badge-finished' : 'badge-upcoming');
                            $badgeText  = (isset($m['status']) && $m['status'] === 'live') ? 'جارية' : ((isset($m['status']) && $m['status'] === 'finished') ? 'انتهت' : 'قادمة');
                        ?>
                        <tr data-day="<?php echo $dayKey; ?>" data-home="<?php echo strtolower(isset($m['homeTeam'])?$m['homeTeam']:''); ?>" data-away="<?php echo strtolower(isset($m['awayTeam'])?$m['awayTeam']:''); ?>"<?php echo $isVisible; ?>>
                            <td><?php echo htmlspecialchars($m['homeTeam'] . " vs " . $m['awayTeam']); ?></td>
                            <td><?php echo htmlspecialchars(isset($m['league'])?$m['league']:'--'); ?></td>
                            <td><?php echo date("h:i A", strtotime($m['time'])); ?></td>
                            <td><span class="<?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span></td>
                            <td style="font-size:16px;"><?php echo !empty($m['stream_url']) && $m['stream_url'] !== '#' ? '✅' : '❌'; ?></td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <button class="btn-edit" onclick="openEditModal(this)" data-match='<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES); ?>'><i class="fa-solid fa-pen"></i></button>
                                    <a href="/admin?del_m=<?php echo $m['id']; ?>&section=current" class="btn-del" onclick="return confirm('حذف؟')"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        </tr>
                        <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="edit-modal" class="modal-overlay"><div class="modal-box"><div class="modal-head"><i class="fa-solid fa-pen" style="color:#6366f1;"></i> تعديل المباراة</div>
                <form method="POST" action="/admin?section=current"><input type="hidden" name="edit_match_id" id="edit-id"><div class="modal-body">
                    <div><label>القناة</label><input type="text" name="edit_channel" id="edit-channel"></div>
                    <div><label>المعلق</label><input type="text" name="edit_commentator" id="edit-commentator"></div>
                    <div><label>الحالة</label><select name="edit_status" id="edit-status"><option value="upcoming">قادمة</option><option value="live">جارية الآن</option><option value="finished">انتهت</option></select></div>
                    <div><label>النتيجة</label><input type="text" name="edit_score" id="edit-score"></div>
                    <div class="full"><label>رابط البث</label><input type="text" name="edit_stream" id="edit-stream"></div>
                </div><div class="modal-foot"><button type="button" class="btn-cancel-sm" onclick="document.getElementById('edit-modal').classList.remove('open')">إلغاء</button><button type="submit" name="save_edit" class="btn-primary-sm">حفظ</button></div></form>
            </div></div>
        <?php elseif($sec == 'add_m'): ?>
            <h2 style="font-weight:800; margin-bottom:25px;">إضافة مباراة</h2>
            <form method="POST" style="background:var(--card); padding:30px; border-radius:15px; border:1px solid var(--border);">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div><label>الفريق الأرضي</label><input type="text" name="h" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;" required></div>
                    <div><label>لوجو الأرضي</label><input type="text" name="hl" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"></div>
                    <div><label>الفريق الضيف</label><input type="text" name="a" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;" required></div>
                    <div><label>لوجو الضيف</label><input type="text" name="al" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"></div>
                    <div><label>البطولة</label><input type="text" name="l" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"></div>
                    <div><label>الوقت</label><input type="text" name="t" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"></div>
                    <div><label>الحالة</label><select name="s" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"><option value="upcoming">قادمة</option><option value="live">جارية</option><option value="finished">انتهت</option></select></div>
                    <div><label>اليوم</label><select name="d" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"><option value="today">اليوم</option><option value="yesterday">الأمس</option><option value="tomorrow">الغد</option></select></div>
                </div>
                <input type="text" name="u" placeholder="رابط البث" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px; margin-top:20px;">
                <button type="submit" name="add_m" style="width:100%; padding:14px; background:#6366f1; color:#fff; border:none; border-radius:10px; margin-top:30px; font-weight:800;">حفظ</button>
            </form>
        <?php elseif($sec == 'news'): ?>
            <h2 style="font-weight:800; margin-bottom:30px;">إدارة الأخبار</h2>
            <div class="recent-card" style="margin-bottom:30px;">
                <div style="padding:20px 25px; border-bottom:1px solid var(--border); font-size:17px; font-weight:800; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-plus-circle" style="color:#6366f1;"></i> إضافة خبر جديد
                </div>
                <form method="POST" enctype="multipart/form-data" style="padding:25px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div class="form-group">
                            <label>العنوان</label>
                            <input type="text" name="t" class="form-input" placeholder="اكتب عنواناً جذاباً..." required>
                        </div>
                        <div class="form-group">
                            <label>الصورة</label>
                            <div class="image-input-group">
                                <div id="mini-preview" class="mini-preview"><button type="button" class="mini-remove" onclick="removeImg(event)"><i class="fa-solid fa-xmark"></i></button></div>
                                <input type="text" name="i" id="img-url-backup" class="form-input" style="flex:1;" placeholder="رابط الصورة...">
                                <div class="upload-btn-icon" title="رفع من الجهاز" onclick="document.getElementById('news-img').click()"><i class="fa-solid fa-camera"></i></div>
                                <input type="file" name="img_file" id="news-img" accept="image/*" hidden onchange="previewImg(this)">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>تفاصيل المقال</label>
                        <textarea name="c" class="form-input" placeholder="اكتب محتوى الخبر هنا..." rows="6" required style="resize:vertical;"></textarea>
                    </div>
                    <button type="submit" name="add_n" style="width:100%; padding:14px; background:#6366f1; color:#fff; border:none; border-radius:12px; font-weight:800; font-family:'Cairo'; font-size:16px; cursor:pointer; transition:0.3s; display:flex; align-items:center; justify-content:center; gap:10px;">
                        <i class="fa-solid fa-paper-plane"></i> نشر الخبر الآن
                    </button>
                </form>
            </div>
            <div class="recent-card">
                <div style="padding:20px 25px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:17px; font-weight:800;"><i class="fa-solid fa-newspaper" style="color:#6366f1;"></i> قائمة الأخبار المنشورة</div>
                    <form method="POST"><button type="submit" name="clean_imgs" class="btn-purple"><i class="fa-solid fa-broom"></i> عملية تنظيف الصور</button></form>
                </div>
                <div style="overflow-x:auto;"><table><thead><tr><th>الغلاف</th><th>العنوان</th><th>التاريخ</th><th>التحكم</th></tr></thead><tbody>
                    <?php 
                        $latest_news = array_slice(array_reverse($news), 0, 12);
                        foreach($latest_news as $n): 
                    ?>
                    <tr><td><img src="<?php echo htmlspecialchars(isset($n['image'])?$n['image']:''); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;"></td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars($n['title']); ?></td>
                        <td class="date-cell" data-time="<?php echo $n['id']; ?>">--</td>
                        <td>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <button class="btn-edit" onclick="openNewsEdit(this)" data-news='<?php echo htmlspecialchars(json_encode($n), ENT_QUOTES); ?>'><i class="fa-solid fa-pen-to-square"></i></button>
                                <a href="/admin?del_n=<?php echo $n['id']; ?>" class="btn-del" onclick="return confirm('حذف؟')"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td></tr><?php endforeach; ?>
                </tbody></table></div>
            </div>
            <div id="news-edit-modal" class="modal-overlay"><div class="modal-box"><div class="modal-head">تعديل الخبر</div>
                <form method="POST" enctype="multipart/form-data"><input type="hidden" name="edit_news_id" id="en-id"><div class="modal-body">
                    <div class="full"><label>العنوان</label><input type="text" name="n_t" id="en-t" required></div>
                    <div class="full"><label>الصورة</label><div class="image-input-group">
                        <div id="mini-preview-edit" class="mini-preview"></div>
                        <input type="text" name="n_i" id="en-url" style="flex:1; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;">
                        <input type="file" name="n_img_file" id="n-img-file" accept="image/*" hidden onchange="previewImg(this, true)">
                        <div class="upload-btn-icon" onclick="document.getElementById('n-img-file').click()"><i class="fa-solid fa-camera"></i></div>
                    </div></div>
                    <div class="full"><label>المحتوى</label><textarea name="n_c" id="en-c" rows="8" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); color:#fff; border-radius:8px;"></textarea></div>
                </div><div class="modal-foot"><button type="button" class="btn-cancel-sm" onclick="document.getElementById('news-edit-modal').classList.remove('open')">إلغاء</button><button type="submit" name="save_news_edit" class="btn-primary-sm">حفظ</button></div></form>
            </div></div>
        <?php endif; ?>
    </main>
    <script>
        const themeBtn = document.getElementById('adm-theme');
        themeBtn.onclick = () => {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', target);
            localStorage.setItem('theme', target);
            themeBtn.querySelector('i').className = target === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        };
        let activeDay = 'today';
        function switchDay(el) {
            document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active'); activeDay = el.dataset.day; filterMatches();
        }
        function filterMatches() {
            const search = (document.getElementById('cur-search')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('tbody tr[data-day]');
            let counts = {today:0, yesterday:0, tomorrow:0};
            rows.forEach(r => {
                r.style.display = 'none'; if(r.dataset.empty) return;
                if(r.dataset.day === activeDay){
                    const txt = r.innerText.toLowerCase();
                    if(!search || txt.includes(search)){ r.style.display = ''; counts[activeDay]++; }
                }
            });
            document.querySelectorAll('tr[data-empty]').forEach(r => {
                if(r.dataset.day === activeDay) r.style.display = counts[activeDay] === 0 ? '' : 'none';
                else r.style.display = 'none';
            });
        }
        function openEditModal(btn) {
            const m = JSON.parse(btn.getAttribute('data-match'));
            document.getElementById('edit-id').value = m.id;
            document.getElementById('edit-channel').value = m.channel || '';
            document.getElementById('edit-commentator').value = m.commentator || '';
            document.getElementById('edit-status').value = m.status || 'upcoming';
            document.getElementById('edit-score').value = m.score || '';
            document.getElementById('edit-stream').value = m.stream_url || '';
            document.getElementById('edit-modal').classList.add('open');
        }
        function openNewsEdit(btn) {
            const n = JSON.parse(btn.getAttribute('data-news'));
            document.getElementById('en-id').value = n.id;
            document.getElementById('en-t').value = n.title;
            document.getElementById('en-c').value = n.content;
            document.getElementById('en-url').value = n.image;
            document.getElementById('news-edit-modal').classList.add('open');
        }
        function previewImg(input, isEdit = false) {
            const preview = document.getElementById(isEdit ? 'mini-preview-edit' : 'mini-preview');
            const urlInput = document.getElementById(isEdit ? 'en-url' : 'img-url-backup');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    preview.style.display = 'block';
                    if(urlInput) urlInput.value = input.files[0].name;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        function removeImg(e) {
            e.stopPropagation(); document.getElementById('news-img').value = "";
            document.getElementById('mini-preview').style.display = 'none';
            document.getElementById('img-url-backup').value = "";
        }
        window.onclick = (e) => { if(e.target.classList.contains('modal-overlay')) document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('open')); };

        // Toast & Date Helper Functions
        function showToast(msg, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="fa-solid ${type==='success'?'fa-check-circle':'fa-info-circle'}"></i> <span>${msg}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function formatLocalDates() {
            document.querySelectorAll('.date-cell').forEach(cell => {
                const ts = parseInt(cell.getAttribute('data-time'));
                if(ts) {
                    const d = new Date(ts * 1000);
                    cell.innerText = d.toLocaleDateString('ar-EG', {day:'2-digit', month:'2-digit', year:'numeric'});
                }
            });
        }

        window.onload = () => {
            formatLocalDates();
            const url = new URL(window.location.href);
            if(url.searchParams.has('success')) showToast('تمت العملية بنجاح ✓', 'success');
            if(url.searchParams.has('cleaned')) showToast(`تم تنظيف ${url.searchParams.get('cleaned')} صورة غير مستخدمة ✓`, 'success');
            
            // تنظيف الرابط لمنع ظهور التنبيه عند التحديث
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?section=' + (url.searchParams.get('section') || 'main');
            window.history.replaceState({path:cleanUrl}, '', cleanUrl);
        };
    </script>
<?php endif; ?>
</body>
</html>