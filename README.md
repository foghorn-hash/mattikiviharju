# CV One Pager Theme

A modern, clean one-page CV/portfolio WordPress theme with AI-powered content management capabilities.

## Features

### Core Theme Features

#### 🎨 Modern Design
- Clean, professional one-page layout
- Dark mode color scheme with gradient backgrounds
- Responsive design for all devices
- Smooth scroll navigation
- Custom color variables (accent colors, backgrounds, borders)

#### 📄 Custom Post Types
The theme includes six specialized custom post types for organizing CV content:

1. **Experience** (`cv_experience`)
   - Job roles and professional experience
   - Date ranges and descriptions
   - Sortable via page attributes

2. **Projects** (`cv_project`)
   - Portfolio projects with descriptions
   - Featured images support
   - Project screenshots/gallery
   - External project links
   - Custom meta information

3. **Education** (`cv_education`)
   - Educational background
   - Date ranges and details
   - Sortable entries

4. **Courses** (`cv_course`)
   - Additional training and certifications
   - Professional development courses

5. **Skills** (`cv_skill`)
   - Technical and soft skills
   - Organized display

6. **Profile Badges** (`cv_badge`)
   - Quick profile highlights
   - Achievement badges

#### 🔧 Advanced Custom Fields (ACF) Integration
Complete ACF field groups for customizing every aspect of the CV:

**Hero Section:**
- Custom headline and intro text
- Primary and secondary call-to-action buttons
- About photo

**Profile Section:**
- Profile title and body text
- GitHub and YouTube profile links
- Repeatable profile badges

**Content Sections:**
- Customizable section titles for all areas
- Experience, education, courses repeater fields
- Projects with meta, descriptions
- Skills repeater
- Navigation labels for each section

**Contact Section:**
- Email and phone
- Business ID (Y-tunnus) and VAT ID
- LinkedIn profile with custom label
- Custom footer text

**Blog Page:**
- Blog title and intro customization
- ACF fields for posts page

#### 🌍 Multilingual Support (Polylang)
- Full Polylang integration
- Translation support for custom post types
- Registered translatable strings
- Multi-language navigation
- Language-specific content management

#### 📝 Blog Functionality
- Custom blog page template (`page-blog.php`)
- AJAX-powered "Load More" posts
- Post cards with excerpts
- Date display
- Responsive blog grid
- WordPress REST API ready

#### 🎯 Front-End Features
- Sticky navigation with smooth scrolling
- Interactive project gallery with lightbox
- Bootstrap Icons integration
- Google Fonts (Inter font family)
- Optimized asset loading
- Mobile-friendly navigation

#### 📱 JavaScript Enhancements
- `nav.js` - Smooth scroll navigation and highlighting
- `gallery.js` - Project screenshot galleries
- `blog.js` - AJAX post loading with jQuery
- `admin-project-gallery.js` - WordPress media library integration

### Custom Plugins

#### 🤖 i4ware® Job Seeker Autopilot AI Life-cycle Management System™
**Location:** `wp-content/plugins/ai-cv-tailor/`

An advanced AI-powered system that automates the entire job seeking lifecycle, from fetching job postings to generating tailored applications.

**Features:**
- Fetches new job postings automatically from external sources (e.g., RSS feeds) using background cron jobs or WP-CLI
- AI-driven job analysis: Analyzes job descriptions to calculate a 0-100% Match Score against your CV data
- Automatically generates role-specific application letters (Cover Letter & Motivation Letter) tailored for HR, CTO, CEO, Team Lead, and Recruiter audiences
- AI-tailored dynamic selection of projects and work experiences that match the job's Tech Stack and requirements
- Generates secure, unique, and private application URLs for each specific audience
- "Force Regenerate" feature to iteratively improve AI output without breaking previously shared application links
- Comprehensive WP-CLI support (`wp ai-cv-tailor autopilot ...`) for cron integration and server automation

**Settings:**
- Configurable minimum Match Score threshold for automatic application generation
- Delivery terms and privacy policy URLs configuration for legal compliance
- OpenAI API key and AI model configuration

#### 🤖 CV OpenAI Polylang Translator
**Location:** `wp-content/plugins/cv-openai-polylang-translator/`

Automatically translates custom post types using OpenAI's GPT models:

**Features:**
- Manual translation triggers for custom post types
- OpenAI GPT-4/GPT-4o integration
- Translates from Finnish to English and Swedish
- Preserves HTML formatting
- Customizable translation prompts
- Supports title, excerpt, and content translation
- Works with Polylang language system

**Settings:**
- Configurable OpenAI API key
- Model selection (GPT-4o-mini, GPT-4o, etc.)
- Choose which post types to translate
- Custom translation prompt templates

#### 📄 CV PDF to ACF Filler
**Location:** `wp-content/plugins/cv-pdf-to-acf-filler/`

Extract CV data from PDF files and automatically populate ACF fields:

**Features:**
- Upload PDF CV files through WordPress admin
- OpenAI-powered PDF content extraction
- Automatic translation to Finnish, English, and Swedish
- Populates ACF fields on home pages for each language
- Overwrites existing content with fresh data
- Composer dependencies support
- Polylang integration for multi-language content

