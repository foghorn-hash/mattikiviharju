# AI CV Tailor - Autopilot Crontab & Automatisointiohje

Tämä ohje neuvoo, miten laitat työnhaun tekoälyautomaation (Autopilot) pyörimään palvelimen taustalle ajastettuna tehtävänä (crontab).

---

## 1. Suositeltu Crontab-rivi (Linux-palvelin)

Avaa palvelimen crontab komennolla:
```bash
crontab -e
```

### Vaihtoehto A: Käyttäen valmista shell-skriptiä (Paras ja turvallisin)
Skripti sisältää lukituksen (`flock`), jotta päällekkäiset ajot eivät sotke toisiaan, sekä selkeän vaiheittaisen lokituksen.

```cron
# Aja joka aamu klo 07:00
0 7 * * * /var/www/html/wp-content/plugins/ai-cv-tailor/bin/cron-autopilot.sh /var/www/html >> /var/www/html/wp-content/uploads/autopilot-cron.log 2>&1
```

Muista antaa skriptille suoritusoikeudet:
```bash
chmod +x /var/www/html/wp-content/plugins/ai-cv-tailor/bin/cron-autopilot.sh
```

---

### Vaihtoehto B: Suora WP-CLI komento rivillä

#### 1) Yhdistetty komento (suorittaa koko syklin: fetch -> analyze -> generate):
```cron
0 7 * * * cd /var/www/html && wp ai-cv-tailor autopilot run-all >> /var/www/html/wp-content/uploads/autopilot-cron.log 2>&1
```

#### 2) Erillisinä askeleina:
```cron
0 7 * * * cd /var/www/html && wp ai-cv-tailor autopilot fetch && wp ai-cv-tailor autopilot analyze && wp ai-cv-tailor autopilot generate-applications >> /var/www/html/wp-content/uploads/autopilot-cron.log 2>&1
```

---

## 2. Useamman ajon aikataulutus (esim. 4 kertaa päivässä)

Jos haluat hakea toimeksiantoja useamman kerran päivässä arkisin (klo 07, 11, 14 ja 17):

```cron
0 7,11,14,17 * * 1-5 /var/www/html/wp-content/plugins/ai-cv-tailor/bin/cron-autopilot.sh /var/www/html >> /var/www/html/wp-content/uploads/autopilot-cron.log 2>&1
```

---

## 3. cPanel / Webhotelli -ajastus

Jos käytät cPanelia tai muuta webhotellin hallintapaneelia:
1. Mene kohtaan **Cron Jobs** / **Ajastetut tehtävät**.
2. Aseta aikatauluksi esim. `0 7 * * *` (Kerran päivässä klo 07:00).
3. Komentokenttään:
```bash
/usr/local/bin/wp ai-cv-tailor autopilot run-all --path=/home/kayttaja/public_html
```

---

## 4. Windows / Paikallinen kehitysympäristö

Voit ajaa täyden kierroksen PowerShellissä:
```powershell
.\wp-content\plugins\ai-cv-tailor\bin\cron-autopilot.ps1
```
Tai WP-CLI:llä:
```powershell
wp ai-cv-tailor autopilot run-all
```

---

## 5. Komentojen yhteenveto

| Komento | Kuvaus |
|---|---|
| `wp ai-cv-tailor autopilot fetch` | Hakee uudet toimeksiannot määritetyistä lähteistä (RSS, Työmarkkinatori jne.) |
| `wp ai-cv-tailor autopilot analyze` | Analysoi uudet ilmoitukset OpenAI:lla ja pisteyttää ne suhteessa CV:hen |
| `wp ai-cv-tailor autopilot generate-applications` | Luo automaattisesti räätälöidyt hakemukset ja CV-linkit matcheille |
| `wp ai-cv-tailor autopilot run-all` tai `cron` | Suorittaa kaikki yllä olevat kolme vaihetta peräkkäin yhdellä komennolla |
| `wp ai-cv-tailor autopilot run-debug` | Ajaa täyden diagnostiikkatestin ja tulostaa lokitiedot |
| `wp ai-cv-tailor autopilot list-jobs` | Listaa toimeksiannot ja niiden pisteytykset |
| `wp ai-cv-tailor autopilot list-applications` | Listaa luodut hakemukset |
