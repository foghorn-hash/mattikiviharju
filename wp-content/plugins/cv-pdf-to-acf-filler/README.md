# CV PDF to ACF Filler

WordPress plugin that extracts CV data from PDF files using OpenAI, translates to all languages (Fi, En, Sv), and automatically populates Advanced Custom Fields (ACF) on home pages. **Existing content will be overwritten.**

> ⚠️ **IMPORTANT**: This plugin automatically targets Home pages in all three languages (Fi, En, Sv) and **overwrites all existing CV content**. There is no option to select pages or skip existing data. Use with caution!

## Features

- Upload PDF CV files through WordPress admin (Finnish or English)
- Automatically extract structured CV data using OpenAI
- **Translate to all three languages** (Fi, En, Sv) using OpenAI
- Populate ACF fields on Home pages in all languages
- **Overwrites existing content** - truncates and replaces all CV data
- Support for:
  - Profile badges
  - Experience items (role, dates, summary)
  - Projects (title, meta, description)
  - Skills

## Requirements

- WordPress 5.0 or higher
- Advanced Custom Fields (ACF) plugin
- **Polylang** plugin for multi-language support
- OpenAI API key (GPT-4o, GPT-4o-mini, or GPT-4-turbo)
- PHP 7.4 or higher
- PDF text extraction (one of the following):
  - **Recommended:** Composer package `smalot/pdfparser`
  - **Alternative:** System `pdftotext` command line tool
- Home page translations set up in Polylang (Fi, En, Sv)

## Installation

1. Copy the `cv-pdf-to-acf-filler` folder to your WordPress `wp-content/plugins/` directory