**Capabilities:**
- Intelligent CV parsing from PDF format
- Structured data extraction
- Multi-language content generation
- Direct ACF field population
- Requires ACF and Polylang plugins

**Settings:**
- OpenAI API key configuration
- Model selection (gpt-4o, gpt-4o-mini, gpt-4-turbo)
- Simple upload interface

#### 📝 Word to Blog AI
**Location:** `wp-content/plugins/word-to-blog-ai/`

Generate Finnish draft blog posts from Word documents with OpenAI, including AI-generated images based on article text context.

**Features:**
- Upload `.docx` / `.doc` files from WordPress admin
- Extract text from Word documents for AI processing
- Generate blog title + HTML content with OpenAI (`gpt-4o`)
- Automatically generate **1–4 AI images** from article title/content context (`gpt-image-1`)
- Import generated images to WordPress media library
- Auto-embed generated images inside article content
- Save result as a WordPress draft for editorial review

**Notes:**
- Requires OpenAI API key configuration in plugin settings
- Uses the generated article text snippet to guide image prompts
- Images are generated without logos/watermarks/text overlays by prompt design

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Composer (for plugin dependencies)
- **Required Plugins:**
  - Advanced Custom Fields (ACF) or ACF Pro
  - Polylang (for multilingual support)

## Installation

1. Upload the theme to `/wp-content/themes/`
2. Install and activate required plugins:
   - Advanced Custom Fields
   - Polylang
3. Install custom plugins:
   - Upload `ai-cv-tailor` to `/wp-content/plugins/`
   - Upload `cv-openai-polylang-translator` to `/wp-content/plugins/`
   - Upload `cv-pdf-to-acf-filler` to `/wp-content/plugins/`
   - Upload `word-to-blog-ai` to `/wp-content/plugins/`
   - Activate the custom plugins
   - Run `composer install` inside plugin folders that include `composer.json`
4. Activate the CV One Pager theme
5. Configure Polylang languages (Finnish, English, Swedish recommended)
6. Set up your front page as a static page
7. Configure OpenAI API keys in plugin settings

## Configuration

### Setting Up Languages
1. Go to **Languages** in WordPress admin
2. Add Finnish (fi), English (en), and Swedish (sv)
3. Set Finnish as default language
4. Create translated versions of your home page

### Configuring OpenAI Plugins
1. **CV PDF to ACF Filler:**
   - Settings → CV PDF to ACF
   - Enter your OpenAI API key
   - Select model (recommended: gpt-4o)
   - Upload CV PDF file

2. **CV OpenAI Polylang Translator:**
   - Settings → OpenAI Polylang Translator
   - Enter OpenAI API key
   - Select model
   - Choose post types to translate
   - Customize translation prompt if needed

3. **Word to Blog AI:**
   - Word to Blog AI → plugin admin page
   - Enter OpenAI API key
   - Upload a `.docx` or `.doc` file
   - Generate blog post draft with AI
   - Plugin also generates and embeds 1–4 AI images based on article text context

### Theme Customization
- Navigate to your front page in edit mode
- Fill in ACF fields for each section
- Upload profile photo
- Add experience, education, projects, etc.
- Configure CTAs and contact information
- Repeat for each language version

## File Structure

```
├── comments.php
├── footer.php
├── front-page.php
├── functions.php
├── header.php
├── home.php
├── index.php
├── page-blog.php
├── page.php
├── single.php
├── style.css
├── template-cv.php
├── acf-json/
│   └── group_cv_one_pager.json
├── js/
│   ├── admin-project-gallery.js
│   ├── blog.js
│   ├── gallery.js
│   └── nav.js
└── wp-content/
    └── plugins/
        ├── ai-cv-tailor/
        ├── cv-openai-polylang-translator/
        ├── cv-pdf-to-acf-filler/
        └── word-to-blog-ai/
```

## Theme Support

- Title tag
- Post thumbnails
- Custom logo (240x80, flexible)
- Custom menus
- REST API

## Hooks and Filters

### Actions
- `cv_one_pager_setup` - Theme setup
- `cv_one_pager_register_post_types` - Register custom post types
- `cv_one_pager_assets` - Enqueue scripts and styles
- `cv_one_pager_register_polylang_strings` - Register translatable strings
- `cv_one_pager_add_meta_boxes` - Add custom meta boxes
- `cv_load_more_posts` - AJAX handler for blog posts

### Filters
- `pll_get_post_types` - Filter Polylang post types

### Functions
- `cv_one_pager_t($string)` - Translate string with Polylang
- `cv_one_pager_front_page_id()` - Get language-specific front page ID
- `cv_one_pager_render_post_card($post)` - Render blog post card

## Development

### Color Customization
Edit CSS variables in [style.css](style.css):
```css
:root {
  --bg: #0f172a;
  --surface: #111827;
  --card: #0b1220;
  --text: #e5e7eb;
  --muted: #9ca3af;
  --accent: #38bdf8;
  --accent-2: #22c55e;
  --accent-3: #fa4fa7;
  --border: rgba(148, 163, 184, 0.2);
}
```

## License

GNU General Public License v2 or later

## Credits

- **Author:** GitHub Copilot/i4ware Software
- **Theme URI:** https://www.i4ware.fi/
- **Version:** 1.0.0

## Support

For issues and feature requests, please refer to the theme documentation or contact the theme author.
