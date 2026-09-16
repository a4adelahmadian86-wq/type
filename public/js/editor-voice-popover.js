(()=>{
  'use strict';
  if(window.__farastVoiceRuntimeLoaderInstalled)return;
  window.__farastVoiceRuntimeLoaderInstalled=true;

  const load=()=>{
    const editor=document.getElementById('editor');
    const mic=document.getElementById('mic');
    if(!editor||!mic)return;
    if(document.getElementById('farastVoicePanel'))return;

    const script=document.createElement('script');
    script.src='/js/farast-voice.js?v=20260916-2';
    script.async=false;
    script.onload=()=>{
      if(!document.getElementById('farastVoicePanel')){
        setTimeout(load,50);
      }
    };
    script.onerror=()=>{
      window.__farastVoiceRuntimeLoaderInstalled=false;
      console.error('[FARAST VOICE] Failed to load /js/farast-voice.js');
    };
    document.head.appendChild(script);
  };

  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',load,{once:true});
  }else{
    load();
  }
})();
