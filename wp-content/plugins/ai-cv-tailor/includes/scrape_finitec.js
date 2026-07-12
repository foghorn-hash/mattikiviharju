const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
  const outputPath = process.argv[2] || 'finitec_gigs.json';
  console.log('Launching browser...');
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  let gigsData = null;

  page.on('response', async response => {
    const url = response.url();
    if (url.includes('api/general')) {
      try {
        const text = await response.text();
        gigsData = JSON.parse(text);
      } catch (e) {
        console.error('Error parsing response:', e.message);
      }
    }
  });

  console.log('Navigating to gigs page...');
  try {
    await page.goto('https://www.finitec.fi/gigs', { waitUntil: 'networkidle', timeout: 30000 });
  } catch (err) {
    console.error('Navigation error:', err.message);
  }
  
  await browser.close();

  if (gigsData) {
    fs.writeFileSync(outputPath, JSON.stringify(gigsData, null, 2), 'utf8');
    console.log('Saved gigs to:', outputPath);
  } else {
    console.error('Failed to intercept gigs data.');
    process.exit(1);
  }
})();
