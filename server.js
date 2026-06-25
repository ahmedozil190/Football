const express = require('express');
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

function proxyRequest(targetUrl, res, redirectCount = 0) {
    if (redirectCount > 10) return res.status(500).send('Too many redirects');
    try {
        const parsedTarget = new URL(targetUrl);
        const protocol = parsedTarget.protocol === 'https:' ? https : http;
        const options = {
            hostname: parsedTarget.hostname,
            port: parsedTarget.port || (parsedTarget.protocol === 'https:' ? 443 : 80),
            path: parsedTarget.pathname + parsedTarget.search,
            method: 'GET',
            headers: { 'User-Agent': 'VLC/3.0.18 LibVLC/3.0.18' }
        };
        const proxyReq = protocol.request(options, (proxyRes) => {
            if (proxyRes.statusCode >= 300 && proxyRes.statusCode < 400 && proxyRes.headers.location) {
                let nextUrl = proxyRes.headers.location;
                if (!nextUrl.startsWith('http')) nextUrl = new URL(nextUrl, targetUrl).href;
                return proxyRequest(nextUrl, res, redirectCount + 1);
            }
            res.set('Access-Control-Allow-Origin', '*');
            res.set('Content-Type', proxyRes.headers['content-type'] || 'application/octet-stream');
            proxyRes.pipe(res);
        });
        proxyReq.on('error', (err) => res.status(500).send(err.message));
        proxyReq.end();
    } catch (e) { res.status(400).send('Invalid URL'); }
}

app.get('/proxy', (req, res) => {
    if (!req.query.url) return res.status(400).send('URL Missing');
    proxyRequest(req.query.url, res);
});

app.get('/watch.php', (req, res) => {
    const id = req.query.id;
    const matches = JSON.parse(fs.readFileSync(path.join(__dirname, 'data/matches.json'), 'utf8'));
    const match = matches.find(m => m.id == id);
    if (match) {
        let html = fs.readFileSync(path.join(__dirname, 'watch.template'), 'utf8');
        html = html.replace(/<\?php echo \$m\[\'homeTeam\'\] \?\? \'\'; \?>/g, match.homeTeam);
        html = html.replace(/<\?php echo \$m\[\'awayTeam\'\] \?\? \'\'; \?>/g, match.awayTeam);
        html = html.replace(/<\?php echo \$m\[\'homeLogo\'\] \?\? \'\'; \?>/g, match.homeLogo);
        html = html.replace(/<\?php echo \$m\[\'awayLogo\'\] \?\? \'\'; \?>/g, match.awayLogo);
        html = html.replace(/<\?php echo \$m\[\'homeScore\'\] \?\? \'\'; \?>/g, match.homeScore);
        html = html.replace(/<\?php echo \$m\[\'awayScore\'\] \?\? \'\'; \?>/g, match.awayScore);
        html = html.replace(/<\?php echo \$m\[\'stream_url\'\] \?\? \'\'; \?>/g, match.stream_url);
        html = html.replace(/<\?php[\s\S]*?\?>/g, '');
        res.send(html);
    } else res.status(404).send('Match not found');
});

app.get('/', (req, res) => {
    let html = fs.readFileSync(path.join(__dirname, 'index.template'), 'utf8');
    const matches = fs.readFileSync(path.join(__dirname, 'data/matches.json'), 'utf8');
    const injection = `<script>const allMatches = ${matches};</script>`;
    // استبدال العلامة النظيفة بالبيانات
    html = html.replace('{{MATCHES_INJECTION}}', injection);
    res.send(html);
});

app.use(express.static(__dirname));

app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
