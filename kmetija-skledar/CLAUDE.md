# Kmetija Skledar – spletna trgovina (šolski projekt)

Šolska vaja (4. letnik, tehnik računalništva): spletna trgovina v HTML, CSS, JS in PHP z MySQL v XAMPP.
Trgovina prodaja bučno olje in bučnice s kmetije Stanka Skledarja (stari starši avtorja, Filipa).
Komuniciraj v slovenščini, sproščeno in jedrnato. Komentarji v kodi in besedila na strani so v slovenščini; stran nagovarja kupca z "vi".

## Zagon
- Projekt leži v `C:\xampp\htdocs\kmetija-skledar`. V XAMPP Control Panelu zaženi Apache in MySQL.
- Baza: v `localhost/phpmyadmin` uvozi `database.sql` (pobriše in na novo ustvari bazo `kmetija_skledar` s testnimi podatki).
- Stran: `localhost/kmetija-skledar`
- Testna računa: admin `admin@skledar.si` / `admin123`, kupec `kupec@test.si` / `kupec123`
- Povezava z bazo: `config/db.php` (PDO, root brez gesla, utf8mb4)

## Struktura
- `index.php` domača stran: 4 izdelki, najprej tisti z `featured = 1` (kljukica pri urejanju izdelka), prazna mesta zapolnijo drugi aktivni izdelki na zalogi
- `trgovina.php` seznam izdelkov z iskanjem in filtri prek GET: `?q=` (LIKE po imenu, opisu, pakiranju, kategoriji), `?kategorija=ID`, `?razvrsti=` (privzeto, cena-nar, cena-pad, ime, najnovejse – ORDER BY iz bele liste), `?na-zalogi=1`; `shop_url()` ohrani ostale parametre
- `izdelek.php?id=` javna stran izdelka (slika, opis, cena, zaloga, količina + v košarico); skrit ali neobstoječ izdelek vrne 404
- `registracija.php`, `prijava.php`, `odjava.php` (odjava samo POST + CSRF)
- `moj-racun.php` samo za prijavljene (podatki računa in seje)
- `kosarica.php` košarica: POST dejanja `add` (id, qty, back), `update` (qty[ID]), `remove`, `clear`, vse s CSRF in preusmeritvijo; tabela z vmesnimi vsotami, povzetek s skupno ceno; gumb "Na blagajno" je še onemogočen
- `includes/cart.php` `cart_product()`, `cart_add()`, `cart_set()` (omeji na zalogo in `CART_MAX_QTY`), `cart_remove()`, `cart_contents()` (cene iz baze, uskladi skrite/pošle izdelke in vrne opozorila), `safe_back()` (vrnitev samo znotraj `BASE`), `add_to_cart_form($p, $withQty)`
- `admin/` samo za admina, vse upravljanje baze prek spletne strani (phpMyAdmin ni potreben):
  - `index.php` izdelki (seznam s sličicami, skrij/prikaži, izbriši skupaj z datoteko slike; izdelka iz naročil ni mogoče izbrisati, samo skriti), `izdelek.php` dodaj/uredi (`?id=`) z nalaganjem slike (ob dodajanju obvezna, pri urejanju zamenjaj/odstrani), opis 10–2000 znakov
  - `kategorije.php` seznam + obrazec za dodaj/uredi (`?uredi=`), brisanje samo prazne kategorije
  - `uporabniki.php` seznam in brisanje, `uporabnik.php` dodaj/uredi (podatki, vloga, novo geslo); admin ne more spremeniti svoje vloge ali izbrisati sebe
  - `narocila.php` seznam s filtrom `?status=`, `narocilo.php?id=` podrobnosti, sprememba statusa, brisanje
- `includes/admin_layout.php` `admin_start($title, $active)` / `admin_end()` (stranski meni), `ORDER_STATUSES`, `PAYMENT_METHODS`, `field_attrs()`, `field_error()`, `money()`, `action_button()` (POST gumb z `data-confirm`)
- `test-povezave.php` preverjanje povezave z bazo, samo za admina
- `includes/auth.php` seja, `BASE`/`url()`, `e()`, `current_user()`, `is_admin()`, `money()`, `plural()` (slovenska množina), `require_login()`, `require_admin()`, `login_user()`, `end_session()`, CSRF (`csrf_field()`, `csrf_valid()`), `logout_button()`, flash (`set_flash()`, `get_flash()`), samodejna odjava po 30 min (`SESSION_TIMEOUT`), časovni pas Europe/Ljubljana
- `includes/header.php` / `footer.php` glava in noga (navigacija se spreminja glede na prijavo/vlogo); pred vključitvijo nastavi `$pageTitle`, `$activePage`
- `includes/auth_layout.php` postavitev za prijavo/registracijo (črn levi panel + zavihka)
- `includes/password_field.php` polje za geslo z gumbom "oko"
- `includes/product_card.php` kartica izdelka (pričakuje `$p` z `image`), `includes/ikone.php` SVG ilustracije izdelkov in logotip
- `includes/slike.php` `product_image($p)` (fotografija ali SVG ilustracija, če je ni), `check_upload()` (vrsta iz vsebine s finfo + getimagesize, JPG/PNG/WEBP, 2 MB), `save_upload()` (naključno ime v `uploads/izdelki/`), `delete_upload()`; `uploads/.htaccess` prepove izvajanje skript in seznam mape
- `assets/css/style.css` vse oblikovanje (pod 560 px glava skrije "Registracija" in "Odjava", "Administracija" postane "Admin")
- `assets/js/forms.js` (naložen v footer, auth_layout, admin_layout): prikaz gesla, sprotna validacija vseh `form.form` (pravila po imenu polja, enaka kot v PHP; meje bere iz `maxlength`/`minlength`/`required`, `data-edit` na obrazcu uporabnika = prazno geslo dovoljeno), preverjanje slike ob izbiri + predogled, števec znakov za `textarea[maxlength]`, `form[data-confirm]` potrditev, samodejno razvrščanje v trgovini
- `assets/js/cart.js` (samo javni del): dodajanje v košarico s `fetch` + obvestilo (toast) + števec v glavi, na strani košarice gumba −/+ in sprotna sprememba količine. Pošlje glavo `X-Requested-With: fetch`, `kosarica.php` takrat vrne JSON (`cart_respond()`); JS ničesar ne računa sam. Brez JS vse deluje z navadnimi obrazci.
- `header.php` doda razred `js` na `<html>`: `.js-only` je viden samo z JS, `.no-js-only` samo brez JS

