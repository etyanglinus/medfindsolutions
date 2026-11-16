<script>
/* ─────────────────────────────────────────────────────────────────────────
   Subscription coupon + pricing engine
   Shared across doctor-subscription, clinic-subscription, partner-subscription.

   Globals exposed:
     window.subscriptionQuote(packageId, couponCode) → Promise<QuoteObject>
     window.applyQuoteToUI(quote, originalPlanPrice)

   QuoteObject: { original_price, discount_amount, price }
───────────────────────────────────────────────────────────────────────── */
"use strict";

/* ── 1. Quote API ──────────────────────────────────────────────────────── */
window.subscriptionQuote = async function (packageId, code) {
    const response = await fetch(@json(route('subscription.quote', ['type' => $couponType])), {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  @json(csrf_token()),
        },
        body: JSON.stringify({
            package:     packageId,
            coupon_code: code,
            token:       @json($couponToken ?? null),
        }),
    });
    const data = await response.json();
    if (!response.ok) {
        throw new Error(
            data.errors?.coupon_code?.[0] ||
            data.message ||
            @json(__('Unable to validate coupon.'))
        );
    }
    return data; // { original_price, discount_amount, price }
};

/* ── 2. UI helpers ─────────────────────────────────────────────────────── */
function fmt(n) {
    return 'KSh\u00a0' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

/**
 * Apply a quote object to the page:
 *  - Show Was / Now price summary
 *  - Show / hide the FREE banner
 *  - Show / hide the payment method panels
 *
 * @param {Object} quote            { original_price, discount_amount, price }
 * @param {number} originalPlanPrice  The raw plan price (before any coupon)
 */
window.applyQuoteToUI = function (quote, originalPlanPrice) {
    const discounted = Number(quote.price);
    const original   = Number(quote.original_price || originalPlanPrice);
    const saving     = Number(quote.discount_amount);
    const isFree     = discounted === 0;
    const hasCoupon  = saving > 0;

    /* Price summary block */
    const summary = document.getElementById('coupon-price-summary');
    if (summary) {
        if (hasCoupon) {
            document.getElementById('cps-original').textContent   = fmt(original);
            document.getElementById('cps-discounted').textContent = fmt(discounted);
            document.getElementById('cps-savings-badge').textContent =
                '−' + fmt(saving) + ' ' + @json(__('saved'));
            summary.style.display = '';
        } else {
            summary.style.display = 'none';
        }
    }

    /* FREE banner */
    const freeBanner = document.getElementById('coupon-free-banner');
    if (freeBanner) freeBanner.classList.toggle('d-none', !isFree);

    /* Payment method panels — hide everything when free */
    const paymentMethodButtons = document.getElementById('paymentMethodButtons');
    const panelPaystack        = document.getElementById('panel-paystack');
    const panelBank            = document.getElementById('panel-bank');
    const txCodeWrapper        = document.getElementById('tx-code-wrapper');  // bank tx field wrapper (optional id)

    if (paymentMethodButtons) paymentMethodButtons.style.display  = isFree ? 'none' : '';
    if (panelPaystack)        panelPaystack.style.display         = isFree ? 'none' : '';

    if (panelBank) {
        if (isFree) {
            panelBank.classList.remove('d-none');
            panelBank.style.display = '';
            /* Hide the transaction-code input row — not needed when free */
            if (txCodeWrapper) txCodeWrapper.style.display = 'none';
        } else {
            if (txCodeWrapper) txCodeWrapper.style.display = '';
        }
    }

    /* Update Paystack button label */
    const psLabel = document.getElementById('paystack-btn-label');
    if (psLabel) psLabel.textContent = @json(__('Pay with Paystack')) + ' — ' + fmt(discounted);
};

/* ── 3. DOM wiring ─────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const input   = document.getElementById('coupon-input');
    const message = document.getElementById('coupon-msg');
    if (!input || !message) return;

    /* Inject coupon_code into every form that has a transaction_code field */
    function syncCode() {
        document.querySelectorAll('form').forEach(function (form) {
            let hidden = form.querySelector('[name="coupon_code"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'coupon_code';
                form.appendChild(hidden);
            }
            hidden.value = input.value.trim();
        });
    }

    function selectedPackage() {
        const checked = document.querySelector('input[name="package"]:checked');
        if (checked) return checked.value;
        const sel = document.querySelector('select[name="package"]');
        if (sel)  return sel.value;
        const any = document.querySelector('input[name="package"]');
        return any?.value || '';
    }

    function currentPlanPrice() {
        const checked = document.querySelector('input[name="package"]:checked');
        return checked ? parseFloat(checked.dataset.price || 0) : 0;
    }

    let revision = 0;

    async function apply() {
        const current = ++revision;
        syncCode();

        const code = input.value.trim();

        /* No coupon — reset UI to full price */
        if (!code) {
            message.textContent = '';
            const planPrice = currentPlanPrice();
            window.applyQuoteToUI(
                { original_price: planPrice, discount_amount: 0, price: planPrice },
                planPrice
            );
            /* Ensure tx field visible when no coupon */
            const txWrapper = document.getElementById('tx-code-wrapper');
            if (txWrapper) txWrapper.style.display = '';
            return;
        }

        message.className   = 'mt-2 small text-muted';
        message.textContent = @json(__('Checking coupon…'));

        try {
            const quote = await window.subscriptionQuote(selectedPackage(), code);
            if (current !== revision) return;

            window.applyQuoteToUI(quote, currentPlanPrice());

            if (Number(quote.price) === 0) {
                message.className   = 'mt-2 small text-success fw-semibold';
                message.textContent = '✓ ' + @json(__('Coupon applied — no payment needed!'));
            } else {
                message.className   = 'mt-2 small text-success';
                message.textContent = '✓ ' + @json(__('Coupon applied.'))
                    + ' ' + @json(__('You pay')) + ' ' + fmt(quote.price)
                    + ' ' + @json(__('(saving')) + ' ' + fmt(quote.discount_amount) + ').';
            }
        } catch (error) {
            if (current !== revision) return;
            message.className   = 'mt-2 small text-danger';
            message.textContent = '✗ ' + error.message;
            /* Reset UI to full plan price on error */
            const planPrice = currentPlanPrice();
            window.applyQuoteToUI(
                { original_price: planPrice, discount_amount: 0, price: planPrice },
                planPrice
            );
        }
    }

    /* Re-check when user changes plan */
    document.querySelectorAll('input[name="package"], select[name="package"]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (input.value.trim()) apply();
        });
    });

    input.addEventListener('input', function () {
        ++revision;
        syncCode();
        if (!this.value.trim()) {
            message.textContent = '';
            const planPrice = currentPlanPrice();
            window.applyQuoteToUI(
                { original_price: planPrice, discount_amount: 0, price: planPrice },
                planPrice
            );
        } else {
            message.className   = 'mt-2 small text-muted';
            message.textContent = @json(__('Press "Apply" to check this coupon.'));
        }
    });

    document.getElementById('apply-coupon-btn').addEventListener('click', apply);

    syncCode();
    /* Auto-apply if coupon pre-filled (e.g. old() flash) */
    if (input.value.trim()) apply();
});
</script>