2. **IMPORTANT:** Install PDF parser library (choose one method):
   
   **Method A: Using Composer (Recommended for best results)**
   ```bash
   # Navigate to the plugin directory
   cd wp-content/plugins/cv-pdf-to-acf-filler
   
   # Install dependencies
   composer install
   ```
   
   This will install the `smalot/pdfparser` package which provides the most reliable PDF text extraction.
   
   **Method B: Using pdftotext command (Alternative)**
   - Ubuntu/Debian: `sudo apt-get install poppler-utils`
   - macOS: `brew install poppler`
   - Windows: Download from [Xpdf tools](https://www.xpdfreader.com/download.html)
   
   > **Note:** Without a PDF parser, the plugin **will not work**. You must install at least one of these options.

3. Activate the plugin in WordPress admin (Plugins > Installed Plugins)

4. Install and activate **Advanced Custom Fields (ACF)** if not already installed

5. Install and activate **Polylang** if not already installed

6. Set up Polylang languages (Finnish, English, Swedish) and create home page translations for all three languages

## Troubleshooting

### PDF Import Not Working

If the PDF import is failing, check the following:

1. **Check Plugin Status** - Go to Settings > CV PDF to ACF and check the "Status" section:
   - ACF Plugin: Must show "✓ Active"
   - Polylang Plugin: Must show "✓ Active"  
   - PDF Parser: Must show "✓ Smalot PDF Parser (Composer)" or "✓ pdftotext (Command-line)"
   - OpenAI API Key: Must show "✓ Configured"

2. **Install PDF Parser** - If PDF Parser shows "✗ Not available":
   ```bash
   cd wp-content/plugins/cv-pdf-to-acf-filler
   composer install
   ```

3. **Check PDF Quality** - Ensure your PDF:
   - Contains selectable text (not scanned images)
   - Is not password protected
   - Is a valid PDF file

4. **Check Error Messages** - The plugin now shows detailed error messages that will help identify the issue

5. **API Key** - Verify your OpenAI API key is correct and has sufficient credits

## Configuration

1. **Set up Polylang** (if not already done):
   - Install and activate Polylang plugin
   - Configure languages: Finnish (fi), English (en), Swedish (sv)
   - Create home page translations for all three languages
   - Set your front page in Settings > Reading

2. Go to **Settings > CV PDF to ACF** in WordPress admin
3. Enter your OpenAI API Key
4. Select the model (default: `gpt-4o`)
5. Click **Save Settings**

## Usage

1. Navigate to **Settings > CV PDF to ACF**
2. Verify that all three home page versions are detected (Fi, En, Sv)
3. Under "Upload CV PDF" section:
   - Choose your PDF CV file
   - Select source language:
     - **Finnish**: Populates Fi directly, translates to En & Sv
     - **English**: Populates En directly, translates to Fi & Sv
   - Click **Upload and Process PDF**
4. The plugin will:
   - Extract CV data from the PDF using OpenAI
   - Populate source language home page
   - Translate to the other two languages
   - Populate all three home pages (Fi, En, Sv)
   - **Overwrite all existing CV data**

## ACF Field Structure

The plugin expects the following ACF field structure (as defined in `acf-json/group_cv_one_pager.json`):

- `cv_profile_badges` - Repeater field with `label` subfield
- `cv_experience_title` - Text field for section title
- `cv_experience_items` - Repeater with `role`, `dates`, `summary` subfields
- `cv_projects_title` - Text field for section title
- `cv_projects` - Repeater with `title`, `meta`, `description` subfields
- `cv_skills_title` - Text field for section title
- `cv_skills` - Repeater field with `label` subfield

## How It Works

1. **PDF Upload**: User uploads a PDF CV file (Finnish or English) through the admin interface
2. **Text Extraction**: PDF text is extracted using smalot/pdfparser or pdftotext
3. **OpenAI Processing**: Extracted text is sent to OpenAI GPT for structured data extraction
4. **Source Population**: Extracted data populates the source language home page
5. **Translation**: Data is translated to the other two languages using OpenAI
6. **Multi-language Population**: All three home pages (Fi, En, Sv) are updated
7. **Overwrite Mode**: All existing CV data is replaced with new content

## Notes

- The plugin **always populates all three language versions**: Home (Fi), Home (En), Home (Sv)
- **Existing content is overwritten** - all CV data will be replaced when you upload a new PDF
- Select the source language that matches your PDF (Finnish or English)
- The plugin automatically translates to the other two languages
- Processing time: ~15-30 seconds (extraction + 2 translations)
- PDF files are temporarily stored during processing and deleted immediately after
- Works with any GPT model, but GPT-4o/GPT-4o-mini provides best results
- Requires Polylang to be active with all three languages configured

## Troubleshooting

### "ACF is not active" error
Make sure Advanced Custom Fields plugin is installed and activated.

### "Polylang is not active" error
Install and activate Polylang plugin. This is required for multi-language support.

### "Missing home page translations" error
- Ensure you have a front page set (Settings > Reading)
- Create translations for your front page in all three languages (Fi, En, Sv) using Polylang
- Check that Polylang is properly configured with Finnish, English, and Swedish

### "Missing OpenAI API key" error
Configure your OpenAI API key in Settings > CV PDF to ACF.

### "Unable to extract text from PDF" error
Install a PDF parser:
- Run `composer require smalot/pdfparser` in the plugin directory, OR
- Install pdftotext: `sudo apt-get install poppler-utils` (Linux) or `brew install poppler` (macOS)

### PDF extraction fails
- Check that your PDF is readable (not scanned image-only PDF)
- Verify the PDF is not password-protected
- Ensure PDF text extraction is working (see above)

### OpenAI extraction fails
- Ensure you're using a valid model (gpt-4o, gpt-4o-mini, or gpt-4-turbo)
- Check that your OpenAI API key is valid and has sufficient credits
- Verify API key has access to the selected model

### Translation fails
- The plugin requires successful API calls for extraction + 2 translations (En & Sv or Fi & Sv)
- Make sure you have sufficient API credits
- If one translation fails, the page will show an error message but other languages will still be populated

## Security

- File uploads are validated for PDF type
- User permissions checked (requires `manage_options` capability)
- Nonce verification for form submissions
- Data sanitized before saving to database
- Temporary files deleted after processing

## License

This plugin is provided as-is for use with the CV OnePager WordPress theme.

## Changelog

### Version 2.0.0 (Current)
- **BREAKING CHANGE**: Removed page selection - now automatically targets Home pages only
- **BREAKING CHANGE**: Changed from "skip existing" to "overwrite" mode - all CV data is replaced
- Automatic multi-language support: Populates Fi, En, and Sv home pages simultaneously
- Source language selection: Choose between Finnish or English PDF
- Auto-translation to all three languages using OpenAI
- Requires Polylang plugin for multi-language functionality
- Added validation for Polylang and home page translations

### Version 1.0.0
- Initial release
- PDF extraction and ACF population
- Optional English to Finnish translation
- Manual page selection
- Skip existing content mode
