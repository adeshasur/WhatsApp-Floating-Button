/* jshint esversion: 6 */
/**
 * WhatsApp Floating Button v2.0 – Frontend JavaScript
 * Handles: chat bubble popup, click tracking via AJAX.
 */
(function () {
    'use strict';

    var data      = window.wafbData || {};
    var mainBtn   = document.getElementById('wafb-floating-btn');
    var bubble    = document.getElementById('wafb-bubble');
    var closeBtn  = document.getElementById('wafb-bubble-close');

    // ── Click Tracking ──────────────────────────────────────────────────────
    if (data.trackingOn && mainBtn && data.ajaxUrl) {
        mainBtn.addEventListener('click', function () {
            // Dismiss bubble if it's open
            if (bubble && typeof dismissBubble === 'function' && bubble.classList.contains('is-visible')) {
                dismissBubble();
            }

            var fd = new FormData();
            fd.append('action', 'wafb_track_click');
            fd.append('nonce',  data.nonce || '');

            fetch(data.ajaxUrl, {
                method: 'POST',
                body:   fd
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
            }).catch(function (error) {
                console.warn('WAFB: Click tracking failed.', error);
            });
        });
    }

    // ── Chat Bubble Popup ───────────────────────────────────────────────────
    if (data.bubbleEnable && bubble) {

        // Don't show again if dismissed in this browser session.
        var dismissed = sessionStorage.getItem('wafb_bubble_dismissed');
        if (!dismissed) {

            var delayMs = (parseInt(data.bubbleDelay, 10) || 3) * 1000;

            // Show after delay.
            var showTimer = setTimeout(function () {
                bubble.classList.add('is-visible');

                // Typing indicator premium effect
                var typing = document.getElementById('wafb-typing');
                var msg    = document.getElementById('wafb-msg');
                if (typing && msg) {
                    setTimeout(function() {
                        typing.style.display = 'none';
                        msg.style.display    = 'block';
                    }, 1500); // Show typing for 1.5s
                }
            }, delayMs);

            // Close button.
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    dismissBubble(showTimer);
                });
            }

            // Dismiss when user clicks the main WhatsApp button.
            if (mainBtn) {
                mainBtn.addEventListener('click', function () {
                    dismissBubble(showTimer);
                });
            }

            // Auto-dismiss when clicking the bubble CTA.
            var cta = bubble.querySelector('.wafb-bubble__cta');
            if (cta) {
                cta.addEventListener('click', function () {
                    dismissBubble(showTimer);
                });
            }
        }
    }

    function dismissBubble(timer) {
        clearTimeout(timer);
        if (bubble) {
            bubble.classList.remove('is-visible');
        }
        sessionStorage.setItem('wafb_bubble_dismissed', '1');
    }

})();
