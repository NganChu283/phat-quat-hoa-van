(() => {
  const app=document.querySelector('.admin-app'); if(!app)return;
  const toggle=document.querySelector('[data-admin-toggle]');
  const overlay=document.querySelector('[data-admin-close]');
  const mobile=matchMedia('(max-width:800px)');
  const apply=()=>{
    const open=mobile.matches?app.classList.contains('is-menu-open'):!app.classList.contains('is-collapsed');
    toggle.setAttribute('aria-expanded',String(open));
    const sidebar=document.getElementById('admin-sidebar');sidebar.inert=!open;sidebar.setAttribute('aria-hidden',String(!open));
    overlay.hidden=!mobile.matches||!open;
  };
  toggle.addEventListener('click',()=>{app.classList.toggle(mobile.matches?'is-menu-open':'is-collapsed');apply();});
  overlay.addEventListener('click',()=>{app.classList.remove('is-menu-open');apply();});
  mobile.addEventListener('change',()=>{app.classList.remove('is-menu-open');apply();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){app.classList.remove('is-menu-open');apply();}});
  apply();
  const search=document.querySelector('[data-admin-search]');
  const results=document.querySelector('[data-admin-search-results]');
  const normalize=s=>s.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d');
  search.addEventListener('input',()=>{
    results.replaceChildren();const term=normalize(search.value.trim());results.hidden=!term;if(!term)return;
    document.querySelectorAll('.admin-sidebar-nav a').forEach(link=>{if(normalize(link.textContent).includes(term)){const item=document.createElement('a');item.href=link.href;item.textContent=link.textContent.trim();results.append(item);}});
    if(!results.children.length){const p=document.createElement('p');p.textContent='Không tìm thấy mục phù hợp.';results.append(p);}
  });
  search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();results.querySelector('a')?.click();}if(e.key==='Escape')results.hidden=true;});
  document.addEventListener('click',e=>{if(!e.target.closest('.admin-search-wrap'))results.hidden=true;});
})();
