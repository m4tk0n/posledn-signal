# POSLEDNÍ SIGNÁL

Příběhová single-player survival RPG hra pro webový prohlížeč.

> „Pokud mě někdo slyší, nechoďte na sever. Oni stále žijí.“

## Základní námět

Před 73 lety se svět odmlčel. Elektrické sítě zkolabovaly, komunikace zmizela a většina lidstva zahynula. Hráč se probouzí v podzemním výzkumném komplexu bez vzpomínek na svou minulost.

Komplex je téměř bez energie. Funguje pouze nouzový počítač a starý rádiový přijímač. Z rádia se ozve hlas, který varuje před cestou na sever. Hráč se vydává zjistit, co se stalo, kdo vysílá a proč některá místa na mapě stále někdo tají.

## Herní pilíře

1. **Průzkum** – opuštěná města, továrny, laboratoře, vojenské objekty a neznámé oblasti.
2. **Přežití** – získávání vody, jídla, energie, materiálů a vybavení.
3. **Budování základny** – opravy místností, výroba, výzkum a postupné rozšiřování komplexu.
4. **Příběh a záhady** – dokumenty, nahrávky, stopy, svědectví a rozhodnutí s následky.
5. **Postup postavy** – zkušenosti, dovednosti, vybavení a odemykání nových možností.
6. **Setkávání s lidmi** – obchod, spolupráce, konflikty a příběhy dalších přeživších.

## Základní herní smyčka

1. Hráč dostane informaci, úkol nebo nový cíl.
2. Připraví vybavení a vybere oblast k průzkumu.
3. V lokaci hledá suroviny, předměty, informace a nové stopy.
4. Vrátí se na základnu, vyhodnotí nálezy a rozhodne, co vyrobit, opravit nebo prozkoumat.
5. Získané informace a technologie zpřístupní další lokace a části příběhu.

Každá činnost má mít smysluplný výsledek. Hra nemá stát na bezúčelném čekání na výrobu nebo opakovaném sbírání stejných surovin.

## Dlouhodobá motivace

- Hlavní příběh rozdělený do kapitol.
- Vedlejší úkoly a osudy jednotlivých přeživších.
- Dlouhodobé projekty: obnovení elektrické sítě, oprava vysílače, satelitní spojení a rozšíření laboratoře.
- Vzácné technologie, plány a unikátní vybavení.
- Rozhodnutí, která mění vztahy, dostupné možnosti a pozdější události.
- Nové oblasti odemykané průzkumem a příběhovým postupem.

## Technologická architektura

- **PHP 8.4** – serverová logika; cílová verze odpovídá aktuální nabídce Webzdarma.
- **MySQL 8.0 + PDO** – databáze, výhradně připravené dotazy.
- **HTML5, CSS a JavaScript** – herní rozhraní bez zbytečných frameworků.
- **Python** – vývojové nástroje, generování obsahu a automatizované testy.
- **GitHub Actions** – kontrola a automatické nasazení na Webzdarma.

