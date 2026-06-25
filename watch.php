<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t);</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كورة لايف - مشاهدة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .team-logo-img { width: 60px; height: 60px; object-fit: contain; }
        .watch-container { width: 100%; max-width: 1000px; margin: 20px auto; padding: 0 15px; }
        .main-video-wrapper { position: relative; background: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .player-placeholder {
            position: absolute; top:0; left:0; width:100%; height:100%; 
            background: #111; color:#fff; display:none; flex-direction:column; align-items:center; justify-content:center; z-index:10;
        }
    </style>
</head>

<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="/" class="nav-logo"><i class="fa-solid fa-play"></i><span> كورة لايف</span></a>
            <nav class="nav-links">
                <a href="/"><i class="fa-solid fa-house"></i> الرئيسية</a>
            </nav>
            <div class="nav-right">
                <button id="theme-toggle" class="nav-icon-btn"><i class="fa-solid fa-moon"></i></button>
            </div>
        </div>
    </header>

    <div class="watch-container">
        <?php
        $id = $_GET['id'] ?? null; $m = null; $f = 'data/matches.json';
        if ($id && file_exists($f)) {
            $mats = json_decode(@file_get_contents($f), true);
            foreach ($mats as $ma) { if ($ma['id'] == $id) { $m = $ma; break; } }
        }
        if ($m): ?>
            <div class="match-card live" style="margin-bottom:20px; width:100%;">
                <div class="match-main-info">
                    <div class="match-team home"><img src="<?php echo $m['homeLogo']; ?>"><span><?php echo $m['homeTeam']; ?></span></div>
                    <div class="match-center-details">
                        <div class="match-score-center shadow-lg"><span><?php echo $m['awayScore']; ?> - <?php echo $m['homeScore']; ?></span></div>
                        <div class="match-status-badge">بث مباشر</div>
                    </div>
                    <div class="match-team away"><span><?php echo $m['awayTeam']; ?></span><img src="<?php echo $m['awayLogo']; ?>"></div>
                </div>
            </div>

            <div class="main-video-wrapper" style="position:relative; background:#000; border-radius:12px; overflow:hidden;">
                <div id="player-error" class="player-placeholder" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(220, 38, 38, 0.9); z-index:10; flex-direction:column; align-items:center; justify-content:center; color:#fff; text-align:center;">
                    <div class="placeholder-text">
                        <h3 id="error-title">فشل تشغيل البث</h3>
                        <p id="error-message">الرابط غير متاح حالياً</p>
                    </div>
                </div>
                <video id="player" controls autoplay style="width:100%; aspect-ratio:16/9;"></video>
            </div>

            <div class="server-selection" style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
                <button class="server-btn active">سيرفر المشاهدة الرئيسي</button>
            </div>

            <script>
                let hls = null;
                function loadUrl(url) {
                    const video = document.getElementById('player');
                    const err = document.getElementById('player-error');
                    if (!video || !url) return;
                    err.style.display = 'none';

                    // تحويل الرابط ليمر عبر البروكسي المطور (Node.js)
                    const finalUrl = `/proxy?url=${encodeURIComponent(url)}`;
                    console.log("Loading via Final Proxy:", finalUrl);
                    
                    if (hls) { hls.destroy(); hls = null; }
                    video.pause(); video.removeAttribute('src'); video.load();

                    if (url.includes('.m3u8')) {
                        if (Hls.isSupported()) {
                            hls = new Hls();
                            hls.loadSource(finalUrl);
                            hls.attachMedia(video);
                            hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
                            hls.on(Hls.Events.ERROR, () => { err.style.display = 'flex'; });
                        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            video.src = finalUrl;
                            video.play();
                        }
                    } else {
                        video.src = finalUrl;
                        video.play().catch(() => { err.style.display = 'flex'; });
                    }
                }
                document.addEventListener('DOMContentLoaded', () => { loadUrl("<?php echo $m['stream_url'] ?? ''; ?>"); });
            </script>
        <?php else: ?>
            <p>المباراة غير موجودة.</p>
        <?php endif; ?>
    </div>
</body>
</html>