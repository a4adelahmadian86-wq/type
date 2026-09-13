(()=>{
'use strict';
const boot=()=>{
 const app=document.getElementById('farastWord'); if(!app||app.dataset.wordExtended==='1') return; app.dataset.wordExtended='1';
 const tabsHost=app.querySelector('.word-tabs'); const home=app.querySelector('#ribbon-home'); const editor=document.getElementById('editor');
 if(!tabsHost||!home||!editor) return;
 const make=(tag,attrs={},html='')=>{const e=document.createElement(tag);Object.entries(attrs).forEach(([k,v])=>e.setAttribute(k,v));e.innerHTML=html;return e;};
 const tab=make('button',{type:'button',class:'word-tab','data-tab':'file','aria-selected':'false'},'فایل');
 tabsHost.insertBefore(tab,tabsHost.firstChild);
 const panel=make('section',{id:'ribbon-file',class:'ribbon hidden','aria-hidden':'true','data-ribbon':'file'});
 panel.innerHTML=`
  <div class="ribbon-group"><button type="button" data-action="new"><i class="fa-regular fa-file"></i><span>سند جدید</span></button><button type="button" data-action="open"><i class="fa-solid fa-folder-open"></i><span>باز کردن</span></button></div>
  <div class="ribbon-group"><button type="button" data-action="save"><i class="fa-solid fa-floppy-disk"></i><span>ذخیره</span></button><button type="button" data-action="print"><i class="fa-solid fa-print"></i><span>چاپ</span></button></div>
  <div class="ribbon-group"><button type="button" data-action="docx"><i class="fa-solid fa-file-word"></i><span>خروجی Word</span></button><button type="button" data-action="pdf"><i class="fa-solid fa-file-pdf"></i><span>خروجی PDF</span></button></div>
  <div class="ribbon-group"><button type="button" data-action="share"><i class="fa-solid fa-share-nodes"></i><span>اشتراک‌گذاری</span></button><button type="button" data-action="info"><i class="fa-solid fa-circle-info"></i><span>اطلاعات سند</span></button></div>`;
 const ribbonHost=home.parentElement; ribbonHost.appendChild(panel);
 panel.querySelectorAll('button').forEach(b=>b.dataset.tip=b.textContent.trim());
 tab.addEventListener('click',()=>{app.dataset.activeRibbon='file'; app.querySelectorAll('.word-tab').forEach(t=>{const on=t===tab;t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false');});app.querySelectorAll('.ribbon[id^="ribbon-"]').forEach(p=>{const on=p===panel;p.classList.toggle('hidden',!on);p.hidden=!on;p.setAttribute('aria-hidden',on?'false':'true');});});
 const action=(a)=>{
   if(a==='new'){ if(confirm('محتوای فعلی پاک و سند جدید ساخته شود؟')){editor.innerHTML='';editor.dispatchEvent(new Event('input',{bubbles:true}));} }
   else if(a==='open') app.querySelector('input[type="file"]')?.click();
   else if(a==='save') document.getElementById('saveBtn')?.click();
   else if(a==='print') window.print();
   else if(a==='docx') document.querySelector('[data-export="docx"],#exportDocx')?.click();
   else if(a==='pdf') document.querySelector('[data-export="pdf"],#exportPdf')?.click();
   else if(a==='share') navigator.share?.({title:document.title,url:location.href}).catch(()=>{});
   else if(a==='info') alert('سند در ویرایشگر فراست؛ ذخیره خودکار و کنترل پرداخت/خروجی در سمت سرویس انجام می‌شود.');
 };
 panel.addEventListener('click',e=>{const b=e.target.closest('button[data-action]');if(b){e.preventDefault();action(b.dataset.action);}});
 const observer=new MutationObserver(()=>{const name=app.dataset.activeRibbon||'home';panel.classList.toggle('hidden',name!=='file');panel.hidden=name!=='file';panel.setAttribute('aria-hidden',name!=='file');}); observer.observe(app,{attributes:true,attributeFilter:['data-active-ribbon']});
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
