(()=>{
'use strict';
if(!window.__farastVoiceRuntimeLoaded){
  window.__farastVoiceRuntimeLoaded=true;
  const s=document.createElement('script');
  s.src='/js/farast-voice.js?v=20260916';
  s.async=false;
  document.head.appendChild(s);
}
})();
