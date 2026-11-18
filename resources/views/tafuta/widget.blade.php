{{-- Tafuta chat widget (public pages). Shown only when switched on in Admin and an API key is configured. --}}
<div id="tafuta" class="tafuta" data-endpoint="{{ route('tafuta.message') }}" data-reset="{{ route('tafuta.reset') }}">
    <button type="button" class="tafuta-launcher" aria-controls="tafuta-panel" aria-expanded="false">
        <i class="fas fa-comment-medical" aria-hidden="true"></i><span>{{ __('Ask Tafuta') }}</span>
    </button>
    <section id="tafuta-panel" class="tafuta-panel" role="dialog" aria-label="{{ __('Tafuta assistant') }}" hidden>
        <header class="tafuta-head">
            <div class="tafuta-avatar" aria-hidden="true"><i class="fas fa-search-location"></i></div>
            <div class="tafuta-title"><strong>Tafuta</strong><small>{{ __('Find care on MedFind · English & Kiswahili') }}</small></div>
            <button type="button" class="tafuta-icon" data-tafuta-reset title="{{ __('New chat') }}" aria-label="{{ __('New chat') }}"><i class="fas fa-redo-alt"></i></button>
            <button type="button" class="tafuta-icon" data-tafuta-close aria-label="{{ __('Close') }}"><i class="fas fa-times"></i></button>
        </header>
        <div class="tafuta-log" aria-live="polite">
            <div class="tafuta-msg tafuta-bot">
                <p>{{ __('Hi, I\'m Tafuta. Tell me what you need and where, and I\'ll find listed specialists, facilities, healthcare courses or suppliers for you.') }}</p>
                <p class="tafuta-note">{{ __('I can\'t give medical advice. In an emergency call 999 or 112.') }}</p>
            </div>
            <div class="tafuta-chips">
                <button type="button" data-ask="{{ __('Find a specialist near me') }}">{{ __('Find a specialist near me') }}</button>
                <button type="button" data-ask="{{ __('Which specialist should I see for my symptoms?') }}">{{ __('Which specialist do I need?') }}</button>
                <button type="button" data-ask="{{ __('Find a clinic that accepts SHA') }}">{{ __('Clinics that accept SHA') }}</button>
                <button type="button" data-ask="{{ __('How do I list my clinic on MedFind?') }}">{{ __('List my practice') }}</button>
            </div>
        </div>
        <form class="tafuta-form">
            <label for="tafuta-input" class="visually-hidden">{{ __('Message Tafuta') }}</label>
            <textarea id="tafuta-input" rows="1" maxlength="1000" placeholder="{{ __('e.g. Child specialist in Kisumu') }}" required></textarea>
            <button type="submit" aria-label="{{ __('Send') }}"><i class="fas fa-paper-plane"></i></button>
        </form>
        <p class="tafuta-privacy">{{ __('Chats are kept for 7 days, then deleted.') }} <a href="{{ route('website.privacy-policy') }}">{{ __('Privacy') }}</a></p>
    </section>
