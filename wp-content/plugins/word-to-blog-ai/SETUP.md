# Word to Blog AI - Asennusohjeet

## Nopea aloitus

### 1. Asenna riippuvuudet

Avaa PowerShell ja suorita:

```powershell
cd c:\Users\matti\git\mattikiviharju\wp-content\plugins\word-to-blog-ai
composer install
```

### 2. Aktivoi plugin WordPressissä

1. Kirjaudu WordPress-hallintapaneeliin
2. Mene kohtaan **Lisäosat** (Plugins)
3. Etsi **Word to Blog AI**
4. Klikkaa **Aktivoi**

### 3. Lisää OpenAI API-avain

1. Hanki API-avain osoitteesta: https://platform.openai.com/api-keys
2. Mene WordPress-hallinnassa kohtaan **Word to Blog AI**
3. Liitä API-avain kenttään
4. Klikkaa **Tallenna API-avain**

### 4. Luo ensimmäinen blogiartikkelisi

1. Valitse suomenkielinen Word-tiedosto (.docx tai .doc)
2. Klikkaa **Lataa Word-tiedosto**
3. Odota, että tiedosto käsitellään
4. Valitse haluamasi tyyli:
   - **Ammattimainen** - muodollinen ja asiallinen
   - **Rento** - keskusteleva ja helppolukuinen
   - **Informatiivinen** - faktapohjainen ja selkeä
   - **Kiinnostava** - mukaansatempaava ja viihdyttävä
5. Klikkaa **Luo blogiartikkeli AI:lla**
6. Odota 10-30 sekuntia (AI käsittelee sisältöä)
7. Klikkaa **Muokkaa artikkelia** avataksesi blogipostauksen editorissa

## Mitä plugin tekee?

1. **Lukee Word-tiedoston** - Purkaa tekstin ja kuvat Word-dokumentista
2. **Lähettää OpenAI:lle** - GPT-4o -malli analysoi sisällön
3. **Luo blogiartikkelin** - AI tuottaa HTML-muotoisen artikkelin otsikolla
4. **Tuo kuvat** - Siirtää kuvat WordPress-mediakirjastoon
5. **Tallentaa luonnoksen** - Luo blogipostauksen "Luonnos"-tilassa

## Tärkeää tietää

- ✅ Artikkeli luodaan aina **luonnoksena** - se ei julkaistu automaattisesti
- ✅ Voit muokata AI:n luomaa sisältöä WordPress-editorissa
- ✅ Kuvat tallennetaan automaattisesti mediakirjastoon
- ✅ Käyttää uusinta GPT-4o -mallia parhaaseen laatuun
- ⚠️ OpenAI API veloittaa käytöstä - tarkista hinnoittelu
- ⚠️ Word-tiedoston tulee olla suomenkielinen

## Vianmääritys

### "PHPWord library not found"
Suorita: `composer install` plugin-kansiossa

### "OpenAI API key not configured"
Lisää API-avain plugin-asetuksissa

### "Invalid file type"
Tarkista että tiedosto on .docx tai .doc -muodossa

### Artikkeli ei ole hyvälaatuinen
Kokeile eri tyyliä tai muokkaa Word-tiedostoa selkeämmäksi

## Tekninen tuki

Tarkista:
1. PHP-versio >= 7.4
2. WordPress-versio >= 5.0
3. Composer on asennettu
4. OpenAI API-avain on voimassa

## Kustannukset

OpenAI API veloittaa käytön mukaan:
- GPT-4o: ~$0.005 per 1000 tokenia (input)
- Keskimääräinen blogiartikkeli: $0.02-0.10

Voit seurata käyttöäsi: https://platform.openai.com/usage
