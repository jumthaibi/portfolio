(function () {
    "use strict";

    /* ============================================================
       Light / dark theme toggle — shared across every page
       ============================================================ */
    var root = document.documentElement;
    var toggle = document.getElementById('themeToggle');

    // Theme choice is per-visitor and per-visit only (sessionStorage), never
    // saved permanently and never sent to the server. It survives a refresh
    // but resets once the visitor closes the tab/browser and comes back later
    // — same rule as the accent color below. On a brand-new visit (nothing
    // saved yet), we open in light mode only if the visitor's OS explicitly
    // prefers light; otherwise (dark OS preference, or no preference at all)
    // we keep dark as the site's default.
    var saved = sessionStorage.getItem('theme');
    if (saved) {
        root.setAttribute('data-theme', saved);
    } else if (window.matchMedia('(prefers-color-scheme: light)').matches) {
        root.setAttribute('data-theme', 'light');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            var current = root.getAttribute('data-theme') || 'dark';
            var next = current === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            sessionStorage.setItem('theme', next);
        });
    }

    /* ============================================================
       Unread-messages badge (admin dashboard only) — clicking the
       Messages nav link marks everything read for next time
       ============================================================ */
    var messagesLink = document.querySelector('a[href="#messages"]');
    var messagesBadge = document.getElementById('messagesBadge');
    if (messagesLink && messagesBadge) {
        messagesLink.addEventListener('click', function () {
            fetch('admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'mark_messages_read=1'
            }).catch(function () { /* badge will just reappear on next load if this fails */ });
            messagesBadge.remove();
        });
    }

    /* ============================================================
       Accent color picker — pink / yellow / purple / light blue /
       green, muted tones only, swapped via a small popover
       ============================================================ */
    var accentToggle = document.getElementById('accentToggle');
    var accentMenu = document.getElementById('accentMenu');
    var accentSwatches = document.querySelectorAll('.accent-swatch');

    // On the admin dashboard the menu is a real <form> that POSTs the choice
    // to the server and saves it as the site-wide default. On the public
    // site it's a plain menu: the choice only applies to this visitor, only
    // for the current browsing session (sessionStorage), then reverts back
    // to whatever the admin set as default.
    var isSiteDefaultForm = accentMenu && accentMenu.tagName === 'FORM';
    var savedAccent = isSiteDefaultForm ? null : sessionStorage.getItem('accent');
    if (savedAccent) root.setAttribute('data-accent', savedAccent);

    function setActiveSwatch(name) {
        accentSwatches.forEach(function (sw) {
            sw.classList.toggle('active', sw.getAttribute('data-accent') === name);
        });
    }
    setActiveSwatch(savedAccent || root.getAttribute('data-accent') || 'blue');

    if (accentToggle && accentMenu) {
        accentToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            accentMenu.classList.toggle('open');
        });
        if (!isSiteDefaultForm) {
            accentSwatches.forEach(function (sw) {
                sw.addEventListener('click', function () {
                    var name = sw.getAttribute('data-accent');
                    root.setAttribute('data-accent', name);
                    sessionStorage.setItem('accent', name);
                    setActiveSwatch(name);
                    accentMenu.classList.remove('open');
                });
            });
        }
        document.addEventListener('click', function (e) {
            if (!accentMenu.contains(e.target) && e.target !== accentToggle) {
                accentMenu.classList.remove('open');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') accentMenu.classList.remove('open');
        });
    }

    /* Respect users who'd rather not see motion */
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ============================================================
       Typing effect for the hero name on first load
       ============================================================ */
    var heroNameEl = document.getElementById('heroName');
    if (heroNameEl) {
        var firstEl = document.getElementById('heroNameFirst');
        var restEl = document.getElementById('heroNameRest');
        var firstText = heroNameEl.getAttribute('data-first') || '';
        var restText = heroNameEl.getAttribute('data-rest') || '';
        var fullLen = firstText.length + restText.length;

        if (reduceMotion || !fullLen) {
            if (firstEl) firstEl.textContent = firstText;
            if (restEl) restEl.textContent = restText;
            heroNameEl.classList.add('typing-done');
        } else {
            var charIndex = 0;
            var typeSpeed = 65;
            (function typeChar() {
                if (charIndex < firstText.length) {
                    firstEl.textContent += firstText.charAt(charIndex);
                } else if (charIndex < fullLen) {
                    restEl.textContent += restText.charAt(charIndex - firstText.length);
                } else {
                    heroNameEl.classList.add('typing-done');
                    return;
                }
                charIndex++;
                setTimeout(typeChar, typeSpeed);
            })();
        }
    }

    if (reduceMotion) return;

    /* ============================================================
       Ambient background orbs — drift on their own, nudge toward
       the cursor for a subtle parallax feel
       ============================================================ */
    var orbs = document.querySelectorAll('.bg-orb');
    if (orbs.length) {
        window.addEventListener('mousemove', function (e) {
            var cx = window.innerWidth / 2;
            var cy = window.innerHeight / 2;
            orbs.forEach(function (orb) {
                var depth = parseFloat(orb.getAttribute('data-depth')) || 1;
                var dx = (e.clientX - cx) * 0.025 * depth;
                var dy = (e.clientY - cy) * 0.025 * depth;
                orb.style.translate = dx.toFixed(1) + 'px ' + dy.toFixed(1) + 'px';
            });
        }, { passive: true });
    }

    /* ============================================================
       Cursor-tracking glow on hero/header/cards — a soft light
       that follows the pointer while it's over the element
       ============================================================ */
    var glowEls = document.querySelectorAll('.cursor-glow');
    if (glowEls.length) {
        document.addEventListener('mousemove', function (e) {
            glowEls.forEach(function (el) {
                var r = el.getBoundingClientRect();
                var inside = e.clientX >= r.left && e.clientX <= r.right &&
                             e.clientY >= r.top && e.clientY <= r.bottom;
                if (inside) {
                    el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                    el.style.setProperty('--my', (e.clientY - r.top) + 'px');
                    el.classList.add('is-glowing');
                } else {
                    el.classList.remove('is-glowing');
                }
            });
        }, { passive: true });
    }
})();
