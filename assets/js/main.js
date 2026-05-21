/* ==========================================
   وظيفة المفضلة (Toggle Favorite)
   ========================================== */
function toggleFavorite(btn, id, type = 'movie') {

    let url = '';

    if(type === 'series'){
        url = 'auth/toggle_favorite.php?series_id=' + id;
    } else {
        url = 'auth/toggle_favorite.php?movie_id=' + id;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {

            if (data.status === 'added') {
                btn.setAttribute('data-fav', 'true');
            } 
            else if (data.status === 'removed') {
                btn.setAttribute('data-fav', 'false');

                // حذف الكارت لو في صفحة المفضلة
                if (window.location.pathname.includes('favorites.php')) {
                    const cardElement = btn.closest('.movie-card-parent');
                    if (cardElement) {
                        cardElement.style.transition = 'all 0.5s ease';
                        cardElement.style.opacity = '0';
                        cardElement.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            cardElement.remove();
                            const remainingCards = document.querySelectorAll('.movie-card-parent');
                            if (remainingCards.length === 0) {
                                location.reload();
                            }
                        }, 500);
                    }
                }
            } else {
                alert(data.message);
            }

        })
        .catch(error => console.error('Error:', error));
}

/* ==========================================
   وظيفة التريلر الذكي (الموبايل المطور)
   ========================================== */
window.hoverTimers = window.hoverTimers || {};
window.loopTimers = window.loopTimers || {};
window.activeCard = null; // لتتبع الكارت الشغال حالياً

function startTrailerTimer(cardElement, videoId) {
    if (!videoId || videoId.trim() === "") return;

    // [جديد]: إذا لمس المستخدم كارت جديد، نغلق الكارت القديم فوراً
    if (window.activeCard && window.activeCard !== cardElement) {
        stopTrailerTimer(window.activeCard);
    }
    window.activeCard = cardElement;

    const cardId = videoId;
    const layer = cardElement.querySelector('.trailer-preview-layer');

    // تحميل الفيديو فوراً في الخلفية
    const randomStart = Math.floor(Math.random() * 50) + 10;
    if (layer && layer.innerHTML === '') {
        layer.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&controls=0&rel=0&start=${randomStart}" frameborder="0" allow="autoplay"></iframe>`;
        layer.style.opacity = '0';
    }

    clearTimeout(window.hoverTimers[cardId]);

    // البدء التلقائي: سيستمر العداد حتى لو رفع المستخدم إصبعه
    window.hoverTimers[cardId] = setTimeout(() => {
        showPreparedTrailer(cardElement, videoId);
    }, 2000); 
}

function showPreparedTrailer(cardElement, videoId) {
    const layer = cardElement.querySelector('.trailer-preview-layer');
    const overlay = cardElement.querySelector('.card-overlay');
    const staticTitle = cardElement.querySelector('.static-title-box');
    const mainImg = cardElement.querySelector('.main-img');

    if (!layer || window.activeCard !== cardElement) return;

    layer.style.opacity = '1';
    if (overlay) overlay.style.opacity = '0';
    if (staticTitle) staticTitle.style.opacity = '0';
    if (mainImg) mainImg.style.opacity = '0';

    const cardId = videoId;
    clearTimeout(window.loopTimers[cardId]);
    window.loopTimers[cardId] = setTimeout(() => {
        // التحقق أن الكارت لا يزال هو الكارت النشط
        if (window.activeCard === cardElement) {
            playShortClip(cardElement, videoId); 
        }
    }, 10000);
}

function playShortClip(cardElement, videoId) {
    const layer = cardElement.querySelector('.trailer-preview-layer');
    const randomStart = Math.floor(Math.random() * 50) + 10;
    if (layer && window.activeCard === cardElement) {
        layer.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&controls=0&rel=0&start=${randomStart}" frameborder="0" allow="autoplay"></iframe>`;
    }
}

function stopTrailerTimer(cardElement) {
    // تنظيف التايمرز الخاصة بهذا الكارت
    if (window.hoverTimers) Object.values(window.hoverTimers).forEach(t => clearTimeout(t));
    if (window.loopTimers) Object.values(window.loopTimers).forEach(t => clearTimeout(t));

    const layer = cardElement.querySelector('.trailer-preview-layer');
    const overlay = cardElement.querySelector('.card-overlay');
    const staticTitle = cardElement.querySelector('.static-title-box');
    const mainImg = cardElement.querySelector('.main-img');

    if (layer) {
        layer.innerHTML = '';
        layer.style.opacity = '0';
        if (overlay) overlay.style.opacity = ''; 
        if (staticTitle) staticTitle.style.opacity = '';
        if (mainImg) mainImg.style.opacity = '1';
    }
}

// [جديد]: لإيقاف الفيديو عند الضغط في أي مكان فارغ في الشاشة
document.addEventListener('touchstart', function(e) {
    if (!e.target.closest('.movie-card-parent') && window.activeCard) {
        stopTrailerTimer(window.activeCard);
        window.activeCard = null;
    }
});