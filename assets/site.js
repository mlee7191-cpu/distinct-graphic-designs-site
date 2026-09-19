const year = document.querySelector('#year');
if (year) year.textContent = new Date().getFullYear();

const printButton = document.querySelector('#print-resume');
if (printButton) printButton.addEventListener('click', () => window.print());

const enquiryForm = document.querySelector('#enquiry-form');
if (enquiryForm) {
  enquiryForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const data = new FormData(enquiryForm);
    const name = String(data.get('name') || '').trim();
    const email = String(data.get('email') || '').trim();
    const phone = String(data.get('phone') || '').trim();
    const service = String(data.get('service') || '').trim();
    const details = String(data.get('details') || '').trim();
    const subject = encodeURIComponent(`Website enquiry — ${service}`);
    const body = encodeURIComponent([
      `Hi Mark,`,
      ``,
      details,
      ``,
      `Service: ${service}`,
      `Name: ${name}`,
      `Email: ${email}`,
      `Phone: ${phone || 'Not provided'}`
    ].join('\n'));
    window.location.href = `mailto:mark.lee@distinctgraphicdesigns.com.au?subject=${subject}&body=${body}`;
    const note = document.querySelector('#form-note');
    if (note) note.textContent = 'Your email app should now be ready with this enquiry.';
  });
}
