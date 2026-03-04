<?php
declare(strict_types=1);

$headers = array_change_key_case(getallheaders(), CASE_LOWER);

if (!empty($headers['user-agent']) && str_starts_with(trim($headers['user-agent']), "Deno/")) {
    http_response_code(302);
    header("Location: https://raw.githubusercontent.com/tristan-nsg/asana-time/refs/heads/master/src/main.ts");
    exit;
}

$repoUrl = 'https://github.com/tristan-nsg/asana-time';
$releaseBase = 'https://github.com/tristan-nsg/asana-time/releases/latest/download';

$platforms = [
    'windows' => [
        'label' => 'Windows',
        'builds' => [
            ['arch' => 'x86_64', 'sublabel' => 'x86_64', 'file' => 'asana-time_windows-x86_64.exe'],
        ],
    ],
    'linux' => [
        'label' => 'Linux',
        'builds' => [
            ['arch' => 'x86_64',  'sublabel' => 'x86_64', 'file' => 'asana-time_linux-x86_64'],
            ['arch' => 'aarch64', 'sublabel' => 'ARM64',   'file' => 'asana-time_linux-aarch64'],
        ],
    ],
    'mac' => [
        'label' => 'macOS',
        'builds' => [
            ['arch' => 'aarch64', 'sublabel' => 'Apple Silicon', 'file' => 'asana-time_mac-aarch64'],
            ['arch' => 'x86_64',  'sublabel' => 'Intel',         'file' => 'asana-time_mac-x86_64'],
        ],
    ],
];

