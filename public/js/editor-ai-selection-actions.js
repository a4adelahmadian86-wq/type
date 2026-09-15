(()=>{
'use strict';
const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();
ready(()=>{
  const editor=document.querySelector('#editor');
  const ribbon=document.querySelector('#ribbon-ai');
  if(!editor||!ribbon)return;
  const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
  const ops=[
    ['selection.proofread','بازبینی','fa-spell-check'],
    ['selection.rewrite','بازنویسی','fa-pen'],
    ['selection.shorten','کوتاه‌سازی','fa-compress'],
    ['selection.expand','گسترش','fa-expand'],
    ['selection.summarize','خلاصه','fa-align-left'],
    ['selection.explain','توضیح','fa-circle-info'],
    ['selection.translate','ترجمه','fa-language']
  ];
  let activeRange=null;
  const toast=text=>{
    let n=document.querySelector('#farastAiActionToast');
    if(!n){n=document.createElement('div');n.id='farastAiActionToast';n.className='farast-ai-action-toast';document.body.append(n)}
    n.textContent=text;clearTimeout(n._t);n._t=setTimeout(()=>n.remove(),2800);
  };
  const post=async body=>{
    const r=await fetch('/editor/ai/assist',{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(body)});
    let j={};try{j=await r.json()}catch{}
    if(!r.ok)throw Object.assign(new Error(j.message||'پردازش هوش مصنوعی انجام نشد.'),{status:r.status});
    return j;
  };
  const currentSelection=()=>{
    const s=window.getSelection();
    if(!s||s.rangeCount===0||s.isCollapsed||!editor.contains(s.anchorNode)||!editor.contains(s.focusNode))return null;
    const range=s.getRangeAt(0).cloneRange();
    const text=range.toString().trim();
    return text?{range,text}:null;
  };
  const restoreRange=range=>{
    if(!range||!range.startContainer?.isConnected||!range.endContainer?.isConnected)return false;
    const s=window.getSelection();s.removeAllRanges();s.addRange(range);return true;
  };
  const applyText=(range,text)=>{
    if(!restoreRange(range)){toast('محل انتخاب قبلی دیگر معتبر نیست.');return}
    const s=window.getSelection(),r=s.getRangeAt(0);r.deleteContents();
    const node=document.createTextNode(String(text??''));r.insertNode(node);r.setStartAfter(node);r.collapse(true);s.removeAllRanges();s.addRange(r);
    editor.dispatchEvent(new Event('input',{bubbles:true}));toast('تغییر اعمال شد.');
  };
  const closePanel=()=>document.querySelector('#farastAiActionPanel')?.remove();
  const showResult=(label,source,result,range,meta={})=>{
    closePanel();
    const panel=document.createElement('section');panel.id='farastAiActionPanel';panel.className='farast-ai-action-panel';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');
    const head=document.createElement('div');head.className='farast-ai-action-head';
    const title=document.createElement('div');const strong=document.createElement('strong');strong.textContent=label;const small=document.createElement('small');small.textContent='پیشنهاد هوش مصنوعی — اعمال فقط با تأیید شما';title.append(strong,small);
    const x=document.createElement('button');x.type='button';x.className='farast-ai-action-close';x.setAttribute('aria-label','بستن');x.textContent='×';x.onclick=closePanel;head.append(title,x);
    const info=document.createElement('div');info.className='farast-ai-action-meta';info.textContent='پردازش: '+(meta.processing_mode==='external'?'خارجی':meta.processing_mode||'خودکار')+' • ارائه‌دهنده: '+(meta.provider||'—')+' • مدل: '+(meta.model||'—');
    const body=document.createElement('div');body.className='farast-ai-action-body';
    const original=document.createElement('div');original.className='farast-ai-action-box';const oh=document.createElement('b');oh.textContent='متن انتخاب‌شده';const op=document.createElement('p');op.textContent=source;original.append(oh,op);
    const suggested=document.createElement('div');suggested.className='farast-ai-action-box suggested';const sh=document.createElement('b');sh.textContent='پیشنهاد';const sp=document.createElement('p');sp.textContent=result;suggested.append(sh,sp);body.append(original,suggested);
    const actions=document.createElement('div');actions.className='farast-ai-action-buttons';
    const apply=document.createElement('button');apply.type='button';apply.className='primary';apply.textContent='اعمال روی انتخاب';apply.onclick=()=>{applyText(range,result);closePanel()};
    const copy=document.createElement('button');copy.type='button';copy.textContent='کپی پیشنهاد';copy.onclick=async()=>{try{await navigator.clipboard.writeText(result);toast('پیشنهاد کپی شد.')}catch{toast('کپی خودکار ممکن نشد.')}};
    const cancel=document.createElement('button');cancel.type='button';cancel.textContent='انصراف';cancel.onclick=closePanel;actions.append(apply,copy,cancel);
    panel.append(head,info,body,actions);document.body.append(panel);requestAnimationFrame(()=>panel.classList.add('open'));
  };
  const run=async(operation,label,button)=>{
    const sel=currentSelection();if(!sel){toast('ابتدا بخشی از متن را انتخاب کنید.');return}
    activeRange=sel.range;
    const body={operation,text:sel.text,processing_mode:'automatic'};
    if(operation==='selection.translate'){
      const lang=prompt('زبان مقصد ترجمه را وارد کنید','انگلیسی');if(!lang)return;body.target_language=lang.trim();if(!body.target_language)return;
    }
    const old=button.innerHTML;button.disabled=true;button.textContent='در حال پردازش…';
    try{
      const j=await post(body);const text=String(j.text??j.ai?.result?.text??'').trim();
      if(!text){toast('پاسخ قابل اعمالی دریافت نشد.');return}
      showResult(label,sel.text,text,activeRange,{processing_mode:j.ai?.processing_mode,provider:j.ai?.provider,model:j.ai?.model});
    }catch(e){toast(e.status===429?'سهمیه درخواست‌های هوش مصنوعی تکمیل شده است.':e.message)}finally{button.disabled=false;button.innerHTML=old}
  };
  const target=[...ribbon.querySelectorAll('.ribbon-group')].find(x=>(x.dataset.group||'')==='عملیات')||ribbon;
  if(!target.querySelector('.farast-ai-selection-actions')){
    const wrap=document.createElement('div');wrap.className='farast-ai-selection-actions';
    ops.forEach(([op,label,icon])=>{const b=document.createElement('button');b.type='button';b.dataset.aiSelectionOperation=op;b.title=label+' روی متن انتخاب‌شده';b.innerHTML='<i class="fa-solid '+icon+'" aria-hidden="true"></i><span>'+label+'</span>';b.addEventListener('mousedown',()=>{const s=currentSelection();if(s)activeRange=s.range});b.addEventListener('click',()=>{if(activeRange&&restoreRange(activeRange)){}run(op,label,b)});wrap.append(b)});
    const note=document.createElement('small');note.className='farast-ai-privacy-note';note.textContent='فقط متن انتخاب‌شده ارسال می‌شود؛ هیچ تغییری بدون تأیید شما اعمال نمی‌شود.';wrap.append(note);target.append(wrap);
  }
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closePanel()});
});
})();
