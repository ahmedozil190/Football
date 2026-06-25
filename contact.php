<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t);</script>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كورة لايف - اتصل بنا</title>
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
                <a href="/all-news"><i class="fa-regular fa-newspaper"></i> آخر الأخبار</a>
                <a href="/contact" class="active"><i class="fa-solid fa-envelope"></i> اتصل بنا</a>
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
            <a href="/all-news"><i class="fa-regular fa-newspaper"></i> آخر الأخبار</a>
            <a href="/contact" class="active"><i class="fa-solid fa-envelope"></i> اتصل بنا</a>
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
        <div class="contact-container card">
            <h2><i class="fa-solid fa-envelope"></i> اتصل بنا</h2>
            <form method="POST" action="/contact">
                <input type="text" placeholder="الاسم" class="input" style="width:100%; padding:12px; margin-bottom:15px; background:var(--bg-input); border:1px solid var(--border-color); color:var(--text-main); border-radius:8px;">
                <input type="email" placeholder="البريد الإلكتروني" class="input" style="width:100%; padding:12px; margin-bottom:15px; background:var(--bg-input); border:1px solid var(--border-color); color:var(--text-main); border-radius:8px;">
                <textarea placeholder="رسالتك" style="width:100%; padding:12px; margin-bottom:15px; background:var(--bg-input); border:1px solid var(--border-color); color:var(--text-main); border-radius:8px;" rows="6"></textarea>
                <button type="submit" style="width:100%; padding:12px; background:var(--color-primary); color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">إرسال</button>
            </form>
            <?php if ($_SERVER['REQUEST_METHOD']==='POST') echo "<p style='color:var(--color-success); text-align:center; margin-top:15px;'>تم الإرسال بنجاح!</p>"; ?>
        </div>
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
