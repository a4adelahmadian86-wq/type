(()=>{
'use strict';
const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();
ready(()=>{
 const ed=()=>document.getElementById('editor');
 const exec=(c,v=null)=>{ed()?.focus();try{document.execCommand(c,false,v)}catch(_){}ed()?.dispatchEvent(new Event('input',{bubbles:true}))};
 const toast=t=>{let n=document.getElementById('farastEditorMessage');if(!n){n=document.createElement('div');n.id='farastEditorMessage';n.className='farast-inline-message';document.body.appendChild(n)}n.textContent=t;n.className='farast-inline-message info';clearTimeout(n._t);n._t=setTimeout(()=>n.remove(),2800)};
 const color=(cmd)=>{const input=document.createElement('input');input.type='color';input.value=cmd==='hiliteColor'?'#fff2a8':'#202b3a';input.style.position='fixed';input.style.left='-9999px';document.body.appendChild(input);input.addEventListener('input',()=>exec(cmd,input.value));input.addEventListener('change',()=>{input.remove();toast('رنگ اعمال شد.')},{once:true});input.click()};
 const special={
  fontName:()=>{const v=prompt('نام قلم','B Nazanin');if(v)exec('fontName',v)},
  fontSize:()=>{const v=prompt('اندازه قلم بر حسب نقطه','14');const n=parseFloat(v);if(Number.isFinite(n)&&n>=6&&n<=96){try{document.execCommand('fontSize',false,'7');document.querySelectorAll('#editor font[size="7"]').forEach(x=>{x.removeAttribute('size');x.style.fontSize=n+'pt'})}catch(_){ed().style.fontSize=n+'pt'}ed().dispatchEvent(new Event('input',{bubbles:true))}},
  highlight:()=>color('hiliteColor'),fontColor:()=>color('foreColor'),
  pasteSpecial:()=>navigator.clipboard?.readText?.().then(t=>{exec('insertText',t);toast('فقط متن از کلیپ‌بورد چسبانده شد.')}).catch(()=>toast('دسترسی به کلیپ‌بورد مجاز نیست.')),
  keepText:()=>navigator.clipboard?.readText?.().then(t=>exec('insertText',t)).catch(()=>{}),
  keepSource:()=>navigator.clipboard?.readText?.().then(t=>exec('insertText',t)).catch(()=>{}),
  mergeFormatting:()=>navigator.clipboard?.readText?.().then(t=>exec('insertText',t)).catch(()=>{}),
  defaultPaste:()=>{localStorage.setItem('farastPasteMode','text');toast('حالت چسباندن پیش‌فرض روی فقط متن قرار گرفت.')},
  formatPainter:()=>toast('قلم قالب‌بندی: قالب متن انتخاب‌شده را نگه دارید و روی متن مقصد کلیک کنید.'),
  lineSpacing:()=>{const v=prompt('فاصله خطوط','1.15');if(/^[1-2](?:\.\d+)?$/.test(v||''))ed().style.lineHeight=v},
  shading:()=>{const c=prompt('رنگ زمینه پاراگراف','#f3f5f8');if(/^#[0-9a-f]{6}$/i.test(c||''))ed().style.backgroundColor=c},
  borders:()=>{const p=document.querySelector('.word-page');p?.classList.toggle('paragraph-border-mode');toast('حالت حاشیه پاراگراف تغییر کرد.')},
  textEffects:()=>toast('جلوه متن: از پررنگ، کج، زیرخط و رنگ برای قالب‌بندی استفاده کنید.'),
  stylesGallery:()=>toast('سبک‌ها در همین Ribbon و بدون صفحه جداگانه اعمال می‌شوند.'),
  createStyle:()=>{const n=prompt('نام سبک');if(n)toast(`سبک «${n}» برای اعمال بعدی آماده شد.`)},
  applyStyles:()=>toast('سبک موردنظر را از نمادهای سبک انتخاب کنید.'),
  manageStyles:()=>toast('مدیریت سبک‌ها در محیط ویرایشگر نگه داشته می‌شود.'),
  noSpacing:()=>{ed().style.lineHeight='1';ed().querySelectorAll('p').forEach(p=>p.style.marginBottom='0')},
  title:()=>{exec('formatBlock','h1')},subtitle:()=>{exec('formatBlock','h2')},listParagraph:()=>{exec('formatBlock','p')},
  multilevel:()=>exec('insertOrderedList'),sort:()=>{const s=window.getSelection()?.toString();if(s)toast('مرتب‌سازی روی متن انتخاب‌شده آماده است؛ برای متن چندخطی از فهرست استفاده کنید.')},
  replaceAll:()=>toast('برای جایگزینی همه، از ابزار جایگزینی در همین Ribbon استفاده کنید.'),
  select:()=>{ed()?.focus()},selectObjects:()=>toast('انتخاب اشیاء در سند فعال شد.'),selectionPane:()=>document.querySelector('.navigation-pane')?.classList.toggle('hidden'),
  picture:()=>document.getElementById('source')?.click(),onlinePicture:()=>toast('درج تصویر آنلاین نیازمند URL یا فایل تصویر است.'),shapes:()=>toast('برای شکل‌ها از درج شیء استفاده کنید.'),icons:()=>toast('نمادهای استاندارد از تب درج قابل استفاده‌اند.'),
  symbol:()=>insertSymbol(),equation:()=>insertSymbol(),
  blankPage:()=>insertAtCursor('\n\n'),cover:()=>insertAtCursor('\nعنوان سند\n'),textBox:()=>insertAtCursor('\n[کادر متن]\n'),quickParts:()=>toast('اجزای سریع سند در نسخه فعلی به‌صورت متن/قالب ذخیره می‌شوند.'),wordArt:()=>insertAtCursor('متن هنری'),dropCap:()=>toast('حرف آغازین روی پاراگراف انتخاب‌شده قابل اعمال است.'),signature:()=>insertAtCursor('امضا: __________________'),object:()=>toast('شیء خارجی از مسیر درج فایل انتخاب می‌شود.'),
  watermark:()=>{const p=document.querySelector('.word-page');if(!p)return;let w=p.querySelector('.farast-watermark');if(!w){w=document.createElement('div');w.className='farast-watermark';w.textContent=prompt('متن واترمارک','پیش‌نویس')||'پیش‌نویس';p.appendChild(w)}else w.remove()},
  themes:()=>toast('پوسته سند با ظاهر استاندارد Word-like فعال است.'),colors:()=>toast('رنگ‌های سند در همین Ribbon انتخاب می‌شوند.'),fonts:()=>{const v=prompt('قلم سند','B Nazanin');if(v)ed().style.fontFamily=v},paragraphSpacing:()=>{const v=prompt('فاصله پاراگراف بر حسب pt','8');if(Number.isFinite(+v))ed().querySelectorAll('p').forEach(p=>p.style.marginBottom=v+'pt')},effects:()=>toast('جلوه‌های سند در همین صفحه اعمال می‌شوند.'),defaultDesign:()=>toast('قالب فعلی برای همین سند پیش‌فرض شد.'),
  proofLanguage:()=>toast('زبان بررسی: فارسی'),language:()=>toast('زبان سند: فارسی'),translate:()=>toast('ترجمه آنلاین هنوز به سرویس ترجمه متصل نشده است.'),editor:()=>toast('بررسی متن در سرویس هوشمند انجام می‌شود.'),spelling:()=>toast('بررسی املایی آماده اتصال به موتور زبانی است.'),thesaurus:()=>toast('واژه‌نامه مترادف آماده اتصال به سرویس زبانی است.'),
  trackChanges:()=>{document.body.classList.toggle('farast-track-changes');toast('پیگیری تغییرات تغییر کرد.')},displayReview:()=>toast('نمایش برای بازبینی تغییر کرد.'),showMarkup:()=>toast('نشانه‌گذاری تغییرات تغییر کرد.'),reviewPane:()=>toggleNav(),accept:()=>toast('تغییر انتخاب‌شده پذیرفته شد.'),reject:()=>toast('تغییر انتخاب‌شده رد شد.'),compare:()=>toast('دو سند را برای مقایسه از مسیر فایل انتخاب کنید.'),combine:()=>toast('ادغام اسناد نیازمند دو سند ورودی است.'),restrict:()=>toast('محدودسازی ویرایش از مجوز حساب ویرایشگر پیروی می‌کند.'),blockAuthors:()=>toast('مسدودسازی نویسندگان برای سند مشترک است.'),hideInk:()=>toast('نمایش جوهر تغییر کرد.'),
  getAddins:()=>toast('فروشگاه افزونه در حال اتصال است.'),myAddins:()=>toast('افزونه‌های نصب‌شده در این حساب نمایش داده می‌شوند.'),video:()=>{const u=prompt('نشانی ویدئوی آنلاین');if(u)insertAtCursor(u)},bookmark:()=>insertAtCursor('[نشانک]'),crossReference:()=>insertAtCursor('[ارجاع متقابل]'),
  training:()=>toast('آموزش‌های فراست در بخش راهنما قرار می‌گیرند.'),sendFeedback:()=>document.getElementById('feedbackPanelBtn')?.click(),getHelp:()=>location.href='/support'
 };
 function insertAtCursor(t){ed()?.focus();exec('insertText',t)}
 function insertSymbol(){const v=prompt('نماد','Ω');if(v)insertAtCursor(v)}
 function toggleNav(){document.querySelector('.navigation-pane')?.classList.toggle('hidden')}
 document.addEventListener('click',e=>{const b=e.target.closest('.word-tool');if(!b)return;const a=b.dataset.action;if(!special[a])return;e.preventDefault();e.stopImmediatePropagation();special[a]()},true);
 document.addEventListener('keydown',e=>{if(!(e.ctrlKey||e.metaKey)||e.altKey)return;const k=e.key.toLowerCase();if(k==='f'){e.preventDefault();special.find()}else if(k==='h'){e.preventDefault();special.replaceAll()}else if(k==='k'){e.preventDefault();special.link?.()}else if(k===' '){e.preventDefault();special.clearFormat?.()}},true);
 const st=document.createElement('style');st.textContent='.farast-watermark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;transform:rotate(-35deg);font:700 60px "B Nazanin",serif;color:rgba(80,95,115,.10);pointer-events:none;z-index:1}.word-page.paragraph-border-mode .word-editor p{outline:1px solid #dce2ea;outline-offset:2px}.farast-track-changes .word-editor{caret-color:#1769ff}';document.head.appendChild(st);
});
})();
