const express = require('express');
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');
const url = require('url');

const app = express();
const PORT = process.env.PORT || 3000;

// محرك العرض المبسط (يحل محل PHP)
function renderTemplate(filePath, data = {}) {
    let content = fs.readFileSync(filePath, 'utf8');
    // استبدال المتغيرات البسيطة
    for (const key in data) {
        const regex = new RegExp(`<\?php echo \\$m\\[\\'${key}\\'\\] \\?\\? \\'\\'\\; \?>`, 'g');
        content = content.replace(regex, data[key]);
    }
    // تنظيف بقايا كود PHP في العرض
    return content.replace(/<\?php[\s\S]*?\?>/g, '');
}

// 1. مسار البروكسي القوي (كودك الأصلي)
app.get('/proxy', (req, res) => {
    const targetUrl = req.query.url;
    if (!targetUrl) return res.status(400).send('URL Missing');

    console.log(`[Proxy] Fetching: ${targetUrl}`);

    const parsedTarget = new URL(targetUrl);
    const isHttps = parsedTarget.protocol === 'https:';
    const protocol = isHttps ? https : http;

    const options = {
        hostname: parsedTarget.hostname,
        port: parsedTarget.port || (isHttps ? 443 : 80),
        path: parsedTarget.pathname + parsedTarget.search,
        method: 'GET',
        headers: {
            'User-Agent': 'VLC/3.0.18 LibVLC/3.0.18',
            'Accept': '*/*',
            'Connection': 'keep-alive'
        }
    };

    const proxyReq = protocol.request(options, (proxyRes) => {
        // التعامل مع إعادة التوجيه
        if (proxyRes.statusCode >= 300 && proxyRes.statusCode < 400 && proxyRes.headers.location) {
            return res.redirect(`/proxy?url=${encodeURIComponent(proxyRes.headers.location)}`);
        }

        res.set('Access-Control-Allow-Origin', '*');
        res.set('Content-Type', proxyRes.headers['content-type'] || 'application/octet-stream');
        proxyRes.pipe(res);
    });

    proxyReq.on('error', (err) => {
        console.error('[Proxy Error]:', err.message);
        res.status(500).send(`Proxy Error: ${err.message}`);
    });

    proxyReq.end();
});

// 2. عرض صفحة المشاهدة
app.get('/watch.php', (req, res) => {
    const id = req.query.id;
    const matches = JSON.parse(fs.readFileSync(path.join(__dirname, 'data/matches.json'), 'utf8'));
    const match = matches.find(m => m.id == id);
    
    if (match) {
        let html = fs.readFileSync(path.join(__dirname, 'watch.php'), 'utf8');
        // استبدال البيانات الأساسية (محاكاة PHP)
        html = html.replace(/<\?php echo \$m\[\'homeTeam\'\] \?\? \'\'; \?>/g, match.homeTeam);
        html = html.replace(/<\?php echo \$m\[\'awayTeam\'\] \?\? \'\'; \?>/g, match.awayTeam);
        html = html.replace(/<\?php echo \$m\[\'homeLogo\'\] \?\? \'\'; \?>/g, match.homeLogo);
        html = html.replace(/<\?php echo \$m\[\'awayLogo\'\] \?\? \'\'; \?>/g, match.awayLogo);
        html = html.replace(/<\?php echo \$m\[\'homeScore\'\] \?\? \'\'; \?>/g, match.homeScore);
        html = html.replace(/<\?php echo \$m\[\'awayScore\'\] \?\? \'\'; \?>/g, match.awayScore);
        html = html.replace(/<\?php echo \$m\[\'stream_url\'\] \?\? \'\'; \?>/g, match.stream_url);
        
        // تنظيف بقايا PHP
        html = html.replace(/<\?php[\s\S]*?\?>/g, '');
        res.send(html);
    } else {
        res.status(404).send('Match not found');
    }
});

// 3. عرض الصفحة الرئيسية
app.get('/', (req, res) => {
    let html = fs.readFileSync(path.join(__dirname, 'index.php'), 'utf8');
    // محاكاة تحويل البيانات لـ JSON لتعمل الـ Loops في الواجهة
    const matches = fs.readFileSync(path.join(__dirname, 'data/matches.json'), 'utf8');
    html = html.replace(/<\?php[\s\S]*?\$allMatches = [\s\S]*?\?>/g, `<script>const allMatches = ${matches};</script>`);
    
    // تنظيف بقايا PHP
    html = html.replace(/<\?php[\s\S]*?\?>/g, '');
    res.send(html);
});

// خدمة الملفات الثابتة
app.use(express.static(__dirname));

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
