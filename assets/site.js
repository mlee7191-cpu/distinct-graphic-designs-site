const print = document.querySelector('#print-resume');
if (print) print.addEventListener('click', () => window.print());

const form = document.querySelector('#enquiry-form');
if (form) {
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const g = (k) => String(data.get(k) || '').trim();
    const subject = encodeURIComponent(`Website enquiry — ${g('service')}`);
    const body = encodeURIComponent(['Hi Mark,', '', g('details'), '', `Service: ${g('service')}`, `Name: ${g('name')}`, `Email: ${g('email')}`, `Phone: ${g('phone') || 'Not provided'}`].join('\n'));
    window.location.href = `mailto:mark@distinctgraphicdesigns.com.au?subject=${subject}&body=${body}`;
    const note = document.querySelector('#form-note');
    if (note) note.textContent = 'Your email app should now be ready with this enquiry.';
  });
}
