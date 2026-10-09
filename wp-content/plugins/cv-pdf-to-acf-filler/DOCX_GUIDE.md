# CV Word (.docx) & PDF to ACF Filler - Käyttöohje

Tämä opas neuvoo, miten luot ja muotoilet **Word (.docx)** -tiedoston siten, että tekoäly ja lisäosa poimivat siitä automaattisesti kaikki osiot, upotetut **PNG/JPEG-kuvat**, **useat YouTube-videolinkit** sekä kääntävät sisällön automaattisesti **suomeksi (Fi), englanniksi (En) ja ruotsiksi (Sv)**.

---

## 1. Miten .docx-tiedosto luodaan ja muotoillaan oikein

Jotta OpenAI ja lisäosa jäsentävät tiedot luotettavasti, käytä selkeitä otsikoita ja johdonmukaista rakennetta.

### A. Suositeltu rakenne Word-dokumentissa

```text
[Otsikko / Nimi]
Matti Kiviharju - Senior Full Stack Developer

[Profiili / Tiivistelmä]
Kokenut ohjelmistokehittäjä ja arkkitehti, jolla on yli 10 vuoden kokemus moderneista web-teknologioista ja skaalautuvista ratkaisuista.

[Sertifikaatit & Merkit / Badges]
• AWS Certified Solutions Architect
• Scrum Master (PSM I)
• WordPress Core Contributor

[Työkokemus / Experience]
Senior Developer | Acme Oy | 2021 - Nykyhetki
Vastuussa pilvipalveluiden kehityksestä ja mikropalveluarkkitehtuurista. Tiimin tekninen vetovastuu.

Web Developer | Digitoimisto Esimerkki | 2018 - 2021
Verkkopalveluiden, REST API -rajapintojen ja räätälöityjen teemojen toteutus.

[Projektit / Selected Projects]
Verkkokaupan Uudistus
Teknologiat: React, Node.js, WordPress REST API, Stripe
Kuvaus: Kansainvälisen verkkokaupan täysi arkkitehtuurin ja käyttöliittymän modernisointi.
Linkki: https://github.com/kayttaja/verkkokauppa (Katso lähdekoodi)
Videot:
- Esittelyvideo 1: https://www.youtube.com/watch?v=VIDEO_ID_1
- Toimintojen demo 2: https://youtu.be/VIDEO_ID_2
[Liitä tähän projektin kuvakaappaus PNG- tai JPEG-muodossa suoraan Wordiin]

Mobiilisovellus Backend
Teknologiat: Python, FastAPI, Docker, PostgreSQL
Kuvaus: Reaaliaikainen viestintä- ja push-ilmoitusjärjestelmä.
Videot: https://www.youtube.com/watch?v=VIDEO_ID_3
[Liitä tähän toinen kuvakaappaus]

[Koulutus / Education]
Tietojenkäsittelytieteen maisteri | Helsingin yliopisto | 2015 - 2020
Pääaineena ohjelmistojärjestelmät ja tekoäly.

[Lisäkoulutus & Kurssit / Additional Training]
Advanced React & TypeScript | Frontend Masters | 2023
Kattava koulutus moderneista React 18 -ominaisuuksista ja tyyppiturvallisuudesta.

[Taidot / Skills]
Ohjelmointikielet: PHP, JavaScript, TypeScript, Python, SQL
Frontend: React, Next.js, Vue, CSS3, HTML5, Webpack, Vite
Backend & Pilvi: Node.js, REST API, GraphQL, Docker, AWS, Linux
```

---

## 2. Kuvat ja YouTube-videot projektiosioissa

### A. Kuvat (.png, .jpg, .webp)
1. **Liitä kuvat suoraan Word-dokumenttiin** (`Lisää` -> `Kuvat` -> `Tämä laite`).
2. Lisäosa purkaa Word-tiedoston `word/media/`-kansiosta kaikki upotetut PNG/JPEG-kuvat.
3. Kuvat ladataan automaattisesti WordPressin mediakirjastoon ja liitetään `cv_project`-postauksiin sekä ACF-kuvagalleriaan (`cv_project_screenshots`).