</div>
<style>
.tafuta{--t-blue:#2b5fde;--t-ink:#0f172a;--t-soft:#f1f5ff;font-size:14px}
.tafuta-launcher{position:fixed;right:16px;bottom:88px;z-index:1070;display:flex;align-items:center;gap:8px;border:0;border-radius:999px;padding:12px 18px;background:var(--t-blue);color:#fff;font-weight:700;box-shadow:0 8px 24px rgba(43,95,222,.35);cursor:pointer}
.tafuta-launcher:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}
.tafuta-panel{position:fixed;right:16px;bottom:88px;z-index:1080;width:380px;max-width:calc(100vw - 32px);height:min(600px,calc(100vh - 120px));display:flex;flex-direction:column;background:#fff;border-radius:16px;box-shadow:0 18px 50px rgba(15,23,42,.25);overflow:hidden}
.tafuta-panel[hidden]{display:none}
.tafuta-head{display:flex;align-items:center;gap:10px;padding:12px 12px 12px 16px;background:var(--t-blue);color:#fff}
.tafuta-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center}
.tafuta-title{flex:1;display:flex;flex-direction:column;line-height:1.2}.tafuta-title small{opacity:.85;font-size:12px}
.tafuta-icon{border:0;background:transparent;color:#fff;width:34px;height:34px;border-radius:50%;cursor:pointer}.tafuta-icon:hover{background:rgba(255,255,255,.15)}
.tafuta-log{flex:1;overflow-y:auto;padding:14px;background:#f8fafc;display:flex;flex-direction:column;gap:10px}
.tafuta-msg{max-width:88%;padding:10px 12px;border-radius:14px;line-height:1.45;word-wrap:break-word}
.tafuta-msg p{margin:0 0 6px}.tafuta-msg p:last-child{margin:0}.tafuta-msg ul{margin:4px 0;padding-left:18px;list-style:disc}.tafuta-msg li{list-style:disc;margin:2px 0}
.tafuta-msg a{color:var(--t-blue);text-decoration:underline;word-break:break-all}
.tafuta-bot{align-self:flex-start;background:#fff;border:1px solid #e2e8f0;color:var(--t-ink)}
.tafuta-user{align-self:flex-end;background:var(--t-blue);color:#fff}
.tafuta-note{font-size:12px;color:#64748b}
.tafuta-emergency{align-self:stretch;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;padding:10px 12px;font-weight:600}
.tafuta-chips{display:flex;flex-wrap:wrap;gap:6px}
.tafuta-chips button{border:1px solid #c7d2fe;background:var(--t-soft);color:var(--t-blue);border-radius:999px;padding:6px 12px;font-size:13px;cursor:pointer}
.tafuta-cards{display:flex;flex-direction:column;gap:8px;align-self:stretch}
.tafuta-card{display:flex;gap:10px;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px;text-decoration:none!important;color:var(--t-ink)!important}
.tafuta-card:hover{border-color:var(--t-blue)}
.tafuta-card img,.tafuta-card .ph{width:44px;height:44px;border-radius:10px;object-fit:cover;flex-shrink:0;background:var(--t-soft);color:var(--t-blue);display:flex;align-items:center;justify-content:center;font-weight:700}
.tafuta-card strong{display:block;font-size:14px}.tafuta-card small{display:block;color:#64748b;font-size:12px}
.tafuta-card .v{color:#05603a;font-weight:600}
.tafuta-typing{align-self:flex-start;color:#64748b;font-size:13px}
.tafuta-form{display:flex;gap:8px;padding:10px;border-top:1px solid #e2e8f0;background:#fff}
.tafuta-form textarea{flex:1;resize:none;border:1px solid #cbd5e1;border-radius:12px;padding:9px 12px;font:inherit;max-height:110px}
.tafuta-form textarea:focus{outline:2px solid #93c5fd;border-color:var(--t-blue)}
.tafuta-form button{border:0;width:42px;border-radius:12px;background:var(--t-blue);color:#fff;cursor:pointer}.tafuta-form button:disabled{opacity:.5}
.tafuta-privacy{margin:0;padding:0 12px 8px;font-size:11px;color:#94a3b8;background:#fff}.tafuta-privacy a{color:#94a3b8;font-size:11px;text-decoration:underline}
@media (max-width:575.98px){.tafuta-launcher{right:12px;bottom:76px;padding:11px 15px}.tafuta-panel{inset:0;width:auto;max-width:none;height:100%;border-radius:0}}
</style>
<script>
(function () {
    "use strict";
    var root = document.getElementById('tafuta'); if (!root) return;
    var launcher = root.querySelector('.tafuta-launcher'), panel = root.querySelector('.tafuta-panel'), log = root.querySelector('.tafuta-log'),
        form = root.querySelector('.tafuta-form'), input = root.querySelector('#tafuta-input'), send = form.querySelector('button'),
        token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var intro = log.innerHTML;

    function open(state) { panel.hidden = !state; launcher.hidden = state; launcher.setAttribute('aria-expanded', state); if (state) setTimeout(function () { input.focus(); }, 50); }
    launcher.addEventListener('click', function () { open(true); });
    root.querySelector('[data-tafuta-close]').addEventListener('click', function () { open(false); launcher.focus(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { open(false); launcher.focus(); } });
    root.querySelector('[data-tafuta-reset]').addEventListener('click', function () {
        if (send.disabled) return;
        fetch(root.dataset.reset, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
        log.innerHTML = intro; bindChips();
    });

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function format(text) {
        var lines = esc(text).split(/\n/), html = '', list = false;
        lines.forEach(function (line) {
            line = line.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                .replace(/(^|[\s(])(https?:\/\/[^\s<)]+)/g, '$1<a href="$2" target="_blank" rel="noopener">$2</a>');
            var item = line.match(/^\s*(?:[-*•]|\d+[.)])\s+(.*)$/);
            if (item) { if (!list) { html += '<ul>'; list = true; } html += '<li>' + item[1] + '</li>'; return; }
            if (list) { html += '</ul>'; list = false; }
            if (line.trim()) html += '<p>' + line + '</p>';
        });
        return html + (list ? '</ul>' : '');
    }
    function add(cls, html) { var d = document.createElement('div'); d.className = cls; d.innerHTML = html; log.appendChild(d); log.scrollTop = log.scrollHeight; return d; }
    function cards(list) {
        if (!list || !list.length) return;
        var html = list.map(function (c) {
            var pic = c.image ? '<img src="' + esc(c.image) + '" alt="" loading="lazy">' : '<span class="ph">' + esc((c.name || '?').charAt(0)) + '</span>';
            var meta = [c.specialty, c.location].filter(Boolean).map(esc).join(' · ');
            var extra = (c.verified_licence ? '<small class="v"><i class="fas fa-check-circle"></i> {{ __('Licence verified by MedFind') }}</small>' : '') + (c.consultation_fee_kes ? '<small>{{ __('Consultation') }}: KES ' + Number(c.consultation_fee_kes).toLocaleString() + '</small>' : '');
            return '<a class="tafuta-card" href="' + esc(c.url) + '">' + pic + '<span><strong>' + esc(c.name) + '</strong><small>' + meta + '</small>' + extra + '</span></a>';
        }).join('');
        add('tafuta-cards', html);
    }
    function ask(text, lookDeeper = false) {
        text = text.trim(); if (!text || send.disabled) return;
        log.querySelectorAll('.tafuta-chips').forEach(function (el) { el.remove(); });
        if (!lookDeeper) add('tafuta-msg tafuta-user', format(text));
        input.value = ''; input.style.height = ''; send.disabled = true;
        var typing = add('tafuta-typing', lookDeeper ? @json(__('Tafuta is checking your question…')) : @json(__('Tafuta is searching…')));
        fetch(root.dataset.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ message: text, look_deeper: lookDeeper }) })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (data) {
                typing.remove();
                if (data.emergency) add('tafuta-emergency', '<i class="fas fa-exclamation-triangle"></i> {{ __('If this is an emergency, call 999 or 112 now, or go to the nearest emergency department. Kenya Red Cross: 1199.') }}');
                add('tafuta-msg tafuta-bot', format(data.reply || '{{ __('Sorry, something went wrong. Please try again.') }}'));
                cards(data.cards);
                if (data.offer_more_help) {
                    var options = add('tafuta-chips', '');
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = @json(__('Look into it'));
                    var notice = document.createElement('small');
                    notice.textContent = @json(__('Tafuta uses AI for this answer. Leave out personal or medical details.'));
                    options.append(button, notice);
                    button.addEventListener('click', function () { ask(text, true); });
                    log.scrollTop = log.scrollHeight;
                }
            })
            .catch(function () { typing.remove(); add('tafuta-msg tafuta-bot', '<p>{{ __('Connection problem. Please try again.') }}</p>'); })
            .finally(function () { send.disabled = false; input.focus(); });
    }
    function bindChips() { log.querySelectorAll('[data-ask]').forEach(function (b) { b.addEventListener('click', function () { ask(b.dataset.ask); }); }); }
    bindChips();
    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ask(input.value); } });
    input.addEventListener('input', function () { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 110) + 'px'; });
})();
</script>
