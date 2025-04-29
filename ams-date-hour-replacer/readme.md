# AMS - Replace Date and Hour (Datepicker Version)

## Descriere
Acest plugin WordPress permite salvarea din backend a unei date si a unui interval orar (ora de inceput si ora de final) folosind un calendar picker si un time picker. In frontend, pluginul inlocuieste shortcode-urile `[ams_date]`, `[ams_hour_start]`, `[ams_hour_end]`, si `[ams_hour_interval]` cu valorile setate.

## Functionalitati
- Pagina de administrare pentru setarea datei si orelor.
- Foloseste jQuery UI Datepicker si jQuery Timepicker.
- Inlocuieste automat shortcode-urile in continutul paginilor sau articolelor.

## Shortcode-uri disponibile
- `[ams_date]` - afiseaza data cursului.
- `[ams_hour_start]` - afiseaza ora de inceput.
- `[ams_hour_end]` - afiseaza ora de final.
- `[ams_hour_interval]` - afiseaza intervalul orar format "ora inceput – ora final".

## Instalare
1. Creeaza un folder numit `ams-replace-date-hour` in `/wp-content/plugins/`.
2. Copiaza fisierele pluginului in acel folder.
3. Activeaza pluginul din WordPress Admin -> Plugins.

## Setari
Acceseaza meniul nou adaugat "Setări Curs" din Dashboard pentru a selecta data si orele dorite.

## Dependinte externe
- [jQuery UI Datepicker](https://jqueryui.com/datepicker/)
- [jQuery Timepicker](https://timepicker.co/)

## Autor
Raluca dev team

## Versiune
1.2

---

**Note:**
- Valorile salvate in baza de date sunt escapate pentru siguranta.
- Codul respecta conventiile propuse (`$strNumeVariabila`, `$bNumeVariabila`).

