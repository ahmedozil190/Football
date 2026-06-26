<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t);</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كورة لايف - آخر الأخبار</title>
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
        <section class="news-section">
            <div class="section-header">
                <h2><i class="fa-regular fa-newspaper"></i> آخر الأخبار</h2>
            </div>
            <div class="news-grid">
                <?php
                $f = 'data/news.json';
                $found = false;
                $perPage = 12;
                $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
                if ($page < 1) $page = 1;

                if (file_exists($f)) {
                    $allNews = json_decode(@file_get_contents($f), true);
                    if (is_array($allNews)) {
                        $allNews = array_reverse($allNews);
                        $totalNews = count($allNews);
                        $totalPages = ceil($totalNews / $perPage);
                        $offset = ($page - 1) * $perPage;
                        $news = array_slice($allNews, $offset, $perPage);

                        if (count($news) > 0) {
                            $found = true;
                            foreach ($news as $n) { ?>
                <article class="news-card">
                    <a href="/news?id=<?php echo $n['id']; ?>">
                        <div class="news-img-wrapper"><img src="<?php echo $n['image']; ?>"></div>
                    </a>
                    <div class="news-content">
                        <a href="/news?id=<?php echo $n['id']; ?>">
                            <h3><?php echo $n['title']; ?></h3>
                        </a>
                        <p><?php echo mb_substr(strip_tags($n['content']), 0, 120); ?>...</p>
                        <a href="/news?id=<?php echo $n['id']; ?>" class="read-more-btn">عرض المزيد <i class="fa-solid fa-arrow-left"></i></a>
                    </div>
                </article>
                <?php }
                        }
                    }
                }
                if (!$found) echo '<div class="empty-state"><i class="fa-solid fa-newspaper"></i><p>لا توجد أخبار حتي الان.</p></div>';
                ?>
            </div>

            <?php if ($found && isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?p=<?php echo $page - 1; ?>" class="pagination-btn"><i class="fa-solid fa-chevron-right"></i></a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): $isActive = ($i === $page); ?>
                <a href="?p=<?php echo $i; ?>" class="pagination-btn <?php echo $isActive ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?p=<?php echo $page + 1; ?>" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
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