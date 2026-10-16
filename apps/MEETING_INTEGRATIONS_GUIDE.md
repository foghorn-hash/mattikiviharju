# i4ware® Job Seekers Autopilot AI — Kokous- ja Haastatteluavustajan Arkkitehtuuri- ja Integraatio-opas

Tämä dokumentti määrittelee Microsoft Teamsin ja Google Meetin kokousavustamisen toteutustavat, tekniset vaatimukset, turvallisuusperiaatteet ja asennusohjeet.

---

## 1. Kokousintegraatioiden kolme erillistä toteutustapaa (A, B ja C)

Kokousavustamisessa on erotettava toisistaan kolme eri arkkitehtuuritasoa:

| Menetelmä | Kuvaus | Toimintatapa ja Vaatimukset | Tila nykyisessä toteutuksessa |
| :--- | :--- | :--- | :--- |
| **Tapa A: Selainkokouksen laajennus** | Chrome- ja Edge -selainlaajennus (Manifest V3) | Toimii suoraan Teamsin ja Google Meetin selainversioissa (`teams.microsoft.com`, `meet.google.com`). Kaappaa haastattelijan äänen `chrome.tabCapture` -rajapinnan ja DOM-tekstityksen kautta. Lähettää puheen paikalliselle Windows-sovellukselle loopback-yhteydellä (`127.0.0.1:41793`). | **Valmis ja testattu** |
| **Tapa B: Windows-työpöytäsovellus ja Virtual Device -reititys** | Windows 11 Electron -ohjelma + Virtuaalikamera & Virtuaalikaapeli | Avustaa asennettua Teams-työpöytäohjelmaa. Haastattelijan ääni kuunnellaan WASAPI-loopbackin tai virtuaalikaapelin kautta. LiveAvatarin kuva välitetään Teamsin kameraksi (OBS Virtual Camera / DirectShow) ja ääni Teamsin mikrofoniksi (VB-Audio Virtual Cable). | **Valmis ja testattu** |
| **Tapa C: Erillinen itsenäinen AI-pilviosallistuja (Bot)** | Kokoukseen itsenäisenä bottina / osallistujana liittyvä pilvipalvelu | Vaatii Microsoft 365 / Azure -yritystilin ja `Calls.JoinGroupCall.All` -oikeudet tai Google Workspace Enterprise Meet Media API -hyväksynnät. | **Dokumentoitu arkkitehtuuri (vaatii ulkoiset yrityslisenssit)** |

> [!IMPORTANT]
> **Selvyysperiaate:** Nykyinen aktiivinen toteutus käyttää **Tapaa A (selaimet)** ja **Tapaa B (työpöytäohjelma)**. Avatarin esikatseluikkuna tai jaettu virtuaalikamera ei ole itsenäinen pilvibotti, vaan käyttäjän hallitsema paikallinen virtuaalilähde.

---

## 2. Toteutustapa A: Selainkokousta avustava laajennus (Chrome & Edge)

### Rakenne (Manifest V3)
- **Kansio:** `apps/meeting-assistant-extension`
- **Käyttö:** Kehitysvaiheessa ladataan purettuna laajennuksena osoitteissa:
  - Google Chrome: `chrome://extensions`
  - Microsoft Edge: `edge://extensions`

### Äänireitit ja turvallisuus
1. **Kokousääni vs. oma mikrofoni:**
   - Kokousääni (haastattelijan puhe) vastaanotetaan välilehdestä `chrome.tabCapture` -rajapinnalla ja toistetaan käyttäjän kaiuttimiin `AudioContext.destination` -liitännällä, jotta ääni ei mykisty.
   - Oma mikrofoni on täysin erillinen laite (`navigator.mediaDevices.getUserMedia({ audio: true })`). Välilehden ääni **ei** sisällä käyttäjän omaa mikrofonia.
2. **Käyttäjän nimenomainen toiminto:**
   - Laajennus ei koskaan aloita kuuntelua automaattisesti. Käyttäjän on painettava kelluvassa ohjauspalkissa painiketta *"Aloita kokousäänen kuuntelu"*.
3. **Turvallinen Loopback-yhteys:**
   - Laajennus yhdistää vain osoitteeseen `ws://127.0.0.1:41793`.
   - Windows-sovellus tarkistaa pyynnön `Origin`-otsakkeen (`chrome-extension://*`, `edge-extension://*`) ja istuntotunnisteen (`X-Session-Token`).
   - Ei avoimia komentorajapintoja eikä pääsyä muihin välilehtiin, evästeisiin tai salasanoihin.

---

## 3. Toteutustapa B: Teamsin työpöytäsovelluksen avustaminen (Windows 11)

Koska selainlaajennus ei voi suoraan lukea ulkoisen Windows-työpöytäohjelman (`Teams.exe`) muistia tai ääntä, Teamsin työpöytäversiolle käytetään virallisesti tuettuja Windows-äänilaitteita:

