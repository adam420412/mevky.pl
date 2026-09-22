document.querySelectorAll('.mevky-header nav').forEach(nav=>{
 const open=nav.querySelector('.wp-block-navigation__responsive-container-open'),panel=nav.querySelector('.wp-block-navigation__responsive-container'),close=nav.querySelector('.wp-block-navigation__responsive-container-close');
 if(!open||!panel||!close)return;
 open.setAttribute('aria-expanded','false');open.setAttribute('aria-controls',panel.id);
 let scroll='';
 const hide=(restore=true)=>{panel.classList.remove('is-menu-open');panel.removeAttribute('role');panel.removeAttribute('aria-modal');open.setAttribute('aria-expanded','false');document.body.style.overflow=scroll;if(restore)open.focus();};
 open.addEventListener('click',()=>{scroll=document.body.style.overflow;panel.classList.add('is-menu-open');panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Menu główne');open.setAttribute('aria-expanded','true');document.body.style.overflow='hidden';close.focus();});
 close.addEventListener('click',()=>hide());panel.addEventListener('click',e=>{if(e.target.closest('a'))hide(false);});
 panel.addEventListener('keydown',e=>{if(e.key==='Escape')hide();if(e.key==='Tab'){const items=[...panel.querySelectorAll('a[href],button')],first=items[0],last=items.at(-1);if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
 matchMedia('(min-width:1001px)').addEventListener('change',e=>{if(e.matches&&panel.classList.contains('is-menu-open'))hide(false);});
});