PHP 5.3 používat nebudeme. Webzdarma uvádí podporu PHP 7.4 až 8.4 a MySQL 8; pro nový projekt volíme PHP 8.4. Viz [nabídka Webzdarma](https://www.webzdarma.cz/vlastni-kod/) a [oznámení PHP 8.4](https://www.webzdarma.cz/novinky/detail/php-8-4-nyni-dostupne-na-webzdarma-cz-210).

## Bezpečnost a konfigurace

- Databázové údaje a aplikační tajemství patří do souboru `.env`, nikdy přímo do zdrojového kódu.
- Do GitHubu se ukládá pouze `.env.example` bez skutečných hesel. Skutečný `.env` je ignorován Gitem a vyloučen z FTP nasazení, takže se při deployi nepřepisuje.
- Přístup k databázi probíhá přes PDO, vypnuté emulované prepare statements a parametrizované dotazy.
- Chyby databáze se zapisují do serverového logu; uživateli se neukazují přihlašovací údaje ani interní výjimky.
- Relace používají HttpOnly, SameSite a při HTTPS Secure cookies; formuláře budou chráněny CSRF tokenem.
- Soukromé adresáře a skryté konfigurační soubory jsou blokovány přes `.htaccess`.
- Hesla hráčů budou ukládána výhradně pomocí `password_hash()` a ověřována přes `password_verify()`.
- Herní akce a změny stavu ověřuje server; klientské JavaScriptové údaje nejsou autoritativní.

### Nastavení serveru

1. Na serveru vytvořit soubor `.env` podle `.env.example`.
2. Doplnit skutečné údaje databáze, povinný `DB_PREFIX` (např. `posledni_signal_`) a unikátní `APP_KEY` o délce alespoň 32 znaků.
3. Otevřít `/install/install.php` a zadat instalační klíč shodný s `APP_KEY`.
4. Po úspěšné instalaci ponechat `.env` na serveru; automatické nasazení jej záměrně vynechává.
5. Zkontrolovat, že hosting používá PHP 8.4 a má zapnuté rozšíření PDO MySQL.

**Nikdy neposílat obsah produkčního `.env` do chatu ani do GitHubu.**

## Adresářová struktura

```
/
├── .github/workflows/deploy.yml
├── .env.example
├── .gitignore
├── .htaccess
├── index.php
├── install/
│   ├── install.php
│   └── schema.sql
├── app/
│   ├── bootstrap.php
│   ├── Core/
│   │   ├── Env.php
│   │   └── Database.php
│   ├── Security/
│   │   └── Session.php
│   ├── Game/
│   ├── Auth/
│   └── Services/
├── assets/
│   ├── css/main.css
│   ├── js/
│   └── images/
├── database/
│   └── migrations/
├── storage/
│   ├── logs/
│   └── saves/
├── tests/
└── tools/
```

Složky pro další moduly budou doplňovány postupně podle skutečné implementace; nebudeme vytvářet prázdné systémy jen kvůli struktuře.

## Databáze a instalace

- Databázi bude možné připravit samostatným webovým instalátorem `install/install.php`; ruční vytváření tabulek přes phpMyAdmin nebude běžným instalačním postupem.
- Instalátor načte konfiguraci ze skutečného serverového `.env`, včetně povinného `DB_PREFIX`. Prefix nebude mít skrytou výchozí hodnotu.
- Schéma bude uloženo v `install/schema.sql` a názvy tabulek budou v něm označené zástupným symbolem `{DB_PREFIX}`. Instalátor jej bezpečně nahradí hodnotou z konfigurace.
- Všechny tabulky, indexy a vazby budou používat stejný prefix. Žádná část aplikace nesmí názvy tabulek natvrdo zapisovat bez prefixu.
- Instalace ověří připojení a konfiguraci, bezpečně vytvoří potřebnou strukturu a nesmí při opakovaném spuštění nekontrolovaně duplikovat základní data.
- Po úspěšné instalaci bude instalační rozhraní zablokováno nebo odstraněno.
- Pozdější změny struktury databáze se budou provádět verzovanými migracemi, nikoli opětovným spuštěním destruktivní instalace.
- Produkční `.env` zůstává pouze na serveru; nesmí se dostat do GitHubu ani být přepsán FTP nasazením.

## Zásady vývoje

- Nejdříve navrhnout herní systémy a datový model, teprve potom implementovat jednotlivé části.
- Oddělovat příběhový obsah, herní pravidla, databázovou vrstvu a uživatelské rozhraní.
- Důležité herní akce a změny stavu ověřovat na serveru.
- Ukládání postupu musí být bezpečné a konzistentní.
- Každá větší změna musí mít odpovídající testy.
- Zachovávat přehlednou strukturu, komentáře a diagnostiku.
- Po každé etapě připravit testovací verzi; uživatel ověřuje chování hry, vývoj a opravy zajišťuje implementace.

## Grafické zpracování

Herní lokace se vykreslují jako samostatné škálovatelné SVG ilustrace uložené v `assets/images/scenes/`. Aktuálně jsou připravené dvě odlišné scény: kryokomora a servisní chodba. Rozhraní používá stejný systém pro úvodní obrazovku i samotnou hru; nové lokace dostanou vlastní ilustrace bez zásahu do herních pravidel. Kontrola existence a napojení scén probíhá v `tests/scene_assets_test.php`.

## První hratelný úsek\n\nPo instalaci databáze může hráč založit novou hru. Stav postavy se ukládá na serveru do JSON sloupce uložené hry; každá důležitá akce se zapisuje také do tabulky herních událostí. Úvodní místnost nabízí jednorázové interakce: prozkoumání kryokomory, nalezení nouzového balíčku, přečtení terminálu a poslech rádia. Balíček obsahuje dvě lahve vody a dvě potravinové dávky. Hráč může jídlo a vodu spotřebovat; jejich počet se odečítá z inventáře. Po každé úspěšné akci roste hlad a žízeň a klesá energie. Kritická žízeň nebo hlad poškozují zdraví. Při nulovém zdraví hra skončí a server další akce odmítne. Opakování jednorázových akcí server odmítne.\n\n## První vývojová etapa

1. Základ projektu, konfigurace a bezpečnostní vrstva.
2. Datový model hráče, herního světa a ukládání postupu.
3. Založení nové hry a načítání uložené hry.
4. První hratelný úsek: probuzení v komplexu, první oprava a zachycení rádiového signálu.
5. Automatizované testy a uživatelské testování na Webzdarma.

---

**Stav projektu:** technický základ, instalátor databáze a první hratelný úsek jsou implementovány. Hráč může založit uloženou hru, prozkoumat kryokomoru, nalézt nouzové zásoby, přečíst terminál, zachytit rádiové varování a postoupit do servisní chodby. Fungují základní potřeby postavy, spotřeba jídla a vody i stav konce hry. Další etapou je rozšíření světa, odpočinek a obnova energie, bezpečné obnovení uložené hry a navazující příběh.
