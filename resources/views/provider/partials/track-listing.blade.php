<script>
document.addEventListener('click', function (event) {
    const anchor = event.target.closest('a[href]');
    if (!anchor) return;
    const urls = @json($trackingUrls), website = @json($listingWebsite);
    const href = anchor.getAttribute('href') || '';
    const phones = @json($listingPhones);
    const digits = value => (value || '').replace(/\D/g, '').replace(/^0/, '254');
    const phoneMatches = value => phones.some(phone => phone && digits(value) === digits(phone));
    let metric = null;
    if (/^tel:/i.test(href) && phoneMatches(href.slice(4))) metric = 'phone_click';
    else if (/^https?:\/\/(wa\.me|api\.whatsapp\.com|web\.whatsapp\.com)(\/|$)/i.test(href)) {
        const url = new URL(href);
        if (phoneMatches(url.searchParams.get('phone') || url.pathname)) metric = 'whatsapp_click';
    }
    else if (/^https?:/i.test(href) && website && href.replace(/\/$/, '') === website.replace(/\/$/, '')) metric = 'website_click';
    if (metric) fetch(urls[metric], {credentials: 'same-origin', keepalive: true}).catch(function () {});
});
</script>
