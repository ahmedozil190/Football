<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t);</script>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كورة لايف - الخبر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="navbar">
        <div class="nav-container">
            <button id="mobile-menu-open" class="mobile-menu-btn"><i class="fa-solid fa-bars"></i></button>
            <a href="/" class="nav-logo"><i class="fa-solid fa-play"></i><span> كورة لايف</span></a>
            <nav class="nav-links">
                <a href="/"><i class="fa-solid fa-house"></i> الرئيسية</a>
                <a href="/all-news" class="active"><i class="fa-regular fa-newspaper"></i> آخر الأخبار</a>
                <a href="/contact"><i class="fa-solid fa-envelope"></i> اتصل بنا</a>
            </nav>
            <div class="nav-right">
                <div class="desktop-only" style="display:flex; gap:10px;">
                    <a href="#" target="_blank" class="nav-icon-link instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" target="_blank" class="nav-icon-link facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" target="_blank" class="nav-icon-link twitter"><i class="fa-brands fa-x-twitter"></i></a>
                </div>
                <button id="theme-toggle" class="nav-icon-btn"><i class="fa-solid fa-moon"></i></button>
            </div>
        </div>
    </header>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="sidebar-menu" id="sidebar-menu">
        <div class="sidebar-header">
            <div class="nav-logo"><i class="fa-solid fa-play"></i><span> كورة لايف</span></div>
            <button class="sidebar-close-btn" id="sidebar-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="sidebar-links">
            <a href="/"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="/all-news" class="active"><i class="fa-regular fa-newspaper"></i> آخر الأخبار</a>
            <a href="/contact"><i class="fa-solid fa-envelope"></i> اتصل بنا</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-social">
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
            </div>
        </div>
    </div>

    <div class="main-content-wrapper">
        <?php
        $id = $_GET['id'] ?? null; $art = null; $f = 'data/news.json';
        if ($id && file_exists($f)) {
            $news = json_decode(@file_get_contents($f), true);
            foreach ($news as $n) { if ($n['id'] == $id) { $art = $n; break; } }
        }
        if ($art): ?>
            <div class="news-detail-container card">
                <h1><?php echo $art['title']; ?></h1>
                <div class="news-detail-img"><img src="<?php echo $art['image']; ?>"></div>
                <div class="news-detail-body"><?php echo $art['content']; ?></div>
            </div>
        <?php else: ?>
            <div class="admin-empty-state"><p>المقال غير موجود.</p></div>
        <?php endif; ?>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar-menu');
        const overlay = document.getElementById('sidebar-overlay');
        const openBtn = document.getElementById('mobile-menu-open');
        const closeBtn = document.getElementById('sidebar-close');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if(openBtn) openBtn.onclick = toggleSidebar;
        if(closeBtn) closeBtn.onclick = toggleSidebar;
        if(overlay) overlay.onclick = toggleSidebar;

        const thBtn = document.getElementById('theme-toggle');
        function upTh(t) {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            document.querySelectorAll('.nav-icon-btn i').forEach(i => {
                i.className = t === 'light' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            });
        }
        const curTh = localStorage.getItem('theme') || 'dark';
        upTh(curTh);
        if(thBtn) thBtn.onclick = () => upTh(document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
    </script>
</body>
</html>
