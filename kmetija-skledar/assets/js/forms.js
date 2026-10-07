// Prikaz/skritje gesla in sprotno preverjanje obrazcev.
// Pravila so enaka kot v PHP, a so tu samo za udobje: strežnik vse preveri še enkrat,
// ker lahko kdorkoli JavaScript izklopi ali pošlje zahtevo mimo obrazca.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-pass').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', String(show));
            btn.setAttribute('aria-label', show ? 'Skrij geslo' : 'Pokaži geslo');
        });
    });

    const namePattern = /^[\p{L}][\p{L} '\-]{1,49}$/u;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const imageTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxImageSize = 2 * 1024 * 1024; // 2 MB, enako kot UPLOAD_MAX_SIZE v includes/slike.php

    function passwordRule(v) {
        if (v.length < 8) return 'Geslo mora imeti vsaj 8 znakov.';
        if (v.length > 72) return 'Geslo je predolgo (največ 72 znakov).';
        if (!/\p{L}/u.test(v) || !/\d/.test(v)) return 'Geslo mora vsebovati vsaj eno črko in eno številko.';
        return '';
    }

    // Pravilo za vsako ime polja: vrne sporočilo o napaki ali prazen niz.
    // Meje (maxlength, minlength, required) preberemo iz HTML, da jih ne pišemo dvakrat.
    const rules = {
        first_name: (v) => !v.trim() ? 'Vpišite ime.' : !namePattern.test(v.trim()) ? 'Ime naj ima 2–50 črk.' : '',
        last_name: (v) => !v.trim() ? 'Vpišite priimek.' : !namePattern.test(v.trim()) ? 'Priimek naj ima 2–50 črk.' : '',
        email: (v) => !v.trim() ? 'Vpišite e-poštni naslov.' : !emailPattern.test(v.trim()) ? 'E-poštni naslov ni veljaven.' : '',
        password: (v, form) => form.id === 'login-form' ? (v ? '' : 'Vpišite geslo.') : passwordRule(v),
        password2: (v, form) => !v ? 'Ponovite geslo.' : v !== form.password.value ? 'Gesli se ne ujemata.' : '',
        // Admin: pri urejanju uporabnika je prazno geslo dovoljeno (pomeni "ne spremeni")
        new_password: (v, form) => (!v && form.dataset.edit) ? '' : passwordRule(v),
        phone: (v) => v.trim() && !/^\+?[0-9 \/\-]{6,20}$/.test(v.trim()) ? 'Telefon naj vsebuje samo številke (npr. 041 123 456).' : '',
        postal_code: (v) => v.trim() && !/^\d{4}$/.test(v.trim()) ? 'Poštna številka naj ima 4 števke.' : '',
        // Admin: izdelek in kategorija
        name: (v, form, input) => {
            const len = v.trim().length;
            return len < 2 || len > input.maxLength ? 'Ime naj ima 2–' + input.maxLength + ' znakov.' : '';
        },
        packaging: (v) => !v.trim() || v.trim().length > 30 ? 'Vpišite pakiranje (npr. 0,5 l), največ 30 znakov.' : '',
        price: (v) => !/^\d{1,6}([.,]\d{1,2})?$/.test(v.trim()) ? 'Cena naj bo število z največ dvema decimalkama, npr. 12,50.' : '',
        stock: (v) => !/^\d{1,6}$/.test(v.trim()) ? 'Zaloga naj bo celo število 0 ali več.' : '',
        description: (v, form, input) => {
            const len = v.trim().length;
            if (input.minLength > 0 && (len < input.minLength || len > input.maxLength)) {
                return 'Opis naj ima ' + input.minLength + '–' + input.maxLength + ' znakov.';
            }
            return len > input.maxLength ? 'Opis je predolg (največ ' + input.maxLength + ' znakov).' : '';
        },
        image: (v, form, input) => {
            const file = input.files[0];
            if (!file) return input.required ? 'Izberite sliko izdelka.' : '';
            if (!imageTypes.includes(file.type)) return 'Dovoljene so samo slike JPG, PNG ali WEBP.';
            if (file.size > maxImageSize) return 'Slika je prevelika (največ 2 MB, izbrana ima ' + (file.size / 1048576).toFixed(1).replace('.', ',') + ' MB).';
            return '';
        },
    };

    function check(input, form) {
        const rule = rules[input.name];
        if (!rule) return true;
        const msg = rule(input.value, form, input);
        const out = document.getElementById(input.id + '-error');
        if (out) out.textContent = msg;
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        return !msg;
    }

    document.querySelectorAll('form.form').forEach((form) => {
        const inputs = [...form.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), textarea')];

        inputs.forEach((input) => {
            // Preveri, ko uporabnik zapusti polje; po prvi napaki preverja sproti
            input.addEventListener('blur', () => { if (input.value) check(input, form); });
            input.addEventListener('input', () => {
                if (input.getAttribute('aria-invalid') === 'true') check(input, form);
                if (input.name === 'password' && form.password2 && form.password2.value) check(form.password2, form);
            });
        });

        form.addEventListener('submit', (ev) => {
            const bad = inputs.filter((input) => !check(input, form));
            if (bad.length) {
                ev.preventDefault();
                bad[0].focus();
            }
        });
    });

    // Potrditev pred nepovratnimi dejanji (brisanje izdelkov, uporabnikov, praznjenje košarice …)
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (!confirm(form.dataset.confirm)) ev.preventDefault();
        });
    });

    // Slika izdelka: preveri vrsto in velikost takoj ob izbiri in pokaže predogled, še preden jo naložimo
    const imageInput = document.querySelector('input[type=file]#image');
    const preview = document.getElementById('image-preview');
    if (imageInput && preview) {
        const original = preview.innerHTML;
        imageInput.addEventListener('change', () => {
            const ok = check(imageInput, imageInput.form);
            const file = imageInput.files[0];
            if (!ok || !file) {
                preview.innerHTML = original;
                if (!ok) imageInput.value = ''; // napačne datoteke ne pošljemo
                return;
            }
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = 'Predogled izbrane slike';
            preview.replaceChildren(img);
        });
    }

    // Števec znakov pod opisom (npr. 120 / 2000)
    document.querySelectorAll('textarea[maxlength]').forEach((area) => {
        const counter = document.createElement('span');
        counter.className = 'char-count';
        counter.setAttribute('aria-live', 'polite');
        area.after(counter);
        const update = () => {
            const len = area.value.length;
            counter.textContent = len + ' / ' + area.maxLength;
            counter.classList.toggle('char-count-low', area.minLength > 0 && len < area.minLength);
        };
        area.addEventListener('input', update);
        update();
    });

    // Kljukice pri pravilih za geslo
    const pass = document.querySelector('#register-form #password');
    const list = document.getElementById('password-rules');
    if (pass && list) {
        const tests = { length: (v) => v.length >= 8, letter: (v) => /\p{L}/u.test(v), digit: (v) => /\d/.test(v) };
        const update = () => list.querySelectorAll('li').forEach((li) => li.classList.toggle('ok', tests[li.dataset.rule](pass.value)));
        pass.addEventListener('input', update);
        update();
    }

    // Trgovina: sprememba razvrščanja ali kljukice "Samo na zalogi" takoj osveži rezultate
    document.querySelectorAll('.shop-tools select, .shop-tools input[type=checkbox]').forEach((el) => {
        el.addEventListener('change', () => el.form.requestSubmit());
    });
});