### 1. Kuvan välitys kokoukseen (LiveAvatar Video)
- Windows-sovelluksessa on painike **"📺 Avaa jaettava Avatar"**, joka avaa puhtaan `avatar-popout.html` -ikkunan ilman yksityisiä ohjeita.
- Kuva viedään Teamsiin:
  - **Vaihtoehto 1 (Suositeltu):** OBS Studio → *Start Virtual Camera* (lähteenä Avatar-ikkuna). Teamsin asetuksissa valitaan kameraksi *OBS Virtual Camera*.
  - **Vaihtoehto 2:** Teamsin normaali ikkunan jakaminen (*Share Window* → *LiveAvatar*).

### 2. Äänen välitys kokoukseen (LiveAvatar Audio)
- Asennetaan ilmainen ja standardi [VB-Audio Virtual Cable](https://vb-audio.com/Cable/).
- Windows-sovelluksen TTS-puhe ohjataan äänilaitteelle: `CABLE Input (VB-Audio Virtual Cable)`.
- Microsoft Teamsin laiteasetuksissa (`Settings → Devices → Microphone`) valitaan:
  - Mikrofoni: `CABLE Output (VB-Audio Virtual Cable)`
- Tällöin hyväksytty puheenvuoro kuuluu suoraan Teams-kokouksessa korkealaatuisena ilman taustamelua.

---

## 4. Toteutustapa C: Erillinen AI-osallistuja (Pilvibotti) — Vaatimusselvitys

Jos järjestelmä halutaan tulevaisuudessa laajentaa liittymään kokouksiin täysin itsenäisenä pilviosallistujana ilman paikallista selainta tai työpöytäohjelmaa, vaaditaan seuraavat viralliset alustakohtaiset resurssit:

### Microsoft Teams (Graph Calling API & ACS)
1. **Tilit ja lisenssit:**
   - Microsoft 365 Enterprise -vuokralainen (Tenant) esim. E3/E5.
   - Microsoft Azure -tilaus laskutuksella.
2. **Käyttöoikeudet ja hyväksynnät (Entra ID App Registration):**
   - Sovellusoikeudet (Application permissions):
     - `Calls.JoinGroupCall.All` (Liittyminen ryhmäpuheluihin)
     - `Calls.AccessMedia.All` (Reaaliaikaisen äänen ja videon käsittely)
   - Vaatii organisaation globaalin pääkäyttäjän nimenomaisen hyväksynnän (*Admin Consent*).
   - Teams PowerShell -komento botin sallimiseksi organisaation kokouksiin:
     ```powershell
     New-CsApplicationAccessPolicy -Identity "i4ware-Avatar-Policy" -AppIds "<Azure-App-ID>"
     Grant-CsApplicationAccessPolicy -PolicyName "i4ware-Avatar-Policy" -Global
     ```
3. **Infrastruktuuri:**
   - Julkinen HTTPS-päätepiste luotetulla SSL-varmenteella puhelukutsujen vastaanottoon.
   - Reaaliaikainen mediaserveri (.NET Microsoft.Graph.Communications.Calls SDK tai Azure Communication Services Calling SDK).

### Google Meet (Meet REST API & Meet Media API)
1. **Tilit ja lisenssit:**
   - Google Workspace Enterprise -organisaatiotili.
   - Google Cloud Platform (GCP) -projekti luottokortilla/laskutuksella.
2. **Käyttöoikeudet ja rajapinnat:**
   - Google Meet REST API (`meet.googleapis.com`)
   - Meet Media API (Developer Preview) WebRTC-liitännällä.
   - OAuth 2.0 -scopet: `https://www.googleapis.com/auth/meetings.space.created`
   - Google Workspace Marketplace App -hyväksyntä organisaatiolle.
3. **Infrastruktuuri:**
   - WebRTC-mediapalvelin (esim. LiveKit, Pion tai GStreamer SIP-yhdyskäytävä), joka vastaa Meet-puhelun WebRTC-neuvotteluun.

---

## 5. API-avainten suojaus ja .env-konfiguraatio

Palveluntarjoajien (OpenAI, Google Gemini, LiveAvatar) API-avaimia **ei koskaan** sisällytetä selainlaajennukseen tai Windows-asennuspakettiin.

Kaikki tekoälykutsut suoritetaan palvelinpuolella paikallisen WordPressin kautta:
- Osoite: `http://localhost/mattikiviharju`
- Päätepiste: `/wp-json/ai-cv-tailor/v1/interview/suggest-answer`
- Avaimet syötetään WordPress-hallinnassa:
  * `wp-admin → AI CV Tailor → Asetukset`

Windows-työpöytäsovelluksen ympäristötiedostopohja on luotu osoitteeseen:
`apps/interview-assistant-desktop/.env.example`
