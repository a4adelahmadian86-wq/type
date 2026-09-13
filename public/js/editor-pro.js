(()=>{
'use strict';
const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const editor=$('#editor');
if(!editor)return;
const csrf=$('meta[name="csrf-token"]')?.content||'';
let bypass=false;

function fireInput(){editor.dispatchEvent(new Event('input',{bubbles:true}))}
function cmd(name,value=null){editor.focus();document.execCommand(name,false,value);fireInput()}
function api(url,body={}){return fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(body)}).then(async r=>{let j={};try{j=await r.json()}catch{}if(!r.ok){const e=new Error(j.message||'خطا در ارتباط با سرور');e.status=r.status;throw e}return j})}
function money(rials){return new Intl.NumberFormat('fa-IR').format(Math.round(Number(rials||0)/10))+' تومان'}

function labelExistingGroups(){
 const map=[['.ribbon-group.history','تاریخچه'],['.ribbon-group.clipboard','کلیپ‌بورد'],['.ribbon-group.font-group','قلم'],['.ribbon-group.paragraph-group','پاراگراف'],['.ribbon-group.style-group','سبک‌ها'],['.ribbon-group.tools-group','ابزارها']];
 map.forEach(([s,n])=>$(s)?.setAttribute('data-group',n));
 $$('#ribbon-insert .ribbon-group').forEach((g,i)=>g.setAttribute('data-group',i===0?'صفحات و عناصر':'پیوندها'));
 $$('#ribbon-layout .ribbon-group').forEach(g=>g.setAttribute('data-group','تنظیم صفحه'));
 $$('#ribbon-review .ribbon-group').forEach((g,i)=>g.setAttribute('data-group',i===0?'بازبینی':'راهنما'));
 $$('#ribbon-ai .ribbon-group').forEach((g,i)=>g.setAttribute('data-group',i===0?'وضعیت هوش مصنوعی':i===1?'شناسه‌ها':'بازخورد'));
}

function addHomeTools(){
 const font=$('.font-group'),para=$('.paragraph-group'),tools=$('.tools-group');
 if(font&&!$('#farastFontColor')){
   font.insertAdjacentHTML('beforeend',`<span class="ribbon-sep"></span><button type="button" data-pro-cmd="superscript" title="بالانویس"><i class="fa-solid fa-superscript"></i></button><button type="button" data-pro-cmd="subscript" title="پایین‌نویس"><i class="fa-solid fa-subscript"></i></button><label class="ribbon-label" title="رنگ قلم"><i class="fa-solid fa-font"></i><input id="farastFontColor" type="color" value="#111111" aria-label="رنگ قلم"></label><label class="ribbon-label" title="رنگ برجسته‌سازی"><i class="fa-solid fa-highlighter"></i><input id="farastHighlightColor" type="color" value="#fff59d" aria-label="رنگ برجسته‌سازی"></label>`);
 }
 if(para&&!$('#farastIndent'))para.insertAdjacentHTML('beforeend',`<span class="ribbon-sep"></span><button id="farastIndent" type="button" title="افزایش تورفتگی"><i class="fa-solid fa-indent"></i></button><button id="farastOutdent" type="button" title="کاهش تورفتگی"><i class="fa-solid fa-outdent"></i></button>`);
 if(tools&&!$('#farastSelectAll'))tools.insertAdjacentHTML('afterbegin',`<button id="farastSelectAll" type="button" title="انتخاب همه"><i class="fa-solid fa-object-group"></i> انتخاب همه</button>`);
}

