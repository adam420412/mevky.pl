/* Native controls; WooCommerce remains responsible for the cart and checkout. */
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
 const slides = [...gallery.querySelectorAll('[data-gallery-slide]')];
 const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
 const counter = gallery.querySelector('[data-gallery-count]');
 const dialog = gallery.querySelector('dialog');
 const show = (index) => {
  slides.forEach((slide, i) => { slide.hidden = i !== index; });
  thumbs.forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
  counter.textContent = String(index + 1).padStart(2, '0');
 };
 thumbs.forEach((button, index) => {
  button.addEventListener('click', () => show(index));
  button.addEventListener('keydown', (event) => {
   if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
   event.preventDefault();
   const next = event.key === 'Home' ? 0 : event.key === 'End' ? thumbs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + thumbs.length) % thumbs.length;
   show(next); thumbs[next].focus();
  });
 });
 if (!dialog || typeof dialog.showModal !== 'function') return;
 slides.forEach((slide) => slide.addEventListener('click', (event) => {
  event.preventDefault(); dialog.querySelector('img').src = slide.dataset.full; dialog.showModal();
 }));
 dialog.querySelector('button').addEventListener('click', () => dialog.close());
 dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
});
