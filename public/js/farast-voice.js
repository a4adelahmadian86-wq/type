(()=>{
  'use strict';
  const editor=document.getElementById('editor');
  const mic=document.getElementById('mic');
  if(!editor||!mic)return;

  const meta=document.querySelector('meta[name="farast-capabilities"]');
  let caps={can_voice:true,can_type:true};
  try{if(meta?.content)caps={...caps,...JSON.parse(meta.content)}}catch{}
  const authenticated=window.FARAST_AUTHENTICATED===true;
  const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
  const Sound=()=>window.FarastSound;
  let savedRange=null,recognition=null,mediaRecorder=null,mediaStream=null,chunks=[],wanted=false,paused=false,startAt=0,timer=null,elapsedBeforePause=0,mode=null,sessionWords=0,lastConfidence=null;

  const panel=document.createElement('section');
  panel.id='farastVoicePanel';
  panel.className='farast-voice-panel';
  panel.dir='rtl';
  panel.setAttribute('role','dialog');
  panel.setAttribute('aria-label','تایپ صوتی حرفه‌ای فراست');
  panel.innerHTML=`
    <div class="farast-voice-head">
      <div class="farast-voice-orb"><i class="fa-solid fa-microphone-lines"></i></div>
      <div class="farast-voice-title"><b>تایپ صوتی فراست</b><small id="fvMode">آماده برای شروع</small></div>
      <button type="button" class="farast-voice-close" id="fvClose" aria-label="بستن">×</button>
    </div>
    <div class="farast-voice-body">
      <div class="farast-voice-status"><span><i class="farast-voice-dot"></i><b id="fvStatus" aria-live="polite">آماده</b></span><span class="farast-voice-time" id="fvTime">00:00</span></div>
      <div class="farast-voice-wave" aria-hidden="true">${'<i></i>'.repeat(18)}</div>
      <div class="farast-voice-live is-empty" id="fvLive" aria-live="polite">متن شنیده‌شده یا نتیجه رونویسی در اینجا نمایش داده می‌شود.</div>
      <div class="farast-voice-meta"><div><small>کلمات این جلسه</small><b id="fvWords">۰</b></div><div><small>اطمینان</small><b id="fvConfidence">—</b></div><div><small>حالت</small><b id="fvEngine">—</b></div></div>
      <div class="farast-voice-language"><label for="fvLocale">زبان گفتار</label><select id="fvLocale"><option value="fa-IR">فارسی — ایران</option><option value="en-US">English — US</option><option value="ar-SA">العربية — السعودية</option></select></div>
      <div class="farast-voice-actions"><button type="button" class="farast-voice-start" id="fvStart"><i class="fa-solid fa-microphone"></i> شروع</button><button type="button" class="farast-voice-pause" id="fvPause" disabled><i class="fa-solid fa-pause"></i> مکث</button><button type="button" class="farast-voice-stop" id="fvStop" disabled><i class="fa-solid fa-stop"></i> پایان</button></div>
      <div class="farast-voice-error" id="fvError" role="alert"></div>
      <div class="farast-voice-note"><i class="fa-solid fa-shield-halved"></i><span id="fvPrivacy">در حالت مرورگری، فراست فایل صوتی ذخیره نمی‌کند. اگر مرورگر تشخیص زنده را پشتیبانی نکند، صدا پس از پایان ضبط برای رونویسی AI ارسال و بدون ذخیره دائمی پردازش می‌شود.</span></div>
    </div>`;
  document.body.appendChild(panel);

  const $=id=>document.getElementById(id),status=$('fvStatus'),live=$('fvLive'),time=$('fvTime'),words=$('fvWords'),confidence=$('fvConfidence'),engine=$('fvEngine'),modeLabel=$('fvMode'),startBtn=$('fvStart'),pauseBtn=$('fvPause'),stopBtn=$('fvStop'),locale=$('fvLocale'),errorBox=$('fvError');
  const nf=n=>new Intl.NumberFormat('fa-IR').format(n||0);
  const SR=window.SpeechRecognition||window.webkitSpeechRecognition;

  function rememberRange(){const s=window.getSelection();if(!s||!s.rangeCount)return;const r=s.getRangeAt(0);if(editor.contains(r.commonAncestorContainer))savedRange=r.cloneRange()}
  document.addEventListener('selectionchange',rememberRange);
  editor.addEventListener('keyup',rememberRange);editor.addEventListener('mouseup',rememberRange);editor.addEventListener('touchend',rememberRange);

  function open(){rememberRange();panel.classList.add('is-open');setTimeout(()=>startBtn.focus(),30)}
  function close(){if(wanted)stop();panel.classList.remove('is-open')}
  function setStatus(textValue){status.textContent=textValue}
  function setLive(textValue,empty=false){live.textContent=textValue;live.classList.toggle('is-empty',empty)}
  function showError(message){errorBox.textContent=message;errorBox.classList.add('is-visible');Sound()?.play('voiceError',{gain:.75,cooldown:400})}
  function clearError(){errorBox.textContent='';errorBox.classList.remove('is-visible')}
  function setControls(active){startBtn.disabled=active;pauseBtn.disabled=!active;stopBtn.disabled=!active;locale.disabled=active;panel.classList.toggle('is-listening',active&&!paused)}
  function formatTime(ms){const total=Math.floor(ms/1000),m=Math.floor(total/60),s=total%60;return String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')}
  function beginTimer(){startAt=Date.now();clearInterval(timer);timer=setInterval(()=>{time.textContent=formatTime(elapsedBeforePause+(paused?0:Date.now()-startAt))},250)}
  function pauseTimer(){elapsedBeforePause+=Date.now()-startAt;clearInterval(timer);timer=null;time.textContent=formatTime(elapsedBeforePause)}
  function resetTimer(){clearInterval(timer);timer=null;elapsedBeforePause=0;time.textContent='00:00'}
  function countWords(textValue){return (String(textValue).trim().match(/\S+/gu)||[]).length}
  function insertText(textValue){
    const value=String(textValue||'').trim();if(!value)return;
    editor.focus();const s=window.getSelection();s.removeAllRanges();
    if(savedRange&&editor.contains(savedRange.commonAncestorContainer))s.addRange(savedRange);else{const r=document.createRange();r.selectNodeContents(editor);r.collapse(false);s.addRange(r)}
    const r=s.getRangeAt(0);r.deleteContents();const prefix=(r.startContainer.nodeType===Node.TEXT_NODE&&r.startOffset>0)?' ':'',node=document.createTextNode(prefix+value+' ');r.insertNode(node);r.setStartAfter(node);r.collapse(true);s.removeAllRanges();s.addRange(r);savedRange=r.cloneRange();
    editor.dispatchEvent(new InputEvent('input',{bubbles:true,inputType:'insertText',data:value}));
    sessionWords+=countWords(value);words.textContent=nf(sessionWords);
  }
  function errorMessage(code){return({
    'not-allowed':'دسترسی میکروفن رد شد. اجازه میکروفن را در تنظیمات مرورگر فعال کنید.',
    'service-not-allowed':'سرویس تشخیص گفتار مرورگر در دسترس نیست.',
    'audio-capture':'میکروفن قابل دسترسی نیست یا دستگاه ورودی پیدا نشد.',
    'no-speech':'گفتاری تشخیص داده نشد. دوباره با فاصله کمتر از میکروفن امتحان کنید.',
    'network':'ارتباط سرویس تشخیص گفتار قطع شد.',
    'aborted':'ضبط گفتار متوقف شد.'
  })[code]||'در تایپ صوتی خطایی رخ داد.'}

  function setupSpeechRecognition(){
    recognition=new SR();recognition.continuous=true;recognition.interimResults=true;recognition.maxAlternatives=1;recognition.lang=locale.value;
    recognition.onstart=()=>{mode='speech';engine.textContent='زنده';modeLabel.textContent='تشخیص زنده مرورگر';setStatus('در حال گوش دادن');setControls(true);Sound()?.play('voiceStart',{gain:.75,cooldown:0});beginTimer()};
    recognition.onresult=e=>{let interim='';for(let i=e.resultIndex;i<e.results.length;i++){const result=e.results[i],textValue=result[0]?.transcript||'';lastConfidence=Number(result[0]?.confidence);if(result.isFinal){insertText(textValue);setLive(textValue,false)}else interim+=textValue}if(interim)setLive(interim,false);if(Number.isFinite(lastConfidence)&&lastConfidence>0)confidence.textContent=Math.round(lastConfidence*100)+'٪'};
    recognition.onerror=e=>{if(e.error==='no-speech'&&wanted)return;showError(errorMessage(e.error));if(['not-allowed','service-not-allowed','audio-capture'].includes(e.error)){wanted=false;finishState(false)}};
    recognition.onend=()=>{if(wanted&&!paused){setTimeout(()=>{try{recognition.lang=locale.value;recognition.start()}catch{}},180)}else if(!wanted)finishState(true)};
  }

  async function startMedia(){
    if(!navigator.mediaDevices?.getUserMedia||!window.MediaRecorder)throw new Error('این مرورگر امکان ضبط صوت مورد نیاز را ندارد.');
    mediaStream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,autoGainControl:true,channelCount:1},video:false});
    const candidates=['audio/webm;codecs=opus','audio/ogg;codecs=opus','audio/webm'];const mime=candidates.find(x=>MediaRecorder.isTypeSupported?.(x))||'';
    mediaRecorder=new MediaRecorder(mediaStream,mime?{mimeType:mime}:undefined);chunks=[];
    mediaRecorder.ondataavailable=e=>{if(e.data&&e.data.size)chunks.push(e.data)};
    mediaRecorder.onerror=()=>{showError('ضبط صدا با خطا متوقف شد.');wanted=false;finishMediaTracks();finishState(false)};
    mediaRecorder.onstop=async()=>{const blob=new Blob(chunks,{type:mediaRecorder.mimeType||'audio/webm'});finishMediaTracks();if(blob.size<1000){showError('صدای کافی برای رونویسی ثبت نشد.');finishState(false);return}await uploadRecorded(blob)};
    mediaRecorder.start(1000);mode='server';engine.textContent='AI';modeLabel.textContent='ضبط امن + رونویسی AI';setStatus('در حال ضبط');setLive('در حال ضبط صدا… پس از پایان، متن رونویسی می‌شود.',true);setControls(true);Sound()?.play('voiceStart',{gain:.75,cooldown:0});beginTimer();
  }
  function finishMediaTracks(){if(mediaStream){mediaStream.getTracks().forEach(t=>t.stop());mediaStream=null}}
  async function uploadRecorded(blob){
    setStatus('در حال رونویسی');modeLabel.textContent='ارسال امن برای رونویسی AI';setLive('صدا دریافت شد؛ در حال تبدیل به متن…',true);pauseBtn.disabled=true;stopBtn.disabled=true;
    const fd=new FormData();fd.append('audio',blob,'farast-voice.'+(blob.type.includes('ogg')?'ogg':'webm'));fd.append('locale',locale.value);
    try{const r=await fetch('/editor/voice/transcribe',{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:fd});let data={};try{data=await r.json()}catch{}if(!r.ok)throw new Error(data.message||'رونویسی صوتی ناموفق بود.');insertText(data.text||'');setLive(data.text||'متنی تشخیص داده نشد.',!(data.text||'').trim());confidence.textContent='AI';setStatus('رونویسی شد');Sound()?.play('voiceStop',{gain:.72,cooldown:0})}catch(e){showError(e.message||'رونویسی صوتی انجام نشد.');setStatus('خطا')}finally{finishState(false)}
  }

  async function start(){
    clearError();rememberRange();
    if(!caps.can_voice){showError('تایپ صوتی از طرف مدیر برای این حساب غیرفعال است.');return}
    if(!caps.can_type){showError('ویرایش متن برای این حساب فعال نیست.');return}
    if(!authenticated){location.href='/login?continue='+encodeURIComponent('/editor');return}
    wanted=true;paused=false;sessionWords=0;lastConfidence=null;words.textContent='۰';confidence.textContent='—';resetTimer();
    try{if(SR){if(!recognition)setupSpeechRecognition();recognition.lang=locale.value;recognition.start()}else await startMedia()}catch(e){wanted=false;showError(e?.message||'شروع میکروفن ممکن نیست.');finishMediaTracks();finishState(false)}
  }
  function pauseResume(){
    if(!wanted)return;
    if(mode==='server'&&mediaRecorder){if(!paused&&mediaRecorder.state==='recording'){mediaRecorder.pause();paused=true;pauseTimer();setStatus('مکث');panel.classList.remove('is-listening');pauseBtn.innerHTML='<i class="fa-solid fa-play"></i> ادامه';Sound()?.play('toggleOff',{gain:.5})}else if(paused&&mediaRecorder.state==='paused'){mediaRecorder.resume();paused=false;beginTimer();setStatus('در حال ضبط');panel.classList.add('is-listening');pauseBtn.innerHTML='<i class="fa-solid fa-pause"></i> مکث';Sound()?.play('toggleOn',{gain:.5})}return}
    if(mode==='speech'&&recognition){if(!paused){paused=true;pauseTimer();try{recognition.stop()}catch{}setStatus('مکث');panel.classList.remove('is-listening');pauseBtn.innerHTML='<i class="fa-solid fa-play"></i> ادامه'}else{paused=false;beginTimer();try{recognition.lang=locale.value;recognition.start()}catch{}setStatus('در حال گوش دادن');panel.classList.add('is-listening');pauseBtn.innerHTML='<i class="fa-solid fa-pause"></i> مکث'}}
  }
  function stop(){
    if(!wanted)return;wanted=false;paused=false;pauseBtn.innerHTML='<i class="fa-solid fa-pause"></i> مکث';pauseTimer();setStatus(mode==='server'?'در حال آماده‌سازی':'پایان ضبط');panel.classList.remove('is-listening');
    if(mode==='server'&&mediaRecorder&&mediaRecorder.state!=='inactive'){mediaRecorder.stop();return}
    if(mode==='speech'&&recognition){try{recognition.stop()}catch{}Sound()?.play('voiceStop',{gain:.72,cooldown:0});finishState(true)}
  }
  function finishState(keepStatus){wanted=false;paused=false;clearInterval(timer);timer=null;setControls(false);panel.classList.remove('is-listening');pauseBtn.innerHTML='<i class="fa-solid fa-pause"></i> مکث';if(!keepStatus&&status.textContent!=='خطا'&&status.textContent!=='رونویسی شد')setStatus('آماده')}

  mic.onclick=e=>{e.preventDefault();open();if(!wanted)start()};
  $('fvClose').onclick=close;startBtn.onclick=start;pauseBtn.onclick=pauseResume;stopBtn.onclick=stop;
  document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.shiftKey&&e.key.toLowerCase()==='m'){e.preventDefault();open();wanted?stop():start()}if(e.key==='Escape'&&panel.classList.contains('is-open'))close()});
  mic.title='تایپ صوتی حرفه‌ای — Ctrl+Shift+M';
  if(!caps.can_voice){mic.disabled=true;mic.title='تایپ صوتی برای این حساب غیرفعال است'}
})();
