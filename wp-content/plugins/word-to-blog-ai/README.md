# Word to Blog AI

WordPress-plugin, joka luo suomenkielisiä blogiartikkeleita Word-tiedostoista OpenAI:n avulla.

## Ominaisuudet

- **Word-tiedoston tuonti**: Tuo suomenkielisiä .docx tai .doc -tiedostoja
- **Kuvien tuonti**: Tuo automaattisesti Word-tiedostoon upotetut kuvat WordPress-mediakirjastoon
- **OpenAI-integraatio**: Käyttää GPT-4o -mallia blogiartikkelien luomiseen
- **Suomenkielinen**: Tuottaa puhtaasti suomenkielistä sisältöä
- **Luonnokset**: Luo artikkelit luonnoksina, jotta voit tarkistaa ne ennen julkaisua

## Asennus

1. Siirry plugin-kansioon:
   ```bash
   cd wp-content/plugins/word-to-blog-ai
   ```

2. Asenna riippuvuudet Composerilla:
   ```bash
   composer install
   ```

3. Aktivoi plugin WordPressin hallintapaneelissa

## Käyttöönotto

1. **OpenAI API-avain**:
   - Mene `Word to Blog AI` -sivulle WordPress-hallinnassa
   - Lisää OpenAI API-avaimesi (hanki [platform.openai.com/api-keys](https://platform.openai.com/api-keys))
   - Tallenna asetukset

2. **Luo blogiartikkeli**:
   - Valitse suomenkielinen Word-tiedosto
   - Klikkaa "Lataa Word-tiedosto"
   - Valitse haluamasi tyyli (ammattimainen, rento, informatiivinen, kiinnostava)
   - Klikkaa "Luo blogiartikkeli AI:lla"
   - Odota hetki kun AI luo artikkelin
   - Klikkaa "Muokkaa artikkelia" tarkastellaksesi ja julkaistaksesi sen

## Tekninen toteutus

- **PHPWord**: Word-tiedostojen lukeminen ja sisällön poimiminen
- **OpenAI API**: GPT-4o -malli blogiartikkelien luomiseen
- **WordPress Media Library**: Kuvien tuonti ja hallinta
- **AJAX**: Asynkroninen tiedostojen käsittely ilman sivun uudelleenlatausta

## Vaatimukset

- PHP >= 7.4
- WordPress >= 5.0
- Composer
- OpenAI API-avain

## Käyttö

Plugin näkyy WordPress-hallinnassa omana sivunaan "Word to Blog AI". Sieltä voit:
1. Konfiguroida OpenAI API-avaimen
2. Ladata Word-tiedostoja
3. Luoda blogiartikkeleita AI:n avulla
4. Siirtyä suoraan muokkaamaan luotuja artikkeleita

## Tekninen rakenne

```
word-to-blog-ai/
├── word-to-blog-ai.php     # Pääplugin-tiedosto
├── composer.json            # Riippuvuudet
├── templates/
│   └── admin-page.php      # Admin-sivun template
├── js/
│   └── admin.js            # Admin JavaScript
└── css/
    └── admin.css           # Admin-tyylit
```

## Tietoturva

- API-avain tallennetaan WordPress-tietokantaan
- Tiedostotyypit validoidaan ennen käsittelyä
- Kaikki käyttäjäsyötteet puhdistetaan
- Vain admin-oikeudet voivat käyttää pluginia

## Lisenssi

Henkilökohtaiseen käyttöön.