### B. YouTube-videot (Useat videot per projekti)
Voit lisätä YouTube-linkkejä kahdella tavalla:
1. **Suorina URL-osoitteina tekstiin**:
   - `https://www.youtube.com/watch?v=dQw4w9WgXcQ`
   - `https://youtu.be/9bZkp7q19f0`
2. **Word-hyperlinkkeinä** (`Ctrl+K` / `Cmd+K`):
   - Kirjoita esim. `Katso videoesittely` ja aseta hyperlinkin kohteeksi YouTube-osoite.
3. Teema luo jokaiselle videolle automaattisesti pikkukuvan (`img.youtube.com`) ja toistopainikkeen, joka käynnistää videon suoraan **overlay lightbox -ikkunassa**!

---

## 3. Tiedoston lataus ja käsittely WordPressissä

### A. Koko CV:n tuonti (PDF tai Word .docx)
1. Mene WordPressin hallinnassa kohtaan: **Settings > CV PDF to ACF**.
2. Varmista **Status**-taulukosta, että kaikki vaatimukset ovat kunnossa.
3. Valitse **Upload CV (PDF or Word .docx)** -osiossa tiedosto ja lähdekieli.
4. Klikkaa **Upload and Process CV**.
   * *Huom:* Tämä korvaa kaikki aiemmat CV-postaukset (kokemus, koulutus, projektit, taidot, merkit).

### B. Pelkkien projektien tuonti Word-tiedostosta (.docx) (Erillinen projektin lisäys)
Jos haluat tuoda tai päivittää **vain projektit** koskematta muihin CV-osioihin:
1. Mene kohtaan **Settings > CV PDF to ACF**.
2. Etsi osio **Lisää projekteja Word-tiedostosta (.docx) / Add Projects from Word (.docx)**.
3. Valitse `.docx`-tiedosto, joka sisältää yhden tai useamman projektin. Tiedostossa voi olla:
   - Projektin nimi ja teknologiapino (Tech Stack)
   - Kattava kuvaus ja saavutukset
   - Verkkolinkki (esim. GitHub-repo tai live-sivusto)
   - Yksi tai useampi YouTube-videolinkki
   - Suoraan Wordiin upotetut kuvakaappaukset (PNG/JPEG/WebP)
4. Valitse **Lähdekieli** (Suomi, Englanti tai Ruotsi). Tekoäly kääntää projektit automaattisesti muille kielille.
5. Valitse **Tuontitapa**:
   - **Lisää olemassa olevien jatkoksi (suositus):** Säilyttää nykyiset projektit ja lisää uudet näkyviin alkuun.
   - **Korvaa aiemmat projektit:** Poistaa vain aiemmat `cv_project`-postaukset, mutta **ei koske** kokemukseen, koulutukseen tai taitoihin.
6. Klikkaa **Tuo projektit (.docx) / Import Projects**.
7. Lisäosa luo `cv_project`-artikkelit, lataa kuvat mediakirjastoon, asettaa pikkukuvat, linkittää YouTube-videot ja yhdistää kieliversiot Polylangilla!

---

## 4. Teeman esityskerros ja Overlay-soitin

Etusivulla (`template-cv.php`):
- Jokainen projekti näyttää omat YouTube-videonsa ja kuvakaappauksensa.
- Klikkaamalla mitä tahansa videota tai kuvaa avautuu **Popup Lightbox Overlay**:
  - Video käynnistyy heti iframe-upotuksena.
  - Nuolinäppäimillä (`←` / `→`) tai edellinen/seuraava-painikkeilla voi siirtyä saumattomasti videosta toiseen tai videosta kuviin.
  - Ikkunan voi sulkea `Esc`-näppäimellä, ruksista tai klikkaamalla taustaa, jolloin videotoisto pysähtyy välittömästi.
