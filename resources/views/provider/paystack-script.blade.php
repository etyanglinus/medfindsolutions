<script>
document.addEventListener('DOMContentLoaded', function () {
    const form=document.querySelector('.mf-paystack-form');
    if(!form)return;
    function plan(){return document.querySelector('input.plan-radio:checked, input[name="__plan_ui"]:checked, input[name="package"]:checked')?.value || form.querySelector('select[name="package"]')?.value || form.elements.package?.value;}
    function coupon(){return document.getElementById('checkout-coupon-code')?.value || document.getElementById('coupon-input')?.value || '';}
    document.addEventListener('change',function(event){if(event.target.matches('input[type="radio"],select[name="package"]')){document.querySelectorAll('.plan-card').forEach(card=>card.classList.toggle('selected',card.id==='card_'+plan()));const result=document.getElementById('checkout-quote-result');if(result)result.textContent='';}});
    form.addEventListener('submit', function(){const hidden=form.querySelector('input[type="hidden"][name="package"]');if(hidden)hidden.value=plan();const code=form.querySelector('input[name="coupon_code"]');if(code)code.value=coupon().trim();form.querySelector('button[type="submit"]').disabled=true;});
    const button=form.querySelector('[data-checkout-coupon]') || document.getElementById('retired-coupon-preview');
    button?.addEventListener('click',async function(){const result=document.getElementById('checkout-quote-result');button.disabled=true;try{const response=await fetch(@json(url('/subscription'))+'/'+form.dataset.providerType+'/quote',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({package:plan(),coupon_code:coupon().trim(),token:form.dataset.registrationToken||null})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat()[0]||'Unable to quote this plan.');const quote=data.quote||data;result.textContent='KES '+Number(quote.price).toLocaleString(undefined,{minimumFractionDigits:2})+' — '+@json(__('confirmed amount before Paystack checkout'));}catch(error){result.textContent=error.message;}finally{button.disabled=false;}});
});
</script>
