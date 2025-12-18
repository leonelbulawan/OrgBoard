// Avatar helper: load avatar from localStorage and apply to profile areas
(function(){
  function applyAvatar(data){
    if(!data) return;
    // common ids
    ['profileImage','dropdownImage'].forEach(id=>{
      const el = document.getElementById(id);
      if(el && el.tagName === 'IMG'){
        el.src = data; el.style.display = 'block';
      }
    });

    // hide initial icons when avatar present
    ['profileInitial','dropdownInitial'].forEach(id=>{
      const el = document.getElementById(id);
      if(el) el.style.display = 'none';
    });

    // update by class selectors (fallbacks)
    document.querySelectorAll('img.avatar-img, img.profile-img, .profile-avatar img, .avatar-preview').forEach(img=>{
      try{ img.src = data; img.style.display = 'block'; }catch(e){}
    });

    // ensure profile buttons show image
    document.querySelectorAll('.profile-btn').forEach(btn=>{
      // if there's already an <img> inside, prefer that
      let img = btn.querySelector('img.profile-img') || btn.querySelector('img');
      if(!img){
        img = document.createElement('img');
        img.className = 'profile-img';
        img.style.width = '100%'; img.style.height = '100%'; img.style.objectFit = 'cover';
        btn.prepend(img);
      }
      try{ img.src = data; img.style.display = 'block'; }catch(e){}
      // hide any person/icon elements inside
      btn.querySelectorAll('i.bi-person, i.bi-person-fill, i.bi, i.fas, i.fa').forEach(i=>{ i.style.display='none'; });
    });
  }

  function loadAndApply(){
    try{
      const data = localStorage.getItem('orgboardAvatar');
      if(!data) return;
      applyAvatar(data);
    }catch(e){/* ignore */}
  }

  // Apply user names (full and first) to profile dropdowns and welcome text
  function applyUserNames(){
    try{
      const full = localStorage.getItem('userFullName') || localStorage.getItem('userName') || '';
      const first = localStorage.getItem('userFirstName') || (full ? full.split(/\s+/)[0] : '') || '';

      // profile name element(s)
      document.querySelectorAll('#profileName').forEach(el=>{
        if(el) el.textContent = full || 'Username';
      });

      // welcome back (single element usually)
      document.querySelectorAll('.welcome-back').forEach(el=>{
        if(el){
          const namePart = first || 'User';
          el.textContent = `WELCOME BACK, ${namePart.toUpperCase()}!`;
        }
      });
    }catch(e){/* ignore */}
  }

  window.addEventListener('storage', function(e){
    if(e.key === 'orgboardAvatar') loadAndApply();
    if(e.key === 'userFullName' || e.key === 'userFirstName' || e.key === 'userName') applyUserNames();
  });

  document.addEventListener('DOMContentLoaded', loadAndApply);
  document.addEventListener('DOMContentLoaded', applyUserNames);
})();
