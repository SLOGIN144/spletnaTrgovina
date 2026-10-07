// Košarica brez ponovnega nalaganja strani.
// Obrazci delujejo tudi brez JavaScripta (navaden POST); JS jih le pošlje s fetch in prikaže odgovor.
// Cene in vsote vedno izračuna strežnik (kosarica.php vrne JSON), JS ničesar ne računa sam.
document.addEventListener('DOMContentLoaded', () => {
    // Pošlje obrazec na kosarica.php in vrne JSON odgovor strežnika
    async function send(formData) {
        const res = await fetch(document.querySelector('.cart-btn').href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'fetch' },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    // Kratko obvestilo v kotu zaslona (bralniki zaslona ga preberejo zaradi aria-live)
    let toast;
    function notify(message, ok = true) {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.append(toast);
        }
        toast.textContent = message;
        toast.classList.toggle('toast-error', !ok);
        toast.classList.add('show');
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    // Števec na ikoni košarice v glavi
    function updateBadge(count) {
        const btn = document.querySelector('.cart-btn');
        let badge = btn.querySelector('.cart-badge');
        if (count > 0 && !badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            btn.append(badge);
        }
        if (badge) {
            badge.textContent = count;
            badge.hidden = count === 0;
        }
        btn.setAttribute('aria-label', 'Košarica, izdelkov: ' + count);
        btn.classList.remove('bump');
        void btn.offsetWidth; // ponovno sproži animacijo
        btn.classList.add('bump');
    }

    // ---------- Dodajanje v košarico (kartice izdelkov in stran izdelka) ----------
    document.querySelectorAll('form.add-form').forEach((form) => {
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const button = form.querySelector('button[type=submit]');
            const qty = form.querySelector('.qty-input');
            if (qty && !qty.checkValidity()) {
                notify('Vpišite količino med ' + qty.min + ' in ' + qty.max + '.', false);
                qty.focus();
                return;
            }
            button.disabled = true;
            try {
                const data = await send(new FormData(form));
                notify(data.message, data.ok);
                updateBadge(data.count);
                // Na strani izdelka pokaži/posodobi povezavo "V košarici: N kos"
                const mine = data.items.find((i) => i.id === Number(form.elements.id.value));
                const badgeRow = document.querySelector('.product-detail-info .badge')?.parentElement;
                if (mine && badgeRow) {
                    let inCart = badgeRow.querySelector('.in-cart');
                    if (!inCart) {
                        inCart = document.createElement('a');
                        inCart.className = 'in-cart';
                        inCart.href = document.querySelector('.cart-btn').href;
                        badgeRow.append(inCart);
                    }
                    inCart.textContent = 'V košarici: ' + mine.quantity + ' kos';
                }
            } catch {
                form.submit(); // če fetch ne uspe, obrazec pošljemo po starem
            } finally {
                button.disabled = false;
            }
        });
    });

    // ---------- Stran košarice: sprotna sprememba količine ----------
    const cartForm = document.getElementById('cart-form');
    if (!cartForm) return;

    function render(data) {
        if (data.items.length === 0) {
            location.reload(); // prikaže stanje "Košarica je prazna"
            return;
        }
        document.querySelectorAll('[data-id]').forEach((el) => {
            const item = data.items.find((i) => i.id === Number(el.dataset.id));
            if (!item) { el.remove(); return; }
            if (el.matches('tr')) {
                el.querySelector('[data-subtotal]').textContent = item.subtotal;
                el.querySelector('.qty-input').value = item.quantity;
            } else if (el.matches('dt')) {
                el.querySelector('[data-qty]').textContent = item.quantity;
            } else if (el.matches('dd')) {
                el.textContent = item.subtotal;
            }
        });
        document.getElementById('cart-total').textContent = data.total;
        const n = data.count, mod = n % 100;
        document.getElementById('cart-count').textContent = n + ' ' +
            (mod === 1 ? 'izdelek' : mod === 2 ? 'izdelka' : mod === 3 || mod === 4 ? 'izdelki' : 'izdelkov');
        updateBadge(n);
        data.notes.forEach((note) => notify(note, false));
    }

    // Spremembo pošljemo z majhnim zamikom, da hitri kliki na + ne pošljejo deset zahtev
    let timer;
    function queueUpdate(input) {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const fd = new FormData();
            fd.append('csrf', cartForm.elements.csrf.value);
            fd.append('action', 'update');
            fd.append(input.name, input.value === '' ? '0' : input.value);
            try {
                render(await send(fd));
            } catch {
                notify('Košarice ni bilo mogoče posodobiti. Osvežite stran.', false);
            }
        }, 400);
    }

    cartForm.querySelectorAll('.qty-stepper').forEach((stepper) => {
        const input = stepper.querySelector('.qty-input');
        stepper.querySelectorAll('.step').forEach((btn) => {
            btn.addEventListener('click', () => {
                const next = Number(input.value) + Number(btn.dataset.step);
                input.value = Math.max(0, Math.min(Number(input.max), next));
                queueUpdate(input);
            });
        });
        input.addEventListener('change', () => {
            if (Number(input.value) > Number(input.max)) {
                notify('Na zalogi je samo ' + input.max + ' kos.', false);
                input.value = input.max;
            }
            if (Number(input.value) < 0 || !Number.isInteger(Number(input.value))) input.value = 0;
            queueUpdate(input);
        });
    });

    // Enter v polju za količino ne pošlje celega obrazca, ampak samo to vrstico
    cartForm.addEventListener('submit', (ev) => {
        ev.preventDefault();
        if (document.activeElement.matches('.qty-input')) queueUpdate(document.activeElement);
    });
});
