/* ─────────────────────────────────────────────
   Keystone Prep — Form Submission Handler
   Sends all forms to api/submit.php → Airtable + email
───────────────────────────────────────────── */

(function () {
  const ENDPOINT = '/api/submit.php';

  const FORM_CONFIG = {
    'tour-form':       { type: 'tour',        successMsg: 'Thank you! Our admissions team will contact you shortly to confirm your tour.' },
    'contact-form':    { type: 'contact',     successMsg: 'Thank you for reaching out! We\'ll get back to you soon.' },
    'camp-form':       { type: 'camp',        successMsg: 'Thank you for registering! We\'ll send a confirmation email shortly.' },
    'admissions-form': { type: 'admissions',  successMsg: 'Thank you for your interest! Our admissions team will be in touch soon.' },
    'donor-form':      { type: 'donor',       successMsg: 'Thank you for your generous interest in supporting Keystone Prep!' },
    'join-form':       { type: 'job',         successMsg: 'Thank you for your interest in joining our team! We\'ll review your information and reach out.' },
    'misconduct-form': { type: 'misconduct',  successMsg: 'Your report has been submitted. All reports are reviewed promptly.' },
    'kpApplyForm':     { type: 'application', successMsg: 'Thank you for applying to Keystone Prep! Our admissions team will be in touch within 5–7 business days.' },
    'alumni-form':     { type: 'alumni',      successMsg: 'Thank you for updating your information! We\'re glad you\'re staying connected with Keystone Prep.' },
  };

  function collectFormData(form) {
    var data = {};
    var elements = form.elements;
    for (var i = 0; i < elements.length; i++) {
      var el = elements[i];
      if (!el.name || el.name.startsWith('_') || el.type === 'submit' || el.type === 'button' || el.type === 'file') continue;
      if (el.type === 'radio' && !el.checked) continue;
      if (el.type === 'checkbox') {
        data[el.name] = el.checked ? 'Yes' : 'No';
        continue;
      }
      if (el.value) data[el.name] = el.value;
    }
    return data;
  }

  function showFeedback(form, message, isError) {
    var existing = form.querySelector('.form-feedback');
    if (existing) existing.remove();

    var div = document.createElement('div');
    div.className = 'form-feedback';
    div.style.cssText = 'padding:16px 20px;border-radius:12px;font-size:.92rem;line-height:1.5;margin-top:16px;' +
      (isError
        ? 'background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;'
        : 'background:#F0FDF4;color:#166534;border:1px solid #BBF7D0;');
    div.textContent = message;
    form.appendChild(div);

    if (!isError) {
      form.reset();
      div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function setSubmitState(btn, loading) {
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
      btn.dataset.origText = btn.textContent;
      btn.textContent = 'Submitting…';
      btn.style.opacity = '0.7';
    } else {
      btn.textContent = btn.dataset.origText || 'Submit';
      btn.style.opacity = '';
    }
  }

  var RECAPTCHA_SITE_KEY = '6LcyUUctAAAAABWNPkwZzuaKZqqbg9fiyhTTrdYw';

  function injectSpamFields(form) {
    var hp = document.createElement('div');
    hp.setAttribute('aria-hidden', 'true');
    hp.style.cssText = 'position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;';
    hp.innerHTML = '<input type="text" name="_name_confirm" tabindex="-1" autocomplete="off" />' +
                   '<input type="email" name="_email_confirm" tabindex="-1" autocomplete="off" />';
    form.appendChild(hp);

    var ts = document.createElement('input');
    ts.type = 'hidden';
    ts.name = '_form_loaded';
    ts.value = String(Date.now());
    form.appendChild(ts);
  }

  function getRecaptchaToken(action) {
    return new Promise(function (resolve) {
      if (!window.grecaptcha) { resolve(''); return; }
      grecaptcha.ready(function () {
        try {
          grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: action }).then(resolve).catch(function () { resolve(''); });
        } catch (e) {
          resolve('');
        }
      });
    });
  }

  function initForm(formId, config) {
    var form = document.getElementById(formId);
    if (!form) return;

    injectSpamFields(form);

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var hp1 = form.querySelector('[name="_name_confirm"]');
      var hp2 = form.querySelector('[name="_email_confirm"]');
      if ((hp1 && hp1.value) || (hp2 && hp2.value)) return;

      var loadedEl = form.querySelector('[name="_form_loaded"]');
      var loadedTime = loadedEl ? parseInt(loadedEl.value, 10) : Date.now();
      var elapsed = (Date.now() - loadedTime) / 1000;
      if (elapsed < 3) return;

      var submitBtn = form.querySelector('[type="submit"], button:not([type])');
      var data = collectFormData(form);

      setSubmitState(submitBtn, true);

      getRecaptchaToken(config.type).then(function (token) {
        fetch(ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            form_type: config.type,
            data: data,
            _recaptcha_token: token,
            _form_loaded: String(loadedTime)
          })
        })
        .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, body: j }; }); })
        .then(function (result) {
          setSubmitState(submitBtn, false);
          if (result.ok && result.body.success) {
            showFeedback(form, config.successMsg, false);
          } else {
            showFeedback(form, 'Something went wrong. Please call (813) 264-4500 or email info@keystoneprep.org.', true);
          }
        })
        .catch(function () {
          setSubmitState(submitBtn, false);
          showFeedback(form, 'Something went wrong. Please call (813) 264-4500 or email info@keystoneprep.org.', true);
        });
      });
    });
  }

  // Load reCAPTCHA v3 script
  var rcScript = document.createElement('script');
  rcScript.src = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_SITE_KEY;
  document.head.appendChild(rcScript);

  // Initialize all forms on the page
  Object.keys(FORM_CONFIG).forEach(function (id) {
    initForm(id, FORM_CONFIG[id]);
  });
})();