## Baza `kmetija_skledar`
- `roles` (id_role, name: kupec=1, admin=2)
- `users` (id_user, id_role FK, first_name, last_name, email UNIQUE, password_hash, phone, address, postal_code, city, created_at)
- `categories` (id_category, name, description): 1 Bučno olje, 2 Bučnice, 3 Darilni paketi
- `products` (id_product, id_category FK, name, description, price DECIMAL(8,2), packaging, stock, image, active, featured, created_at)
- `orders` (id_order, id_user FK, created_at, status ENUM('oddano','v obdelavi','poslano','zaključeno'), total, shipping_address, payment_method ENUM('po povzetju','predračun'))
- `order_items` (id_item, id_order FK CASCADE, id_product FK, quantity, price_at_order)
- Košarica je v seji (`$_SESSION['cart']` = [id_product => količina]), deluje tudi za goste in preživi prijavo; ob odjavi se izprazni. V seji ni cen – te se vedno preberejo iz baze. `header.php` šteje `array_sum($_SESSION['cart'])`.

## Pravila za kodo
- Vsa SQL z uporabniškimi podatki prek pripravljenih stavkov (`$pdo->prepare` + `?`), nikoli spajanje nizov.
- Vsak izpis uporabniških podatkov skozi `e()` (htmlspecialchars).
- Vsak obrazec POST ima `csrf_field()` in na strežniku `csrf_valid()`.
- Validacija vedno na strežniku (PHP); JS je samo za udobje.
- Gesla: `password_hash()` / `password_verify()`.
- Povezave vedno prek `url('pot.php')`, da delujejo tudi iz `admin/`.
- Admin strani začnejo z `require_admin()`, strani za prijavljene z `require_login()`.
- Brez frameworkov in knjižnic (čisti PHP/JS/CSS), ker je šolska naloga; koda naj bo razumljiva za zagovor.

## Dizajn (Claude Design, "Kmetija Skledar – spletna trgovina")
- Belo-črna osnova, kremno zelena (barva bučnega olja) kot poudarek. Barve so CSS spremenljivke v `:root` v `style.css`:
  `--black #111`, `--white #fff`, `--green #C5D19A`, `--green-dark #7F8F4A`, `--green-tint #F4F6EC`, `--line #E5E5E5`, `--muted #6B6B6B`, `--error #A12622`
- Pisavi: Playfair Display (naslovi), Open Sans (besedilo), Google Fonts.
- Gumbi: zaobljeni (pill), `.btn .btn-green` glavni, `.btn-black`, `.btn-outline`, `.btn-sm`. Kartice z robom 1px in zaobljenostjo 12px.
- Brez "AI slop" videza: brez generičnih pisav (Inter, Arial), vijoličnih gradientov in emojijev, ikone so inline stroke SVG.
- Odzivno (breakpointi 1100 / 900 / 560 px), dostopno (pravi `<button>`, `<label>`, `aria-*`, kontrast).
- Mesta brez pravih podatkov so označena v oglatih oklepajih, npr. `[telefon]`, `[fotografija ...]`. Teh podatkov si ne izmišljuj.
- Cene izdelkov so okvirne (še niso potrjene s kmetijo).

## Napredek nalog
1. Načrtovanje: ime, izdelki, struktura, UI, ER-model ✔ (dokument v Claude Docs)
2. XAMPP + MySQL baza in PHP povezava ✔
3. Registracija (validacija, unikaten e-naslov, hash gesla, zapis v `users`) ✔
4. Prijava in odjava (seja, zaključek seje, navigacija glede na prijavo) ✔
5. Avtentikacija in avtorizacija (vlogi kupec/admin, `require_login()`, `require_admin()` z lepo stranjo 403 v `includes/403.php`, vloga se ob vsaki zaščiteni strani preveri v bazi z `refresh_user()`, vrnitev na želeno stran po prijavi prek `$_SESSION['after_login']`, `.htaccess` blokira `includes/` in `config/`) ✔
6. Izdelki in CRUD (z nalaganjem slik in stranjo izdelka) + administracija ostalih tabel (kategorije, uporabniki, naročila) ✔
7. Funkcionalnosti trgovine: pregled, stran izdelka, iskanje/filtri/razvrščanje, košarica s skupno ceno ✔
8. JavaScript in validacija (validacija vseh obrazcev, košarica s fetch, potrditve, predogled slike, števec znakov; strežniška validacija ostaja povsod) ✔
9. Naslednje: blagajna in oddaja naročil (samo prijavljeni; zapis v `orders` + `order_items` v transakciji, zmanjšanje zaloge, pregled naročil v `moj-racun.php`).

Pred spremembami preveri, da `database.sql` ostane uvozljiv (na vrhu `SET NAMES utf8mb4;`).
