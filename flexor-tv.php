<?php
session_start();
$page_title = "بث مباشر قنوات بدون تقطيع | Flexor";
$page_desc  = "شاهد القنوات المباشرة بجودة عالية بدون تقطيع على Flexor";
$page_img   = "https://flexor.gt.tc/public/logo.png";

include('includes/header.php');
$isLogged = isset($_SESSION['user_id']) ? 'true' : 'false';
?>


<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "BroadcastService",
 "name": "Flexor Live TV",
 "areaServed": "EG",
 "inLanguage": "ar",
 "url": "https://flexor.gt.tc/flexor-tv.php"
}
</script>

<script>
const IS_LOGGED = <?= $isLogged ?>;
</script>

<div class="main-live-wrapper">
    <div class="live-layout-container">
		<h1 style="display:none">
مشاهدة القنوات بث مباشر بجودة عالية
</h1>
        <!-- SECTION: PLAYER -->
        <div class="player-section">
            <div class="current-channel-header" id="channelHeader">
                <div class="header-info">
                    <div class="channel-icon-placeholder">
                        <span id="status-dot" class="dot"></span>
                        📺
                    </div>
                    <div class="title-meta">
                        <h2 id="top-channel-name">اختر قناة للبدء</h2>
                        <div class="status-badge">
                            <span id="stream-status">جاهز للبث المباشر</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="video-container shadow-lg">
                <div class="video-overlay" id="overlay" onclick="togglePlay()">
                    <div class="play-pulse">
                        <div class="play-btn">▶</div>
                    </div>
                    <p>اضغط للتشغيل المباشر</p>
                </div>
                
                <div class="loader-wrapper" id="loader">
                    <div class="custom-loader"></div>
                    <span>جاري تحميل البث...</span>
                </div>

                <div class="player-controls-bar">
                    <div class="left-ctrl">
                        <button onclick="togglePlay()" class="ctrl-btn" id="playPauseBtn">⏸</button>
                        <button onclick="toggleMute()" class="ctrl-btn" id="muteBtn">🔊</button>
                    </div>
                    <div class="right-ctrl">
                        <button onclick="toggleFullscreen()" class="ctrl-btn">⛶</button>
                    </div>
                </div>
                <video id="live-player" playsinline crossorigin="anonymous"></video>
            </div>
        </div>

        <!-- SECTION: SIDEBAR -->
        <div class="channels-sidebar">
            <div class="sidebar-header">
                <div class="tabs-modern">
                    <button class="tab-m active" id="tab-all" onclick="showTab('all')">كل القنوات</button>
                    <button class="tab-m" id="tab-fav" onclick="showTab('fav')">المفضلة ✨</button>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="ابحث عن قناتك..." />
                </div>
            </div>

            <div class="sidebar-list" id="allChannels"></div>
            <div class="sidebar-list" id="favChannels" style="display:none;"></div>
        </div>

    </div>
</div>

<style>
:root {
    --primary: #d4ff00;
    --primary-glow: rgba(212, 255, 0, 0.3);
    --bg-dark: #080808;
    --surface: #121212;
    --border: #222;
    --text-main: #ffffff;
    --text-dim: #888;
}

body { background: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; }

.main-live-wrapper { max-width: 1400px; margin: 80px auto 20px; padding: 0 15px; direction: rtl; }
.live-layout-container { display: flex; gap: 20px; flex-wrap: wrap; }

/* Player UI */
.player-section { flex: 2.5; min-width: 0; }
.current-channel-header { 
    background: #181818; 
    padding: 15px; 
    border-radius: 15px 15px 0 0; 
    border: 1px solid var(--border);
    border-bottom: none;
}
.header-info { display: flex; align-items: center; gap: 12px; }
.channel-icon-placeholder { 
    width: 45px; height: 45px; background: #222; border-radius: 10px; 
    display: flex; align-items: center; justify-content: center; 
    font-size: 1.5rem; position: relative; border: 1px solid #333;
}
.dot { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #555; border-radius: 50%; border: 2px solid #181818; }
.dot.live { background: #ff3e3e; box-shadow: 0 0 8px #ff3e3e; animation: pulse 1.5s infinite; }

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

#top-channel-name { margin: 0; font-size: 1.1rem; font-weight: 700; }
.status-badge { font-size: 0.75rem; color: var(--text-dim); }

.video-container { 
    position: relative; background: #000; border-radius: 0 0 15px 15px; 
    overflow: hidden; aspect-ratio: 16/9; border: 1px solid var(--border);
}
#live-player { width: 100%; height: 100%; object-fit: contain; }

.player-controls-bar {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.85));
    padding: 15px; display: flex; justify-content: space-between;
    opacity: 0; transition: 0.3s; z-index: 5;
}
.video-container:hover .player-controls-bar { opacity: 1; }
.ctrl-btn { 
    background: rgba(255,255,255,0.1); border: none; color: #fff; 
    width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
    font-size: 1.1rem; transition: 0.2s; backdrop-filter: blur(5px);
}

