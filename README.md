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

## Technologický základ

Zamýšlené prostředí:

- PHP – serverová logika a práce s databází.
- MySQL 8.0 – ukládání herního světa, postupu, inventáře, úkolů a událostí.
- HTML, CSS a JavaScript – webové herní rozhraní.
- Python – vývojové nástroje, generování dat a automatizované testy.
- GitHub – verzování, revize změn a automatizované kontroly.

**Kompatibilita:** PHP 5.3 je velmi stará verze. Pokud bude povinná, musí se návrh držet její syntaxe a ověřit kompatibilitu s konkrétním MySQL 8.0 serverem a dostupným databázovým ovladačem. Pokud hosting dovolí PHP 8.x, je vhodnější použít podporovanou verzi.

## Zásady vývoje

- Nejdříve navrhnout herní systémy a datový model, teprve potom implementovat jednotlivé části.
- Oddělovat příběhový obsah, herní pravidla, databázovou vrstvu a uživatelské rozhraní.
- Důležité herní akce a změny stavu ověřovat na serveru.
- Ukládání postupu musí být bezpečné a konzistentní.
- Každá větší změna musí mít odpovídající testy.
- Neprovádět nevyžádané změny již existujících souborů.

## První vývojová etapa

1. Upřesnit pravidla hry a podobu herního dne.
2. Navrhnout datový model a ukládání herního postupu.
3. Připravit základní webové rozhraní a založení nové hry.
4. Implementovat první hratelný úsek: probuzení v komplexu, první opravu a zachycení rádiového signálu.
5. Ověřit ukládání a načítání hry.

---

**Stav projektu:** návrh konceptu. Hratelná verze zatím není implementována.
