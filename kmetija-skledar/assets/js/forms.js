// Prikaz/skritje gesla in sprotno preverjanje obrazcev (strežnik vse preveri še enkrat)
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

    const rules = {
        first_name: (v) => !v.trim() ? 'Vpišite ime.' : !namePattern.test(v.trim()) ? 'Ime naj ima 2–50 črk.' : '',
        last_name: (v) => !v.trim() ? 'Vpišite priimek.' : !namePattern.test(v.trim()) ? 'Priimek naj ima 2–50 črk.' : '',
        email: (v) => !v.trim() ? 'Vpišite e-poštni naslov.' : !emailPattern.test(v.trim()) ? 'E-poštni naslov ni veljaven.' : '',
        password: (v, form) => {
            if (form.id === 'login-form') return v ? '' : 'Vpišite geslo.';
            if (v.length < 8) return 'Geslo mora imeti vsaj 8 znakov.';
            if (!/\p{L}/u.test(v) || !/\d/.test(v)) return 'Geslo mora vsebovati vsaj eno črko in eno številko.';
            return '';
        },
        password2: (v, form) => !v ? 'Ponovite geslo.' : v !== form.password.value ? 'Gesli se ne ujemata.' : '',
    };

    function check(input, form) {
        const rule = rules[input.name];
        if (!rule) return true;
        const msg = rule(input.value, form);
        const out = document.getElementById(input.id + '-error');
        if (out) out.textContent = msg;
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        return !msg;
    }

    document.querySelectorAll('form.form').forEach((form) => {
        const inputs = [...form.querySelectorAll('input:not([type=hidden])')];

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

    // Kljukice pri pravilih za geslo
    const pass = document.querySelector('#register-form #password');
    const list = document.getElementById('password-rules');
    if (pass && list) {
        const tests = { length: (v) => v.length >= 8, letter: (v) => /\p{L}/u.test(v), digit: (v) => /\d/.test(v) };
        const update = () => list.querySelectorAll('li').forEach((li) => li.classList.toggle('ok', tests[li.dataset.rule](pass.value)));
        pass.addEventListener('input', update);
        update();
    }
});
