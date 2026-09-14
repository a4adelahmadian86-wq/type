(()=>{
'use strict';
const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();
const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const fmt=n=>new Intl.NumberFormat('fa-IR').format(n||0);
const icon=m=>m?.includes('pdf')?'fa-file-pdf':m?.includes('word')||m?.includes('doc')?'fa-file-word':m?.startsWith('image/')?'fa-file-image':m?.includes('zip')?'fa-file-zipper':'fa-file';
const ext=name=>String(name||'').split('.').pop()?.toUpperCase()||'FILE';
ready(()=>{
 let modal,list,search,input,drop,files=[],busy=false;
 const build=()=>{
  if(modal)return;
  modal=document.createElement('div');
  modal.id='farast-file-picker';
  modal.setAttribute('aria-hidden','true');
  modal.innerHTML=`<section class="farast-file-modal" role="dialog" aria-modal="true" aria-labelledby="farast-file-title">
    <header class="farast-file-head"><div class="farast-file-heading"><span class="farast-file-head-icon"><i class="fa-solid fa-folder-open"></i></span><div><div id="farast-file-title" class="farast-file-title">فایل‌های من</div><div class="farast-file-sub">انتخاب، بررسی و آماده‌سازی فایل برای تایپ</div></div></div><button type="button" class="farast-file-close" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></header>
    <div class="farast-file-toolbar"><div class="farast-file-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input class="farast-file-search" type="search" placeholder="جستجوی نام فایل..." aria-label="جستجوی فایل"></div><button type="button" class="farast-file-library"><i class="fa-solid fa-book-open"></i><span>کتابخانه</span></button></div>
    <div class="farast-file-drop" tabindex="0" role="button" aria-label="افزودن فایل"><input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,.doc,.docx"><div class="farast-drop-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div><div><strong>فایل را اینجا رها کنید</strong><small>یا برای انتخاب فایل از دستگاه کلیک کنید</small><em>PDF، Word، تصویر و ZIP</em></div></div>
    <div class="farast-file-summary"><span class="farast-file-count"></span><span>فایل‌های انتخاب‌شده فقط برای آماده‌سازی تایپ استفاده می‌شوند.</span></div>
    <div class="farast-file-list"></div>
    <footer class="farast-file-foot"><span><i class="fa-solid fa-shield-halved"></i> فایل‌های شما خصوصی نگه داشته می‌شوند.</span><span>۱۴ روز فضای داخلی · سپس آرشیو خارجی</span></footer>
  </section>`;
  document.body.appendChild(modal);
  list=modal.querySelector('.farast-file-list');search=modal.querySelector('.farast-file-search');input=modal.querySelector('input[type=file]');drop=modal.querySelector('.farast-file-drop');
  modal.querySelector('.farast-file-close').onclick=close;
  modal.addEventListener('click',e=>{if(e.target===modal)close()});
  search.oninput=render;
  input.onchange=e=>{const f=e.target.files?.[0];if(f)upload(f);input.value=''};
  drop.addEventListener('click',e=>{if(e.target!==input)input.click()});
  drop.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();input.click()}});
  ['dragenter','dragover'].forEach(ev=>drop.addEventListener(ev,e=>{e.preventDefault();e.stopPropagation();drop.classList.add('is-drag')}));
  ['dragleave','drop'].forEach(ev=>drop.addEventListener(ev,e=>{e.preventDefault();e.stopPropagation();drop.classList.remove('is-drag')}));
  drop.addEventListener('drop',e=>{const f=e.dataTransfer.files?.[0];if(f)upload(f)});
  modal.querySelector('.farast-file-library').onclick=()=>window.location.assign('/library');
 };
 const open=async()=>{build();modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('farast-file-picker-open');await load();setTimeout(()=>search?.focus(),40)};
 const close=()=>{if(!modal)return;modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('farast-file-picker-open')};
 const load=async()=>{try{const r=await fetch('/editor/files',{headers:{Accept:'application/json'},credentials:'same-origin'});const j=await r.json();if(!r.ok)throw new Error(j.message||'load');files=j.files||[];render()}catch(e){list.innerHTML='<div class="farast-file-empty error"><i class="fa-solid fa-triangle-exclamation"></i><strong>فهرست فایل‌ها بارگذاری نشد</strong><small>اتصال یا دسترسی فایل‌ها را بررسی کنید.</small></div>'}};
 const render=()=>{
  if(!list)return;
  const q=(search?.value||'').trim().toLowerCase();const rows=files.filter(f=>!q||String(f.name).toLowerCase().includes(q));
  const count=modal.querySelector('.farast-file-count');if(count)count.textContent=`${fmt(rows.length)} فایل`;
  if(!rows.length){list.innerHTML='<div class="farast-file-empty"><i class="fa-regular fa-folder-open"></i><strong>هنوز فایلی در پروژه ندارید</strong><small>فایل را در کادر بالا رها کنید یا از انتخاب فایل استفاده کنید.</small></div>';return}
  list.innerHTML=rows.map(f=>`<article class="farast-file-row" data-row="${f.id}"><div class="farast-file-icon"><i class="fa-solid ${icon(f.mime)}"></i><span>${esc(ext(f.name))}</span></div><div class="farast-file-name"><strong title="${esc(f.name)}">${esc(f.name)}</strong><small><i class="fa-solid fa-location-dot"></i> ${esc(f.status==='local'?'فضای داخلی':'آرشیو خارجی')} · ${f.local_expires_at?'نگهداری فعال':''}</small></div><div class="farast-file-stat"><b>${esc(f.size)}</b><span>حجم</span></div><div class="farast-file-stat"><b>${fmt(f.pages)}</b><span>صفحه</span></div><div class="farast-file-price"><span>برآورد تایپ</span><b>${esc(f.estimated_price)}</b></div><button class="farast-file-select" data-id="${f.id}"><i class="fa-solid fa-check"></i><span>انتخاب</span></button><button class="farast-file-remove" data-remove="${f.id}" title="حذف فایل"><i class="fa-regular fa-trash-can"></i></button></article>`).join('');
  list.querySelectorAll('[data-id]').forEach(b=>b.onclick=()=>select(Number(b.dataset.id),b));
  list.querySelectorAll('[data-remove]').forEach(b=>b.onclick=()=>remove(Number(b.dataset.remove)));
 };
 const upload=async(file)=>{
  if(busy)return;busy=true;renderBusy(`در حال بارگذاری «${file.name}»`);
  const fd=new FormData();fd.append('file',file);
  try{const r=await fetch('/editor/files',{method:'POST',body:fd,headers:{'X-CSRF-TOKEN':csrf(),Accept:'application/json'},credentials:'same-origin'});const j=await r.json();if(!r.ok)throw new Error(j.message||'upload');await load();toast('فایل با موفقیت به فایل‌های شما اضافه شد.')}catch(e){renderError(e.message||'بارگذاری فایل انجام نشد.')}finally{busy=false;}
 };
 const select=async(id,button)=>{
  if(busy)return;const f=files.find(x=>x.id===id);if(!f)return;busy=true;button?.classList.add('is-loading');button?.setAttribute('disabled','disabled');
  try{const r=await fetch('/editor/files/'+id+'/select',{method:'POST',headers:{'X-CSRF-TOKEN':csrf(),Accept:'application/json'},credentials:'same-origin'});const j=await r.json();if(!r.ok)throw new Error(j.message||'select');
    const quote=await fetch('/editor/preflight/estimate',{method:'POST',headers:{'X-CSRF-TOKEN':csrf(),'Content-Type':'application/json',Accept:'application/json'},body:'{}',credentials:'same-origin'});const q=await quote.json();if(!quote.ok)throw new Error(q.message||'برآورد فایل انجام نشد');
    close();showPreflight(q);
  }catch(e){button?.classList.remove('is-loading');button?.removeAttribute('disabled');renderError(e.message||'انتخاب فایل انجام نشد.')}finally{busy=false;}
 };
 const showPreflight=q=>{
  let box=document.getElementById('farast-file-preflight');if(box)box.remove();
  box=document.createElement('div');box.id='farast-file-preflight';box.innerHTML=`<section class="farast-preflight-card" role="dialog" aria-modal="true"><header><div><span>آماده‌سازی تایپ</span><strong>${esc(q.name||'فایل')}</strong></div><button type="button" data-close aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></header><div class="farast-preflight-grid"><div><span>تعداد صفحات</span><b>${fmt(q.pages)}</b></div><div><span>برآورد تایپ</span><b>${fmt(q.estimate_rials)} ریال</b></div><div><span>تخفیف</span><b>${fmt(q.discount_rials)} ریال</b></div><div><span>مبلغ قابل پرداخت</span><b>${fmt(q.payable_estimate_rials)} ریال</b></div></div><div class="farast-preflight-note"><i class="fa-solid fa-circle-info"></i><span>این مبلغ برآورد اولیه است و قبل از شروع پردازش به شما نمایش داده می‌شود.</span></div><footer><button type="button" data-decline>انصراف</button><button type="button" class="primary" data-accept><i class="fa-solid fa-wand-magic-sparkles"></i> تأیید و ادامه</button></footer></section>`;
  document.body.appendChild(box);box.querySelector('[data-close]').onclick=()=>box.remove();box.querySelector('[data-decline]').onclick=async()=>{box.remove();await fetch('/editor/preflight/decline',{method:'POST',headers:{'X-CSRF-TOKEN':csrf(),Accept:'application/json'}})};box.querySelector('[data-accept]').onclick=()=>acceptPreflight(box,q);
 };
 const acceptPreflight=async(box,q)=>{const b=box.querySelector('[data-accept]');b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> آماده‌سازی...';try{const r=await fetch('/editor/preflight/accept',{method:'POST',headers:{'X-CSRF-TOKEN':csrf(),'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({accept:true}),credentials:'same-origin'});const j=await r.json();if(!r.ok)throw new Error(j.message||'تأیید انجام نشد');if(j.action==='deposit'&&j.checkout_url){window.location.assign(j.checkout_url);return}box.remove();const analyze=document.getElementById('analyze');if(analyze){analyze.disabled=false;analyze.dataset.ready='1';analyze.dataset.path=q.path||'';analyze.dataset.mime=q.mime||'';analyze.dataset.name=q.name||'';analyze.click()}else{window.dispatchEvent(new CustomEvent('farast:file-ready',{detail:q}))}}catch(e){b.disabled=false;b.textContent='تلاش دوباره';toast(e.message)}};
 const renderBusy=t=>{list.innerHTML=`<div class="farast-file-empty loading"><span class="farast-loader"></span><strong>${esc(t)}</strong><small>در حال بررسی نوع، حجم و تعداد صفحات...</small></div>`};
 const renderError=t=>{list.innerHTML=`<div class="farast-file-empty error"><i class="fa-solid fa-triangle-exclamation"></i><strong>عملیات انجام نشد</strong><small>${esc(t)}</small><button type="button" data-retry>بازگشت به فایل‌ها</button></div>`;list.querySelector('[data-retry]')?.addEventListener('click',load)};
 const toast=text=>{let n=document.getElementById('farastEditorMessage');if(!n){n=document.createElement('div');n.id='farastEditorMessage';n.className='farast-inline-message';document.body.append(n)}n.textContent=text;clearTimeout(n._timer);n._timer=setTimeout(()=>n.remove(),2600)};
 document.addEventListener('click',e=>{const source=e.target.closest?.('#source,.ribbon-file');if(source){e.preventDefault();e.stopImmediatePropagation();open()}},{capture:true});
 document.addEventListener('click',e=>{const b=e.target.closest?.('.word-tool[data-action="fileTyping"], [data-action="fileTyping"], [data-editor-action="fileTyping"]');if(b){e.preventDefault();e.stopImmediatePropagation();open()}},{capture:true});
 let dragDepth=0;
 document.addEventListener('dragenter',e=>{if(!e.dataTransfer?.types?.includes('Files'))return;dragDepth++;build();modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('farast-file-picker-open');drop?.classList.add('is-drag')},{capture:true});
 document.addEventListener('dragover',e=>{if(e.dataTransfer?.types?.includes('Files'))e.preventDefault()},{capture:true});
 document.addEventListener('dragleave',e=>{if(!e.dataTransfer?.types?.includes('Files'))return;if(--dragDepth<=0){dragDepth=0;drop?.classList.remove('is-drag')}},{capture:true});
 document.addEventListener('drop',e=>{if(!e.dataTransfer?.files?.length)return;e.preventDefault();e.stopPropagation();dragDepth=0;build();modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('farast-file-picker-open');drop?.classList.remove('is-drag');upload(e.dataTransfer.files[0])},{capture:true});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal?.classList.contains('is-open'))close()});
 window.FarastFilePicker={open,close,refresh:()=>{build();load()}};
});
})();