$runCmd = 'asana-time';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>asana-time</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #09090b;
            --surface: rgba(255, 255, 255, 0.05);
            --surface-border: rgba(255, 255, 255, 0.08);
            --text: #e4e4e7;
            --text-muted: #a1a1aa;
            --text-dim: #71717a;
            --accent: #f97316;
            --accent-dim: rgba(249, 115, 22, 0.12);
            --accent-glow: rgba(249, 115, 22, 0.06);
            --code-bg: rgba(255, 255, 255, 0.03);
            --code-border: rgba(255, 255, 255, 0.06);
            --font-mono: 'SF Mono', 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace;
            --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }

        body {
            font-family: var(--font-sans);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        .bg-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(800px at var(--mx, 50%) var(--my, 30%), var(--accent-glow), transparent 60%),
                radial-gradient(600px at 70% 20%, rgba(249, 115, 22, 0.03), transparent 50%);
            transition: background-position 0.08s ease;
        }

        main {
            max-width: 640px;
            width: 100%;
            padding: 5rem 1.5rem 3rem;
            position: relative;
            z-index: 1;
        }

        .hero {
            text-align: center;
            margin-bottom: 4rem;
        }

        .hero h1 {
            font-family: var(--font-mono);
            font-size: 2.75rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }

        .hero h1 span { color: var(--accent); }

        .hero p {
            color: var(--text-muted);
            font-size: 1.125rem;
            max-width: 28rem;
            margin: 0 auto;
            line-height: 1.7;
        }

        section {
            margin-bottom: 3rem;
        }

        section h2 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--text-dim);
            margin-bottom: 1.25rem;
        }

        /* Download buttons */
        .download-primary {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            background: var(--accent);
            color: var(--bg);
            font-family: var(--font-sans);
            font-size: 1rem;
            font-weight: 600;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .download-btn:hover {
            background: #fb923c;
            transform: translateY(-1px);
        }

        .download-btn svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .download-hint {
            font-size: 0.8rem;
            color: var(--text-dim);
            font-family: var(--font-mono);
        }

        .download-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .download-col {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .download-col-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0 0.25rem;
            margin-bottom: 0.25rem;
        }

        .download-col-header svg {
            width: 1rem;
            height: 1rem;
            color: var(--text-dim);
        }

        .download-col-header span {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .download-card {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            background: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            text-decoration: none;
            color: var(--text);
            transition: all 0.2s;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .download-card:hover {
            border-color: var(--accent);
            background: var(--accent-dim);
        }

        .download-card.active {
            border-color: var(--accent);
        }

        .download-card-label {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .download-card-sub {
            font-size: 0.7rem;
            color: var(--text-dim);
            font-family: var(--font-mono);
        }

        /* Install instructions */
        .install-instructions {
            background: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .install-instructions h3 {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .install-instructions .code-block {
            margin-bottom: 0.5rem;
            background: var(--surface);
            border-radius: 10px;
        }

        .install-instructions .code-block:last-child {
            margin-bottom: 0;
        }

        /* Code blocks */
        .code-block {
            background: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            font-family: var(--font-mono);
            font-size: 0.85rem;
            overflow-x: auto;
            position: relative;
            margin-bottom: 0.75rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .code-block .prompt {
            color: var(--text-dim);
            user-select: none;
        }

        .code-block code {
            color: var(--text);
            white-space: pre-wrap;
            word-break: break-all;
        }

        .code-block .flag { color: var(--accent); }

        .copy-btn {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            background: var(--surface);
            border: 1px solid var(--surface-border);
            border-radius: 8px;
            color: var(--text-dim);
            padding: 0.25rem 0.625rem;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            transform: translateY(-1px);
        }

        /* Steps */
        .steps {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        .step {
            display: flex;
            gap: 1.25rem;
        }

        .step-num {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--accent-dim);
            color: var(--accent);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.1rem;
        }

        .step-content {
            flex: 1;
            min-width: 0;
        }

        .step-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-content p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .step-content a {
            color: var(--accent);
            text-decoration: underline;
            text-decoration-thickness: 2px;
            text-underline-offset: 2px;
            transition: color 0.15s;
        }

        .step-content a:hover { color: #fb923c; }

        /* Env vars */
        .env-grid {
            display: grid;
            gap: 0.5rem;
        }

        .env-row {
            background: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 16px;
            padding: 0.875rem 1.25rem;
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: border-color 0.15s;
        }

        .env-row:hover { border-color: rgba(255, 255, 255, 0.12); }

        .env-name {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: var(--accent);
            flex-shrink: 0;
            min-width: 10rem;
        }

        .env-flag {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-dim);
            flex-shrink: 0;
            min-width: 8.5rem;
        }

        .env-desc {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .env-tag {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            color: var(--text-dim);
            background: var(--surface);
            border: 1px solid var(--surface-border);
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            margin-left: auto;
            flex-shrink: 0;
        }

        footer {
            text-align: center;
            padding: 2rem 1.5rem;
            color: var(--text-dim);
            font-size: 0.85rem;
            border-top: 1px solid var(--surface-border);
            width: 100%;
            max-width: 640px;
            position: relative;
            z-index: 1;
        }

        footer a {
            color: var(--text-dim);
            text-decoration: none;
            transition: color 0.15s;
        }

        footer a:hover { color: var(--text); }

        @media (max-width: 640px) {
            .code-block { font-size: 0.75rem; padding: 0.875rem 1rem; border-radius: 12px; }
            .copy-btn { font-size: 0.65rem; padding: 0.2rem 0.5rem; }
            .env-row { flex-direction: column; gap: 0.25rem; border-radius: 12px; }
            .env-name { min-width: 0; }
            .env-flag { min-width: 0; }
            .env-tag { margin-left: 0; }
            .download-grid { grid-template-columns: 1fr; }
            .download-col-header { margin-bottom: 0; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 2rem; }
            main { padding: 2.5rem 1rem 2rem; }
            .code-block { font-size: 0.7rem; }
            .step { gap: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="bg-glow" id="bg-glow"></div>

    <main>
        <div class="hero">
            <h1><span>asana</span>-time</h1>
            <p>Automatically mark today's Asana time entries as non-billable. One command, every day.</p>
        </div>

        <section>
            <h2>Download</h2>

            <div class="download-primary">
                <a id="download-main" class="download-btn" href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span id="download-main-label">Download</span>
                </a>
                <span class="download-hint" id="download-main-hint"></span>
            </div>

            <div class="download-grid">
                <?php foreach ($platforms as $os => $platform): ?>
                <div class="download-col">
                    <div class="download-col-header">
                        <?php if ($os === 'mac'): ?>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83"/><path d="M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11"/></svg>
                        <?php elseif ($os === 'linux'): ?>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 2c-1.78 0-3.22 1.76-3.22 3.93 0 .43.07.84.19 1.22C7.43 8.13 6 10.04 6 12.28c0 1.5.6 2.85 1.57 3.84-.2.49-.32 1.03-.32 1.6C7.25 19.55 8.69 21 10.5 21c.85 0 1.62-.32 2.22-.85.59.53 1.37.85 2.22.85 1.81 0 3.25-1.45 3.25-3.28 0-.57-.12-1.11-.32-1.6.97-.99 1.57-2.34 1.57-3.84 0-2.24-1.43-4.15-3.47-5.13.12-.38.19-.79.19-1.22C16.16 3.76 14.28 2 12.5 2z"/></svg>
                        <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 12V6.75L9 3.25V4.75L4.5 7.25V11.5L9 14V15.5L3 12M21 12L15 15.5V14L19.5 11.5V7.25L15 4.75V3.25L21 6.75V12M11.25 18.75L14.75 5.25H12.75L9.25 18.75H11.25Z"/></svg>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($platform['label']) ?></span>
                    </div>
                    <?php foreach ($platform['builds'] as $build): ?>
                    <a class="download-card"
                       href="<?= htmlspecialchars("$releaseBase/{$build['file']}") ?>"
                       data-os="<?= htmlspecialchars($os) ?>"
                       data-arch="<?= htmlspecialchars($build['arch']) ?>"
                       data-filename="<?= htmlspecialchars($build['file']) ?>">
                        <span class="download-card-label"><?= htmlspecialchars($build['sublabel']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="install-instructions" id="install-instructions">
                <h3>After downloading, rename and install:</h3>
                <div id="install-steps-unix">
                    <div class="code-block">
                        <button class="copy-btn" id="copy-unix" data-copy="">copy</button>
                        <span class="prompt">$ </span><code id="install-cmd-unix"></code>
                    </div>
                </div>
                <div id="install-steps-windows" style="display: none">
                    <div class="code-block">
                        <button class="copy-btn" id="copy-win" data-copy="">copy</button>
                        <span class="prompt">&gt; </span><code id="install-cmd-windows"></code>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2>Setup</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <div class="step-content">
                        <h3>Set your credentials</h3>
                        <p>Get a <a href="https://app.asana.com/0/developer-console" target="_blank">Personal Access Token</a> from Asana, then add these to your shell profile:</p>
                        <div class="code-block" id="env-mac">
                            <button class="copy-btn" data-copy="export ASANA_TOKEN=&quot;your_token_here&quot;&#10;export ASANA_WORKSPACE=your_workspace_gid&#10;export ASANA_USER=your_user_gid">copy</button>
                            <code>export ASANA_TOKEN=<span class="flag">"your_token_here"</span>
export ASANA_WORKSPACE=<span class="flag">your_workspace_gid</span>
export ASANA_USER=<span class="flag">your_user_gid</span></code>
                        </div>
                        <div class="code-block" id="env-windows" style="display: none">
                            <button class="copy-btn" data-copy="$env:ASANA_TOKEN = &quot;your_token_here&quot;&#10;$env:ASANA_WORKSPACE = &quot;your_workspace_gid&quot;&#10;$env:ASANA_USER = &quot;your_user_gid&quot;">copy</button>
                            <code>$env:ASANA_TOKEN = <span class="flag">"your_token_here"</span>
$env:ASANA_WORKSPACE = <span class="flag">"your_workspace_gid"</span>
$env:ASANA_USER = <span class="flag">"your_user_gid"</span></code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-num">2</div>
                    <div class="step-content">
                        <h3>Run it</h3>
                        <p>With environment variables set:</p>
                        <div class="code-block">
                            <button class="copy-btn" data-copy="<?= htmlspecialchars($runCmd) ?>">copy</button>
                            <span class="prompt">$ </span><code>asana-time</code>
                        </div>
                        <p>Or pass credentials as flags:</p>
                        <div class="code-block">
                            <button class="copy-btn" data-copy="asana-time -t YOUR_TOKEN -w WORKSPACE_GID -u USER_GID">copy</button>
                            <span class="prompt">$ </span><code>asana-time <span class="flag">-t</span> YOUR_TOKEN <span class="flag">-w</span> WORKSPACE_GID <span class="flag">-u</span> USER_GID</code>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2>Usage</h2>
            <div class="code-block" id="usage-unix">
                <button class="copy-btn" data-copy="# Run once&#10;asana-time&#10;&#10;# Schedule daily at 5pm (crontab -e)&#10;0 17 * * * asana-time">copy</button>
                <code><span class="flag"># Run once</span>
asana-time

<span class="flag"># Schedule daily at 5pm (crontab -e)</span>
0 17 * * * asana-time</code>
            </div>
            <div class="code-block" id="usage-windows" style="display: none">
                <button class="copy-btn" data-copy="# Run once&#10;asana-time&#10;&#10;# Schedule daily at 5pm (PowerShell)&#10;schtasks /create /tn &quot;AsanaTime&quot; /tr &quot;asana-time.exe&quot; /sc daily /st 17:00">copy</button>
                <code><span class="flag"># Run once</span>
asana-time

<span class="flag"># Schedule daily at 5pm (PowerShell)</span>
schtasks /create /tn "AsanaTime" /tr "asana-time.exe" /sc daily /st 17:00</code>
            </div>
        </section>

        <section>
            <h2>Configuration</h2>
            <div class="env-grid">
                <div class="env-row">
                    <span class="env-name">ASANA_TOKEN</span>
                    <span class="env-flag">-t, --token</span>
                    <span class="env-desc">Personal access token</span>
                    <span class="env-tag">required</span>
                </div>
                <div class="env-row">
                    <span class="env-name">ASANA_WORKSPACE</span>
                    <span class="env-flag">-w, --workspace_gid</span>
                    <span class="env-desc">Workspace GID</span>
                    <span class="env-tag">required</span>
                </div>
                <div class="env-row">
                    <span class="env-name">ASANA_USER</span>
                    <span class="env-flag">-u, --user</span>
                    <span class="env-desc">User GID or email</span>
                    <span class="env-tag">optional</span>
                </div>
                <div class="env-row">
                    <span class="env-name">LOG_LEVEL</span>
                    <span class="env-flag">&mdash;</span>
                    <span class="env-desc">debug, info, warning, error</span>
                    <span class="env-tag">env only</span>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <a href="<?= htmlspecialchars($repoUrl) ?>">GitHub</a>
    </footer>

    <script>
        // Cursor-tracking background glow
        (() => {
            const layer = document.getElementById('bg-glow');
            if (!layer) return;
            window.addEventListener('pointermove', (e) => {
                const x = `${((e.clientX / window.innerWidth) * 100).toFixed(1)}%`;
                const y = `${((e.clientY / window.innerHeight) * 100).toFixed(1)}%`;
                layer.style.setProperty('--mx', x);
                layer.style.setProperty('--my', y);
            });
        })();

        // Platform detection and download button setup
        (() => {
            const cards = document.querySelectorAll('.download-card');
            const mainBtn = document.getElementById('download-main');
            const mainLabel = document.getElementById('download-main-label');
            const mainHint = document.getElementById('download-main-hint');
            const unixSteps = document.getElementById('install-steps-unix');
            const winSteps = document.getElementById('install-steps-windows');
            const envMac = document.getElementById('env-mac');
            const envWin = document.getElementById('env-windows');
            const usageUnix = document.getElementById('usage-unix');
            const usageWin = document.getElementById('usage-windows');
            const installCmdUnix = document.getElementById('install-cmd-unix');
            const installCmdWin = document.getElementById('install-cmd-windows');
            const copyUnix = document.getElementById('copy-unix');
            const copyWin = document.getElementById('copy-win');

            const ua = navigator.userAgent.toLowerCase();
            const platform = navigator.platform || '';

            let detectedOs = 'linux';
            let detectedArch = 'x86_64';

            if (/mac|iphone|ipad|ipod/i.test(platform) || /macintosh/i.test(ua)) {
                detectedOs = 'mac';
            } else if (/win/i.test(platform)) {
                detectedOs = 'windows';
            }

            if (/arm64|aarch64/i.test(ua) || /arm64|aarch64/i.test(platform)) {
                detectedArch = 'aarch64';
            } else if (detectedOs === 'mac' && navigator.hardwareConcurrency && navigator.hardwareConcurrency >= 8) {
                detectedArch = 'aarch64';
            }

            function activate(os, arch) {
                cards.forEach(card => {
                    card.classList.toggle('active', card.dataset.os === os && card.dataset.arch === arch);
                });

                const match = document.querySelector(`.download-card[data-os="${os}"][data-arch="${arch}"]`);
                if (match) {
                    mainBtn.href = match.href;
                    const colHeader = match.closest('.download-col').querySelector('.download-col-header span').textContent;
                    const archLabel = match.querySelector('.download-card-label').textContent;
                    const filename = match.dataset.filename;
                    mainLabel.textContent = `Download for ${colHeader}`;
                    mainHint.textContent = archLabel;

                    const isWin = os === 'windows';
                    unixSteps.style.display = isWin ? 'none' : '';
                    winSteps.style.display = isWin ? '' : 'none';
                    envMac.style.display = isWin ? 'none' : '';
                    envWin.style.display = isWin ? '' : 'none';
                    usageUnix.style.display = isWin ? 'none' : '';
                    usageWin.style.display = isWin ? '' : 'none';

                    if (isWin) {
                        const cmd = `ren ${filename} asana-time.exe && move asana-time.exe %USERPROFILE%\\AppData\\Local\\Microsoft\\WindowsApps\\`;
                        installCmdWin.textContent = cmd;
                        copyWin.dataset.copy = cmd;
                    } else {
                        const cmd = `mv ${filename} asana-time && chmod +x asana-time && sudo mv asana-time /usr/local/bin/`;
                        installCmdUnix.textContent = cmd;
                        copyUnix.dataset.copy = cmd;
                    }
                }
            }

            activate(detectedOs, detectedArch);

            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    activate(card.dataset.os, card.dataset.arch);
                });
            });
        })();

        // Copy buttons
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.copy);
                btn.textContent = 'copied';
                setTimeout(() => btn.textContent = 'copy', 1500);
            });
        });
    </script>
</body>
</html>
