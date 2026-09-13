(()=>{
  'use strict';
  const BASE='/assets/sounds/';
  const FILES={
    info:'01-notification-info.ogg',success:'02-notification-success.ogg',warning:'03-notification-warning.ogg',error:'04-notification-error.ogg',
    uploadStart:'05-upload-start.ogg',uploadComplete:'06-upload-complete.ogg',processingStart:'07-processing-start.ogg',processingComplete:'08-processing-complete.ogg',processingError:'09-processing-error.ogg',
    aiStart:'10-ai-start.ogg',aiComplete:'11-ai-complete.ogg',aiWarning:'12-ai-warning.ogg',saveSuccess:'13-save-success.ogg',exportComplete:'14-export-complete.ogg',
    message:'15-message-received.ogg',support:'16-support-message.ogg',login:'17-login-success.ogg',logout:'18-logout.ogg',button:'19-button-soft.ogg',
    toggleOn:'20-toggle-on.ogg',toggleOff:'21-toggle-off.ogg',modalOpen:'22-modal-open.ogg',modalClose:'23-modal-close.ogg',autosave:'24-editor-autosave.ogg',
    voiceStart:'25-voice-start.ogg',voiceStop:'26-voice-stop.ogg',voiceError:'27-voice-error.ogg'
  };
  const state={enabled:localStorage.getItem('farast.sound.enabled')!=='0',volume:Number(localStorage.getItem('farast.sound.volume')||.78),unlocked:false,last:new Map()};
  if(!Number.isFinite(state.volume))state.volume=.78;
  state.volume=Math.max(0,Math.min(1,state.volume));
  const pool=new Map();
  function audio(name){if(!FILES[name])return null;if(!pool.has(name)){const a=new Audio(BASE+FILES[name]);a.preload='auto';a.volume=state.volume;pool.set(name,a)}return pool.get(name)}
  function unlock(){if(state.unlocked)return;state.unlocked=true;['button','success','voiceStart','voiceStop','voiceError'].forEach(name=>{const a=audio(name);if(a)a.load()})}
  function play(name,opts={}){
    if(!state.enabled||!state.unlocked)return Promise.resolve(false);
    const now=Date.now(),cooldown=Number(opts.cooldown??100),last=state.last.get(name)||0;if(now-last<cooldown)return Promise.resolve(false);state.last.set(name,now);
    const base=audio(name);if(!base)return Promise.resolve(false);
    let a=base;
    if(!base.paused&&!base.ended){a=base.cloneNode(true)}else{try{base.currentTime=0}catch{}}
    a.volume=Math.max(0,Math.min(1,state.volume*Number(opts.gain??1)));
    return a.play().then(()=>true).catch(()=>false);
  }
  function setEnabled(value){state.enabled=!!value;localStorage.setItem('farast.sound.enabled',state.enabled?'1':'0');renderToggle();if(state.enabled)play('toggleOn',{cooldown:0});}
  function setVolume(value){state.volume=Math.max(0,Math.min(1,Number(value)||0));localStorage.setItem('farast.sound.volume',String(state.volume));pool.forEach(a=>a.volume=state.volume)}
  function renderToggle(){const b=document.getElementById('farastSoundToggle');if(!b)return;b.classList.toggle('is-muted',!state.enabled);b.setAttribute('aria-pressed',state.enabled?'true':'false');b.title=state.enabled?'صدای رابط فعال است':'صدای رابط غیرفعال است';b.innerHTML=state.enabled?'<i class="fa-solid fa-volume-high"></i>':'<i class="fa-solid fa-volume-xmark"></i>'}
  function ensureToggle(){if(document.getElementById('farastSoundToggle'))return;const b=document.createElement('button');b.type='button';b.id='farastSoundToggle';b.className='farast-sound-toggle';b.setAttribute('aria-label','فعال یا غیرفعال کردن صدای رابط');b.addEventListener('click',()=>{unlock();setEnabled(!state.enabled)});document.body.appendChild(b);renderToggle()}
  window.FarastSound={play,setEnabled,setVolume,isEnabled:()=>state.enabled,unlock,files:{...FILES}};
  ['pointerdown','keydown','touchstart'].forEach(ev=>window.addEventListener(ev,unlock,{once:true,capture:true,passive:true}));
  document.addEventListener('DOMContentLoaded',ensureToggle);
  document.addEventListener('change',e=>{if(!state.unlocked)return;const el=e.target;if(el.matches('input[type="checkbox"],input[type="radio"]'))play(el.checked?'toggleOn':'toggleOff',{gain:.55})});
  document.addEventListener('click',e=>{if(!state.unlocked)return;const el=e.target.closest('button,a.btn,.header-cta,.login-link');if(!el||el.id==='mic'||el.id==='farastSoundToggle'||el.closest('.farast-voice-panel'))return;play('button',{gain:.38,cooldown:130})});
  const nativeFetch=window.fetch.bind(window);
  window.fetch=async(input,init)=>{
    const url=typeof input==='string'?input:(input?.url||'');
    let start=null,ok=null,fail=null;
    if(url.includes('/editor/upload')){start='uploadStart';ok='uploadComplete';fail='processingError'}
    else if(url.includes('/editor/analyze')){start='aiStart';ok='aiComplete';fail='processingError'}
    else if(url.includes('/editor/save')){ok='autosave';fail='error'}
    else if(url.includes('/editor/feedback')){ok='success';fail='error'}
    if(start)play(start,{gain:.7,cooldown:250});
    try{const res=await nativeFetch(input,init);if(ok&&res.ok)play(ok,{gain:url.includes('/editor/save')?.32:.68,cooldown:url.includes('/editor/save')?2500:250});else if(fail&&!res.ok)play(fail,{gain:.7,cooldown:500});return res}catch(err){if(fail)play(fail,{gain:.7,cooldown:500});throw err}
  };
})();