/* Sidebar UI */
.channels-sidebar { 
    flex: 1; min-width: 320px; background: var(--surface); 
    border-radius: 15px; height: 650px; display: flex; 
    flex-direction: column; border: 1px solid var(--border);
}
.sidebar-header { padding: 15px; border-bottom: 1px solid var(--border); }
.tabs-modern { display: flex; background: #000; padding: 4px; border-radius: 10px; margin-bottom: 12px; }
.tab-m { flex: 1; padding: 8px; border: none; border-radius: 8px; background: transparent; color: var(--text-dim); cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
.tab-m.active { background: var(--primary); color: #000; }

.search-box { position: relative; }
.search-box input { width: 100%; padding: 10px 35px 10px 10px; border-radius: 10px; border: 1px solid #333; background: #080808; color: #fff; outline: none; font-size: 0.9rem; box-sizing: border-box; }
.search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #555; }

.sidebar-list { flex: 1; overflow-y: auto; padding: 8px; scrollbar-width: thin; scrollbar-color: #333 transparent; }
.sidebar-channel-item { 
    display: flex; align-items: center; gap: 12px; padding: 12px; 
    border-radius: 12px; cursor: pointer; margin-bottom: 5px; 
    transition: 0.2s; border: 1px solid transparent;
}
.sidebar-channel-item:hover { background: #1a1a1a; }
.sidebar-channel-item.active-channel { background: var(--primary-glow); border-color: var(--primary); }
.ch-name { flex: 1; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fav-btn { cursor: pointer; padding: 5px; font-size: 1.1rem; }

.loader-wrapper { position: absolute; inset: 0; background: #000; display: none; flex-direction: column; align-items: center; justify-content: center; z-index: 10; gap: 10px; }
.custom-loader { width: 35px; height: 35px; border: 3px solid #222; border-top: 3px solid var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Mobile Adjustments */
@media (max-width: 768px) {
    .main-live-wrapper { margin: 60px auto 10px; padding: 0 5px; }
    .live-layout-container { flex-direction: column; gap: 10px; }
    .player-section { order: 1; }
    .channels-sidebar { order: 2; height: 450px; min-width: 100%; border-radius: 15px; }
    .current-channel-header { padding: 10px; border-radius: 0; }
    .video-container { border-radius: 0; border-left: none; border-right: none; }
    .sidebar-channel-item { padding: 10px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
let allChannels = [];
let favIds = [];
let filteredChannels = [];
let page = 0;
let pageSize = 40;
let hls;

const video = document.getElementById('live-player');
const statusText = document.getElementById('stream-status');
const statusDot = document.getElementById('status-dot');

async function init() {
    await fetchFavorites();
    await loadChannels();
}

async function fetchFavorites() {
    if(!IS_LOGGED) return;
    try {
        let res = await fetch('/api/favorites.php');
        let data = await res.json();
        if(data.status === "success") favIds = data.channels.map(ch => parseInt(ch.id));
    } catch(e) { console.error("Error loading favorites"); }
}

async function loadChannels() {
    try {
        let res = await fetch('/api/channels.php');
        let data = await res.json();
        allChannels = data.channels;
        filteredChannels = [...allChannels];
        renderInitialBatch();
    } catch(e) { statusText.innerText = "فشل تحديث القنوات"; }
}

function renderInitialBatch() {
    page = 0;
    document.getElementById('allChannels').innerHTML = "";
    loadMore();
}

function loadMore() {
    let start = page * pageSize;
    let slice = filteredChannels.slice(start, start + pageSize);
    if(slice.length === 0) return;
    renderList(slice, 'allChannels', true);
    page++;
}

function renderList(list, containerId, append = false) {
    const container = document.getElementById(containerId);
    if (!append) container.innerHTML = "";
    const fragment = document.createDocumentFragment();
    list.forEach(ch => {
        const isFav = favIds.includes(parseInt(ch.id));
        const item = document.createElement('div');
        item.className = "sidebar-channel-item";
        item.innerHTML = `
            <span>📺</span>
            <span class="ch-name">${ch.channel_name}</span>
            <div class="fav-btn" onclick="toggleFavorite(event, ${ch.id}, this)">
                ${isFav ? '❤️' : '🤍'}
            </div>
        `;
        item.onclick = () => {
            document.querySelectorAll('.sidebar-channel-item').forEach(el => el.classList.remove('active-channel'));
            item.classList.add('active-channel');
            changeChannel(ch);
            // Scroll to top on mobile when clicking channel
            if(window.innerWidth < 768) window.scrollTo({top: 0, behavior: 'smooth'});
        };
        fragment.appendChild(item);
    });
    container.appendChild(fragment);
}

function changeChannel(channel) {
    document.getElementById('overlay').style.display = "none";
    document.getElementById('loader').style.display = "flex";
    document.getElementById('top-channel-name').innerText = channel.channel_name;
    statusText.innerText = "جاري الاتصال المباشر...";
    statusDot.className = "dot";

    // اتصال مباشر 100% بدون وسيط
    const finalUrl = channel.stream_url;

    if (hls) hls.destroy();

    if (Hls.isSupported()) {
        hls = new Hls({
            enableWorker: true,
            maxBufferLength: 30,
            startLevel: 0
        });
        hls.loadSource(finalUrl);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            document.getElementById('loader').style.display = "none";
            video.play().catch(() => {
                 document.getElementById('overlay').style.display = "flex";
            });
            statusText.innerText = "بث مباشر مستقر ✅";
            statusDot.className = "dot live";
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
            if (data.fatal) {
                statusText.innerText = "عذراً، هذا البث غير متاح حالياً";
                statusDot.className = "dot";
                document.getElementById('loader').style.display = "none";
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // دعم Native HLS (مثل متصفح Safari على iPhone)
        video.src = finalUrl;
        video.addEventListener('loadedmetadata', () => {
            document.getElementById('loader').style.display = "none";
            video.play();
            statusText.innerText = "بث مباشر مستقر ✅";
            statusDot.className = "dot live";
        });
    }
}

async function toggleFavorite(event, chId, btn) {
    event.stopPropagation();
    if(!IS_LOGGED) { alert("يرجى تسجيل الدخول أولاً"); return; }
    let formData = new FormData();
    formData.append('channel_id', chId);
    try {
        let res = await fetch('/api/favorites.php', { method: 'POST', body: formData });
        let data = await res.json();
        if(data.status === 'added') {
            btn.innerText = '❤️';
            favIds.push(parseInt(chId));
        } else {
            btn.innerText = '🤍';
            favIds = favIds.filter(id => id !== parseInt(chId));
        }
    } catch(e) { console.error("Fav toggle error"); }
}

function showTab(tab) {
    document.getElementById('tab-all').classList.toggle('active', tab === 'all');
    document.getElementById('tab-fav').classList.toggle('active', tab === 'fav');
    document.getElementById('allChannels').style.display = (tab === 'all' ? 'block' : 'none');
    document.getElementById('favChannels').style.display = (tab === 'fav' ? 'block' : 'none');
    if(tab === 'fav') {
        const favList = allChannels.filter(ch => favIds.includes(parseInt(ch.id)));
        renderList(favList, 'favChannels', false);
    }
}

let searchTimeout;
document.getElementById("searchInput").addEventListener("input", function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        let val = this.value.toLowerCase();
        filteredChannels = allChannels.filter(ch => ch.channel_name.toLowerCase().includes(val));
        renderInitialBatch();
    }, 300);
});

document.getElementById('allChannels').addEventListener('scroll', function() {
    if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20) loadMore();
});

function togglePlay() { 
    if(video.paused) { video.play(); document.getElementById('playPauseBtn').innerText = '⏸'; }
    else { video.pause(); document.getElementById('playPauseBtn').innerText = '▶'; }
}
function toggleMute() { 
    video.muted = !video.muted;
    document.getElementById('muteBtn').innerText = video.muted ? '🔇' : '🔊';
}
function toggleFullscreen() { 
    if (video.requestFullscreen) video.requestFullscreen();
    else if (video.webkitRequestFullscreen) video.webkitRequestFullscreen();
    else if (video.msRequestFullscreen) video.msRequestFullscreen();
}

window.onload = init;
</script>