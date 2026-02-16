# Quick Setup Guide - CV PDF to ACF Filler

## Why PDF Import Isn't Working

The plugin requires a PDF parser library to extract text from PDF files. Without it, the plugin cannot read your CV PDF.

## Fix the Issue (Choose One Method)

### Method 1: Install Composer Package (Recommended)

This is the most reliable method for PDF text extraction.

**Windows PowerShell:**
```powershell
# Navigate to plugin directory
cd wp-content\plugins\cv-pdf-to-acf-filler

# Install dependencies using composer
composer install
```

**Linux/Mac Terminal:**
```bash
cd wp-content/plugins/cv-pdf-to-acf-filler
composer install
```

**Don't have Composer?** Download it from [getcomposer.org](https://getcomposer.org/)

### Method 2: Install pdftotext Command-Line Tool

**Windows:**
1. Download Xpdf tools from [xpdfreader.com/download.html](https://www.xpdfreader.com/download.html)
2. Extract and add to your system PATH

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install poppler-utils
```

**macOS:**
```bash
brew install poppler
```

## Verify Installation

After installing, check if it works:

1. Go to your WordPress admin
2. Navigate to **Settings > CV PDF to ACF**
3. Check the **Status** section:
   - **PDF Parser** should now show: ✓ Smalot PDF Parser (Composer) or ✓ pdftotext (Command-line)
4. All 4 status items should show ✓ (checkmark)

## Upload Your PDF

Once the status checks pass:

1. Scroll down to **Upload CV PDF** section
2. Select your PDF file
3. Choose source language (Finnish or English)
4. Click **Upload and Process PDF**

The plugin will:
- Extract CV data from your PDF
- Populate the source language home page
- Translate to the other two languages
- Update all three home pages (Fi, En, Sv)

## Still Having Issues?

### Check Your PDF
- Must contain selectable text (try copying text from the PDF)
- Cannot be a scanned image
- Must not be password protected

### Check OpenAI API
- Ensure your API key is entered in Settings
- Verify you have available API credits
- Model should be set to gpt-4o, gpt-4o-mini, or gpt-4-turbo

### Check Page Setup
- You must have a front page set in WordPress (Settings > Reading)
- All three language versions must exist (Fi, En, Sv)
- Polylang must be active and configured

## Error Messages

The plugin now shows detailed error messages that indicate exactly what's wrong:

- **"No PDF parsing library found"** → Run `composer install` in the plugin directory
- **"Polylang is not active"** → Install and activate Polylang plugin
- **"ACF is not active"** → Install and activate Advanced Custom Fields plugin
- **"Missing home page translations"** → Create home pages for all three languages in Polylang
- **"PDF contains no readable text"** → Your PDF may be an image/scan, not actual text

Need more help? Check the full README.md file in this directory.
