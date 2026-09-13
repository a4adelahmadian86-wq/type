(()=>{
'use strict';
const make=(tag,attrs={},html='')=>{const e=document.createElement(tag);Object.entries(attrs).forEach(([k,v])=>e.setAttribute(k,v));e.innerHTML=html;return e;};
const boot=()=>{
 const app=document.getElementById('farastWord'); if(!app||app.dataset.wordExtended==='1') return; app.dataset.wordExtended='1';
 const tabsHost=app.querySelector('.word-tabs'); const home=app.querySelector('#ribbon-home'); const editor=document.getElementById('editor');
 if(!tabsHost||!home||!editor) return;
 const style=document.createElement('style');
 style.textContent=`
 .word-file-backstage{position:absolute;inset:0;z-index:80;background:#f7f9fc;display:none;overflow:auto;direction:rtl;padding:28px;}
 .word-file-backstage.is-open{display:block;}
 .word-file-grid{max-width:980px;margin:0 auto;display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:14px;}
 .word-file-card{display:flex;flex-direction:column;align-items:flex-start;gap:10px;min-height:120px;padding:18px;border:1px solid #d9e1eb;border-radius:12px;background:#fff;box-shadow:0 6px 22px rgba(13,29,53,.06);color:#263b55;cursor:pointer;font:inherit;text-align:right;}
 .word-file-card:hover{border-color:#9db9e8;box-shadow:0 10px 30px rgba(23,105,255,.10);}
 .word-file-card i{font-size:1.35rem;color:#1769ff}.word-file-card strong{font-size:.82rem}.word-file-card small{font-size:.64rem;color:#75859a;line-height:1.7}
 .word-file-head{max-width:980px;margin:0 auto 22px;display:flex;align-items:center;justify-content:space-between;gap:12px}.word-file-head strong{font-size:1.05rem}.word-file-close{border:1px solid #d9e1eb;background:#fff;border-radius:8px;padding:7px 11px;cursor:pointer}
 @media(max-width:760px){.word-file-backstage{padding:14px}.word-file-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.word-file-card{min-height:105px;padding:12px}}
 `;
 document.head.appendChild(style);
 const tab=make('button',{type:'button',class:'word-tab','data-tab':'file','aria-selected':'false'},'فایل');
 tabsHost.insertBefore(tab,tabsHost.firstChild);
 const panel=make('section',{id:'ribbon-file',class:'word-file-backstage','aria-hidden':'true','data-ribbon':'file'});
 panel.innerHTML=`<div class="word-file-head"><strong>فایل</strong><button type="button" class="word-file-close" data-action="close">بازگشت به سند</button></div><div class="word-file-grid">
  <button class="word-file-card" type="button" data-action="new"><i class="fa-regular fa-file"></i><strong>سند جدید</strong><small>یک سند خالی تازه ایجاد کن</small></button>
  <button class="word-file-card" type="button" data-action="open"><i class="fa-solid fa-folder-open"></i><strong>باز کردن</strong><small>فایل ورودی را انتخاب کن</small></button>
  <button class="word-file-card" type="button" data-action="save"><i class="fa-solid fa-floppy-disk"></i><strong>ذخیره</strong><small>نسخه فعلی را ذخیره کن</small></button>
  <button class="word-file-card" type="button" data-action="print"><i class="fa-solid fa-print"></i><strong>چاپ</strong><small>پیش‌نمایش و چاپ سند</small></button>
  <button class="word-file-card" type="button" data-action="docx"><i class="fa-solid fa-file-word"></i><strong>خروجی Word</strong><small>عبور از دروازه پرداخت و خروجی</small></button>
  <button class="word-file-card" type="button" data-action="pdf"><i class="fa-solid fa-file-pdf"></i><strong>خروجی PDF</strong><small>خروجی PDF پس از مجوز</small></button>
  <button class="word-file-card" type="button" data-action="share"><i class="fa-solid fa-share-nodes"></i><strong>اشتراک‌گذاری</strong><small>اشتراک‌گذاری در دستگاه‌های پشتیبان‌شده</small></button>
  <button class="word-file-card" type="button" data-action="info"><i class="fa-solid fa-circle-info"></i><strong>اطلاعات سند</strong><small>وضعیت، ذخیره و وضعیت خروجی</small></button>
 </div>`;
 home.parentElement.appendChild(panel);
 const closeFile=()=>{
   panel.classList.remove('is-open');panel.setAttribute('aria-hidden','true');
   app.querySelectorAll('.word-tab').forEach(t=>{const on=t===tabsHost.querySelector('.word-tab[data-tab="home"]');t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false');});
   app.querySelectorAll('.ribbon[id^="ribbon-"]').forEach(p=>{p.classList.remove('hidden');p.hidden=false;p.setAttribute('aria-hidden','false');});
   app.querySelectorAll('.ribbon[id^="ribbon-"]:not(#ribbon-home)').forEach(p=>{p.classList.add('hidden');p.hidden=true;p.setAttribute('aria-hidden','true');});
   app.dataset.activeRibbon='home';
 };
 tab.addEventListener('click',()=>{
   app.dataset.activeRibbon='file';
   app.querySelectorAll('.word-tab').forEach(t=>{const on=t===tab;t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false');});
   app.querySelectorAll('.ribbon[id^="ribbon-"]').forEach(p=>{p.classList.add('hidden');p.hidden=true;p.setAttribute('aria-hidden','true');});
   panel.classList.add('is-open');panel.setAttribute('aria-hidden','false');
 });
 const action=(a)=>{
   if(a==='close'){closeFile();return;}
   if(a==='new'){if(confirm('محتوای فعلی پاک و سند جدید ساخته شود؟')){editor.innerHTML='';editor.dispatchEvent(new Event('input',{bubbles:true));}}
   else if(a==='open') app.querySelector('input[type="file"]')?.click();
   else if(a==='save') document.getElementById('saveBtn')?.click();
   else if(a==='print') window.print();
   else if(a==='docx') document.querySelector('[data-export="docx"],#exportDocx')?.click();
   else if(a==='pdf') document.querySelector('[data-export="pdf"],#exportPdf')?.click();
   else if(a==='share') navigator.share?.({title:document.title,url:location.href}).catch(()=>{});
   else if(a==='info') alert('سند در ویرایشگر فراست؛ ذخیره خودکار و کنترل پرداخت/خروجی در سمت سرویس انجام می‌شود.');
 };
 panel.addEventListener('click',e=>{const b=e.target.closest('[data-action]');if(b){e.preventDefault();action(b.dataset.action);}});
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