function addTabsAndRibbons(){
 const tabs=$('.word-tabs');if(!tabs)return;
 const aiTab=tabs.querySelector('[data-tab="ai"]');
 const tabsHtml=`<button class="word-tab" data-tab="design">طراحی</button><button class="word-tab" data-tab="references">مراجع</button><button class="word-tab" data-tab="view">نمایش</button>`;
 if(aiTab)aiTab.insertAdjacentHTML('beforebegin',tabsHtml);else tabs.insertAdjacentHTML('beforeend',tabsHtml);
 const aiRibbon=$('#ribbon-ai');if(!aiRibbon)return;
 aiRibbon.insertAdjacentHTML('beforebegin',`
 <section class="ribbon ribbon-secondary hidden" id="ribbon-design">
   <div class="ribbon-group" data-group="پس‌زمینه صفحه"><label class="ribbon-label"><i class="fa-solid fa-fill-drip"></i><input id="pageColor" type="color" value="#ffffff" aria-label="رنگ صفحه"></label><button id="clearPageColor" type="button"><i class="fa-solid fa-eraser"></i> بدون رنگ</button></div>
   <div class="ribbon-group" data-group="کادر صفحه"><button data-border="none">بدون کادر</button><button data-border="simple"><i class="fa-regular fa-square"></i> ساده</button><button data-border="double"><i class="fa-regular fa-clone"></i> دوخط</button></div>
 </section>
 <section class="ribbon ribbon-secondary hidden" id="ribbon-references">
   <div class="ribbon-group" data-group="فهرست"><button id="insertToc" class="ribbon-large" type="button"><i class="fa-solid fa-list-ol"></i><span>فهرست مطالب</span></button></div>
   <div class="ribbon-group" data-group="پاورقی"><button id="insertFootnote" class="ribbon-large" type="button"><i class="fa-solid fa-note-sticky"></i><span>درج پاورقی</span></button></div>
 </section>
 <section class="ribbon ribbon-secondary hidden" id="ribbon-view">
   <div class="ribbon-group" data-group="بزرگ‌نمایی"><button data-zoom=".5">۵۰٪</button><button data-zoom=".75">۷۵٪</button><button data-zoom="1">۱۰۰٪</button><button data-zoom="1.25">۱۲۵٪</button><button data-zoom="1.5">۱۵۰٪</button></div>
   <div class="ribbon-group" data-group="نمایش"><button id="toggleGuides" type="button"><i class="fa-solid fa-grip-lines"></i> خطوط راهنما</button><button id="toggleFullscreen" type="button"><i class="fa-solid fa-expand"></i> تمام‌صفحه</button></div>
 </section>`);
}

function expandInsertAndLayout(){
 const insertGroups=$$('#ribbon-insert .ribbon-group');
 if(insertGroups[0]&&!$('#insertTable'))insertGroups[0].insertAdjacentHTML('beforeend',`<button id="insertTable" type="button"><i class="fa-solid fa-table-cells"></i> جدول</button><button id="insertSymbol" type="button"><i class="fa-solid fa-omega"></i> نماد</button><button id="insertTime" type="button"><i class="fa-regular fa-clock"></i> ساعت</button>`);
 const layout=$('#ribbon-layout .ribbon-group');
 if(layout&&!$('#orientation'))layout.insertAdjacentHTML('beforeend',`<label>جهت <select id="orientation"><option value="portrait">عمودی</option><option value="landscape">افقی</option></select></label><label>ستون <select id="columns"><option value="1">یک</option><option value="2">دو</option><option value="3">سه</option></select></label>`);
 const review=$('#ribbon-review .ribbon-group');
 if(review&&!$('#wordStats'))review.insertAdjacentHTML('beforeend',`<button id="wordStats" type="button"><i class="fa-solid fa-chart-simple"></i> آمار سند</button>`);
}

function bindTabs(){
 $$('.word-tab').forEach(tab=>tab.addEventListener('click',()=>{
   $$('.word-tab').forEach(x=>x.classList.remove('active'));tab.classList.add('active');
   $$('.ribbon').forEach((r,i)=>{if(i>0)r.classList.add('hidden')});
   if(tab.dataset.tab==='home')$('#ribbon-home')?.classList.remove('hidden');else $('#ribbon-'+tab.dataset.tab)?.classList.remove('hidden');
 }));
}

