/* Mobile tutor navigation; no student session logic. */
(function () {
  'use strict';
  var sidebar=document.getElementById('bs-tutor-sidebar');
  var backdrop=document.getElementById('bs-tutor-overlay');
  var open=document.getElementById('bs-tutor-open');
  var close=document.getElementById('bs-tutor-close');
  if(!sidebar||!backdrop||!open||!close)return;
  function setOpen(value) {
    sidebar.classList.toggle('is-open', value);
    backdrop.hidden=!value;
    open.setAttribute('aria-expanded', value?'true':'false');
    if(value){close.focus();} else {open.focus();}
  }
  open.addEventListener('click',function(){setOpen(true);});
  close.addEventListener('click',function(){setOpen(false);});
  backdrop.addEventListener('click',function(){setOpen(false);});
  sidebar.addEventListener('click',function(event){
    if(event.target.closest('a'))setOpen(false);
  });
  document.addEventListener('keydown',function(event){
    if(event.key==='Escape'&&sidebar.classList.contains('is-open'))setOpen(false);
  });
  window.addEventListener('resize',function(){
    if(window.innerWidth>=1024&&sidebar.classList.contains('is-open'))setOpen(false);
  });
})();
