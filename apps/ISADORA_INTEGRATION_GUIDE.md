# TroikaTronix Isadora 3 + Google Gemini AI Avatar: Windows 11 Integraatio-opas

Tämä opas määrittelee TroikaTronix Isadora 3 -järjestelmän, Spout-teknologian, NDI-videovirtojen, OBS Studion sekä VB-Audio Virtual Cablen hyödyntämisen yhdessä Gemini AI Avatar -haastatteluavustajan kanssa Microsoft Teamsissa ja Google Meetissä.

---

## 1. TroikaTronix Add-ons ja Käyttöjärjestelmäyhteensopivuus

Ennen toteutusta virallisen TroikaTronix-lisäosasivuston ([https://troikatronix.com/add-ons/](https://troikatronix.com/add-ons/)) ja Windows 11 -arkkitehtuurin perusteella varmistetut faktat:

| Komponentti / Lisäosa | Windows 11 Tila | Tekninen Arkkitehtuuri | Rooli Avatar-integraatiossa |
| :--- | :--- | :--- | :--- |
| **Spout (Spout2)** | **Täysi natiivituki** | GPU-kiihdytetty nollaviiveinen tekstuurijako DirectX- ja OpenGL-muistissa. | Isadora 3 tukee Spoutia natiivisti *Stage Setup* -valikossa. Lähettää ja vastaanottaa avatar-videovirtaa paikallisesti. |
| **SpoutCam** | **Täysi tuki** | Spout2:n mukana toimitettava DirectShow-virtuaalikamera-ajuri. | Muuntaa minkä tahansa Spout-kuvalähteen suoraan Windows-webkameraksi Teamsiin ja Google Meetiin. |
| **Syphon Virtual Webcam** | **EI TUETTU (vain macOS)** | TroikaTronix Syphon Virtual Webcam toimii ainoastaan macOS:ssä, koska se nojaa Applen IOSurface- ja CoreVideo-arkkitehtuuriin. | **Ei asenneta Windowsille.** Windowsissa käytetään SpoutCamia tai OBS Virtual Cameraa. |
| **NDI (Network Device Interface)** | **Täysi natiivituki** | Isadoran sisäänrakennetut *NDI Watcher* ja *NDI Broadcaster* -aktorit. | Siirtää pakkaamatonta/matalaviiveistä videota ja audiota lähiverkossa tai paikallisesti. NDI Tools Webcam Input muuntaa virran webkameraksi. |
| **Screen Capture Actor** | **Täysi natiivituki** | Isadora 3:n sisäänrakennettu aktori tietyn Windows-ikkunan kaappaukseen. | Kaappaa suoraan verkkosivuston *LiveAvatar Popout* -ikkunan ilman lisäajureita. |
| **OBS Virtual Camera** | **Täysi tuki** | OBS Studion virtuaalikamera-ajuri. | Yleisin ja vakain tuotantovirtuaalikamera Windows 11:ssä. |

> [!IMPORTANT]
> **Modulaarisuus:** Gemini AI Avatar -haastatteluavustaja toimii täysin itsenäisesti selaimessa ilman Isadoraa. Isadora 3 toimii valinnaisena videonkäsittely-, compositing- ja tehostekerroksena.

---

## 2. Kuvan Reititys (Video Routing Workflows)

### Työnkulku A: Suora Selaintoteutus + OBS Virtual Camera (Suositeltu & Yksinkertaisin)
1. Avaa haastatteluavustaja WordPress-sivustolla (`http://localhost/mattikiviharju/` tai `https://mattikiviharju.i4ware.fi/`).
2. Klikkaa widgetin yläreunasta painiketta **📺 Avaa jaettava Avatar**. Tämä avaa puhtaan `?gemini_popout=1` -popout-ikkunan (koko esim. 780x560 tai koko näyttö).
3. Avaa **OBS Studio**:
   - Lisää uusi lähde: *Window Capture* (Ikkunakaappaus) → Valitse ikkuna `LiveAvatar — Matti Kiviharju`.
   - Paina oikealta: **Start Virtual Camera** (Käynnistä virtuaalikamera).
4. Siirry **Microsoft Teamsiin** tai **Google Meetiin**:
   - Avaa Asetukset → Laitteet → Kamera.
   - Valitse kameraksi: **OBS Virtual Camera**.

---

### Työnkulku B: Isadora 3 + SpoutCam (Interaktiivinen Lavastus & Efektit)
Jos haluat ajaa avatarin Isadora 3 -efektiprosessoinnin (esim. Chroma Key, taustavideot, partikkelit tai audiomodulaatio) läpi:

1. **Spout2 ja SpoutCam -asennus:**
   - Lataa uusin Spout2-asennuspaketti osoitteesta: [https://spout.zeal.co/](https://spout.zeal.co/)
   - Varmista, että asennuksen yhteydessä ruutu **SpoutCam** on valittuna.
   - Suorita kerran `SpoutCamSettings.exe` ja aseta resoluutioksi `1920x1080` (30 tai 60 fps).
2. **Avatarin tuonti Isadora 3:een:**
   - Avaa Isadora 3 ja luo uusi projekti.
   - Lisää aktori **Screen Capture**:
     - Kaksoisklikkaa aktoria ja valitse kaapattavaksi kohteeksi `LiveAvatar — Matti Kiviharju`.
   - Yhdistä `Screen Capture` -aktorin video-ulostulo efektiketjuun (esim. *Matte Color Key*, *Color Changer*, *Shadertoy* tai *Projector*).
3. **Isadoran ulostulo Spoutiin:**
   - Valitse Isadoran päävalikosta: `Output` → `Stage Setup`.
   - Valitse aktiivinen Stage (esim. Stage 1).
   - Merkitse valinta: **Enable Spout Output** (Lähetysnimi: `IsadoraStage1`).
4. **Kameran valinta kokouksessa:**
   - Avaa Teams tai Google Meet.
   - Valitse kameraksi: **SpoutCam**.
   - SpoutCam nappaa automaattisesti aktiivisen Spout-lähetyksen (`IsadoraStage1`) ja välittää sen kokoukseen ilman viivettä.

---

### Työnkulku C: NDI-videovirta (Network Device Interface)
1. Lisää Isadora 3:een aktori **NDI Broadcaster**.
2. Yhdistä käsitellyn avatarin video-ulostulo `NDI Broadcaster` -aktorin tuloon (esim. Channel Name: `LiveAvatar-NDI`).
3. Asenna ilmainen **NDI Tools for Windows** ([https://ndi.video/tools/](https://ndi.video/tools/)).
4. Käynnistä **NDI Webcam Input** Windowsin ilmaisinalueelle.
5. Klikkaa NDI Webcam -kuvaketta hiiren kakkospainikkeella ja valitse lähteeksi `LiveAvatar-NDI`.
6. Valitse Teamsissa/Meetissä kameraksi **NDI Video**.

---

## 3. Äänen Reititys (Audio Routing — VB-Audio Virtual Cable)

Kokouksessa on välttämätöntä erottaa toisistaan:
1. **Haastattelijan puhe:** Tulee kokouksesta ja toistetaan käyttäjän kuulokkeisiin.
2. **Matin puheenvuoro (Gemini TTS):** Soitetaan suoraan Teamsin/Meetin mikrofonikanavaan ilman huonekaiuntaa tai taustamelua.

### Asennus ja konfigurointi Windows 11:ssä:
1. Lataa ja asenna standardi ilmainen ajuri: [VB-Audio Virtual Cable](https://vb-audio.com/Cable/) (vaatii Windowsin uudelleenkäynnistyksen asennuksen jälkeen).
2. **Äänen toisto selaimesta virtuaalikaapeliin:**
   - Avaa Windows 11 Asetukset: `Järjestelmä` → `Ääni` → `Äänenvoimakkuuden mikseri`.
   - Etsi listasta selain, jolla avatar-avustaja on auki (Google Chrome tai Microsoft Edge).
   - Aseta selaimen **Ulostulolaite (Output)** kohteeksi:
     `CABLE Input (VB-Audio Virtual Cable)`.
3. **Microsoft Teams / Google Meet -mikrofoniasetus:**
   - Avaa Teamsin tai Meetin asetukset: `Laitteet` → `Mikrofoni`.
   - Valitse mikrofoniksi:
     `CABLE Output (VB-Audio Virtual Cable)`.
4. **Tulos:** Kun hyväksyt vastauksen ("Hyväksy ja puhu kokoukseen"), Gemini TTS -ääni siirtyy suoraan korkealaatuisena digitaalisena signaalina kokouksen mikrofoniin.

---

## 4. Testaus- ja Varmistusvaiheet

1. **Yhteystesti:** Avaa `http://localhost/mattikiviharju/` ja klikkaa AI-avatar-painiketta oikeassa alakulmassa.
2. **Tilan valinta:** Varmista, että tilapalkissa on aktiivisena `🎯 Haastattelu (Teams/Meet)`.
3. **Kysymyksen syöttö:** Kirjoita kenttään: *"Kerro kokemuksestasi Atlassian-ratkaisuista ja integraatioista."*
4. **Esikatselun tarkistus:** Varmista, että vastaus ilmestyy esikatselukorttiin tekstinä ensimmäisessä persoonassa ja puhe **ei** ala automaattisesti.
5. **Manuaalinen muokkaus:** Muokkaa jotakin lausetta tekstikentässä.
6. **Hyväksyntä ja puhe:** Paina `✓ Hyväksy ja puhu kokoukseen`.
7. **Huulisynkronointi:** Varmista, että avatarin suu overlay sekä popout-ikkunan (`?gemini_popout=1`) suu liikkuvat puheen tahdissa.
8. **Hätäpysäytys:** Testaa `⏹ Pysäytä` -painiketta puheen aikana; puheen ja suunliikkeen tulee lakata välittömästi.