function bindTools(){
 $$('[data-pro-cmd]').forEach(b=>b.addEventListener('click',()=>cmd(b.dataset.proCmd)));
 $('#farastFontColor')?.addEventListener('input',e=>cmd('foreColor',e.target.value));
 $('#farastHighlightColor')?.addEventListener('input',e=>cmd('hiliteColor',e.target.value));
 $('#farastIndent')?.addEventListener('click',()=>cmd('indent'));
 $('#farastOutdent')?.addEventListener('click',()=>cmd('outdent'));
 $('#farastSelectAll')?.addEventListener('click',()=>cmd('selectAll'));
 $('#insertTable')?.addEventListener('click',()=>{
   const rows=Math.max(1,Math.min(12,Number(prompt('تعداد ردیف‌ها','3')||0))),cols=Math.max(1,Math.min(8,Number(prompt('تعداد ستون‌ها','3')||0)));if(!rows||!cols)return;
   let h='<table><tbody>';for(let r=0;r<rows;r++){h+='<tr>';for(let c=0;c<cols;c++)h+='<td><br></td>';h+='</tr>'}h+='</tbody></table><p><br></p>';cmd('insertHTML',h);
 });
 $('#insertSymbol')?.addEventListener('click',()=>{const s=prompt('نماد را وارد کنید','©');if(s)cmd('insertText',s)});
 $('#insertTime')?.addEventListener('click',()=>cmd('insertText',new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short'}).format(new Date())));
 const page=()=>$('.word-page');
 $('#orientation')?.addEventListener('change',e=>{page()?.classList.toggle('page-landscape',e.target.value==='landscape');fireInput()});
 $('#columns')?.addEventListener('change',e=>{editor.classList.remove('columns-2','columns-3');if(e.target.value==='2')editor.classList.add('columns-2');if(e.target.value==='3')editor.classList.add('columns-3');fireInput()});
 $('#margin')?.addEventListener('change',e=>{const p=page();if(!p)return;p.classList.remove('margin-narrow','margin-wide');if(e.target.value==='narrow')p.classList.add('margin-narrow');if(e.target.value==='wide')p.classList.add('margin-wide');fireInput()});
 $('#paperSize')?.addEventListener('change',e=>{page()?.classList.toggle('page-a5',String(e.target.value).trim().toUpperCase()==='A5');fireInput()});
 $('#pageColor')?.addEventListener('input',e=>{if(page())page().style.backgroundColor=e.target.value;fireInput()});
 $('#clearPageColor')?.addEventListener('click',()=>{if(page())page().style.backgroundColor='';fireInput()});
 $$('[data-border]').forEach(b=>b.addEventListener('click',()=>{const p=page();if(!p)return;p.classList.remove('page-border-simple','page-border-double');if(b.dataset.border==='simple')p.classList.add('page-border-simple');if(b.dataset.border==='double')p.classList.add('page-border-double');fireInput()}));
 $('#insertToc')?.addEventListener('click',()=>{const headings=[...editor.querySelectorAll('h1,h2,h3')];if(!headings.length){alert('برای ساخت فهرست، ابتدا از سبک‌های عنوان ۱ تا ۳ استفاده کنید.');return}const items=headings.map((h,i)=>`<li data-level="${h.tagName.slice(1)}">${String(h.textContent||'').replace(/[&<>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]))}</li>`).join('');cmd('insertHTML',`<div class="farast-toc"><strong>فهرست مطالب</strong><ol>${items}</ol></div><p><br></p>`)});
 $('#insertFootnote')?.addEventListener('click',()=>{const text=prompt('متن پاورقی');if(!text)return;const n=editor.querySelectorAll('.farast-footnote').length+1;cmd('insertHTML',`<sup>${n}</sup><span class="farast-footnote"> [${n}] ${String(text).replace(/[&<>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]))}</span>`)});
 $$('[data-zoom]').forEach(b=>b.addEventListener('click',()=>{const z=Number(b.dataset.zoom)||1;const p=page();if(p)p.style.transform=`scale(${z})`;if(p)p.style.transformOrigin='top center';const zv=$('#zoomValue');if(zv)zv.textContent=Math.round(z*100)+'%'}));
 $('#toggleGuides')?.addEventListener('click',()=>editor.classList.toggle('show-guides'));
 $('#toggleFullscreen')?.addEventListener('click',async()=>{try{if(!document.fullscreenElement)await $('.word-app')?.requestFullscreen();else await document.exitFullscreen()}catch{}});
 $('#wordStats')?.addEventListener('click',()=>{const text=(editor.innerText||'').trim(),words=text?text.split(/\s+/u).filter(Boolean).length:0,chars=text.length,paras=[...editor.querySelectorAll('p,h1,h2,h3,li,blockquote')].filter(x=>x.innerText.trim()).length;alert(`کلمات: ${new Intl.NumberFormat('fa-IR').format(words)}\nنویسه‌ها: ${new Intl.NumberFormat('fa-IR').format(chars)}\nپاراگراف‌ها: ${new Intl.NumberFormat('fa-IR').format(paras)}`)});
}

function modal(){
 let m=$('#editorPreflightModal');if(m)return m;
 document.body.insertAdjacentHTML('beforeend',`<div class="editor-preflight-modal" id="editorPreflightModal" hidden><div class="editor-preflight-card" role="dialog" aria-modal="true"><button type="button" class="editor-preflight-close" data-preflight-close><i class="fa-solid fa-xmark"></i></button><h3>برآورد پیش از شروع تایپ</h3><p id="editorPreflightLead"></p><div id="editorPreflightLines" class="editor-preflight-lines"></div><div class="editor-preflight-actions" id="editorPreflightActions"><button type="button" class="editor-preflight-accept" id="editorPreflightAccept">تأیید و ادامه</button><button type="button" class="editor-preflight-decline" id="editorPreflightDecline">فعلاً ادامه نمی‌دهم</button></div><div class="editor-preflight-message" id="editorPreflightMessage" hidden></div></div></div>`);
 m=$('#editorPreflightModal');m.querySelector('[data-preflight-close]')?.addEventListener('click',()=>m.hidden=true);m.addEventListener('click',e=>{if(e.target===m)m.hidden=true});return m;
}
function showQuote(q){const m=modal(),lead=$('#editorPreflightLead'),lines=$('#editorPreflightLines'),actions=$('#editorPreflightActions'),message=$('#editorPreflightMessage'),acc=$('#editorPreflightAccept');message.hidden=true;actions.hidden=false;lead.textContent=`حدود ${new Intl.NumberFormat('fa-IR').format(q.pages)} صفحه شناسایی شد.`;let h=`<div class="editor-preflight-line"><span>برآورد اولیه</span><strong>${money(q.estimate_rials)}</strong></div>`;if(q.discount_rials>0)h+=`<div class="editor-preflight-line discount"><span>اعتبار هفتگی فراست</span><strong>− ${money(q.discount_rials)}</strong></div>`;h+=`<div class="editor-preflight-line"><span>برآورد پس از اعتبار</span><strong>${money(q.payable_estimate_rials)}</strong></div>`;if(q.deposit_rials>0)h+=`<div class="editor-preflight-line deposit"><span>مبلغ لازم برای شروع سفارش</span><strong>${money(q.deposit_rials)}</strong></div>`;lines.innerHTML=h;acc.textContent=q.deposit_rials>0?'پرداخت و ادامه':'تأیید و ادامه';m.hidden=false}
async function proceedAnalyze(){bypass=true;$('#analyze')?.click()}
async function runPreflight(){try{const q=await api('/editor/preflight/estimate');if(q.mode==='accepted')return proceedAnalyze();if(q.mode==='free'){const a=await api('/editor/preflight/accept',{accept:true});if(a.action==='editor')return proceedAnalyze();if(a.checkout_url)location.href=a.checkout_url;return}showQuote(q)}catch(e){if(e.status===401){location.href='/login?continue='+encodeURIComponent(location.pathname+location.search);return}alert(e.message)}}
function bindPreflight(){
 const analyze=$('#analyze');if(!analyze)return;
 analyze.addEventListener('click',e=>{if(bypass){bypass=false;return}e.preventDefault();e.stopImmediatePropagation();runPreflight()},{capture:true});
 document.addEventListener('click',async e=>{
   if(e.target.closest('#editorPreflightAccept')){const b=$('#editorPreflightAccept');b.disabled=true;try{const a=await api('/editor/preflight/accept',{accept:true});if(a.action==='deposit')location.href=a.checkout_url;else{modal().hidden=true;proceedAnalyze()}}catch(err){alert(err.message)}finally{b.disabled=false}}
   if(e.target.closest('#editorPreflightDecline')){const b=$('#editorPreflightDecline');b.disabled=true;try{const r=await api('/editor/preflight/decline');$('#editorPreflightActions').hidden=true;$('#editorPreflightLines').innerHTML='';$('#editorPreflightLead').textContent='درخواست در همین مرحله متوقف شد.';const m=$('#editorPreflightMessage');m.textContent=r.message;m.hidden=false}catch(err){alert(err.message)}finally{b.disabled=false}}
 });
}

labelExistingGroups();addHomeTools();addTabsAndRibbons();expandInsertAndLayout();bindTabs();bindTools();bindPreflight();
})();
