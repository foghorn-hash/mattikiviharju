# CV One Pager Theme & AI Automation Platform

A modern, high-performance one-page CV and portfolio WordPress theme paired with an advanced suite of AI-powered plugins for content management, automated job applications, live interview assistance, SEO optimization, talking avatars, and multilingual localization.

---

## Table of Contents

- [Overview](#overview)
- [Core Theme Features](#core-theme-features)
  - [Modern Aesthetic & Design System](#modern-aesthetic--design-system)
  - [Custom Post Types (CPTs)](#custom-post-types-cpts)
  - [Smart Chronological Sorting for Experience](#smart-chronological-sorting-for-experience)
  - [Client & Colleague References System](#client--colleague-references-system)
  - [Advanced Custom Fields (ACF) Integration](#advanced-custom-fields-acf-integration)
  - [Interactive Project Gallery & Lightbox](#interactive-project-gallery--lightbox)
  - [Multilingual Architecture (Polylang)](#multilingual-architecture-polylang)
  - [SEO & Open Graph Meta Tags](#seo--open-graph-meta-tags)
  - [Performance & Cookie Consent Guard](#performance--cookie-consent-guard)
  - [Modern Blog System with AJAX Pagination](#modern-blog-system-with-ajax-pagination)
  - [Instant CV Export (PDF & Word DOCX)](#instant-cv-export-pdf--word-docx)
- [WordPress Plugins Suite](#wordpress-plugins-suite)
  - [1. i4ware® Job Seeker Autopilot AI & Interview Assistant](#1-i4ware-job-seeker-autopilot-ai--interview-assistant)
  - [2. Gemini AI Avatar Assistant](#2-gemini-ai-avatar-assistant)
  - [3. i4ware AI SEO Agent](#3-i4ware-ai-seo-agent)
  - [4. CV PDF & Word (.docx) to ACF Filler](#4-cv-pdf--word-docx-to-acf-filler)
  - [5. i4ware CV OpenAI Polylang Translator (Enterprise Edition)](#5-i4ware-cv-openai-polylang-translator-enterprise-edition)
  - [6. CV OpenAI Polylang Translator (Core Edition)](#6-cv-openai-polylang-translator-core-edition)
  - [7. Word to Blog AI](#7-word-to-blog-ai)
  - [8. WP Reddit Autopost](#8-wp-reddit-autopost)
- [Companion Applications](#companion-applications)
- [Repository File Structure](#repository-file-structure)
- [Requirements & Installation](#requirements--installation)
- [Developer Reference & Hooks](#developer-reference--hooks)
- [License & Credits](#license--credits)

---

## Overview

The repository unites a bespoke WordPress theme designed specifically for technology professionals, senior engineers, and consultants with eight specialized WordPress plugins that automate everything from portfolio ingestion to automated job application pipelines, real-time interview coaching, conversational AI avatars, and multi-channel content syndication.

---

## Core Theme Features

The theme (`mattikiviharju`) is built from scratch without bulky framework overhead, prioritizing blazing-fast load times, dark mode aesthetics, clean typography, and seamless interaction.

### Modern Aesthetic & Design System
- **Dark Mode Color Palette**: Hand-crafted dark theme utilizing modern CSS variables for rapid branding adjustments:
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
- **Typography & Icons**: Built-in Google Fonts integration with the **Inter** font family (weights 400, 500, 600, 700) and **Bootstrap Icons** (`1.11.3`).
- **Responsive Layout**: Fluid CSS Grid and Flexbox layouts adapting gracefully across mobile devices, tablets, ultra-wide desktop monitors, and print styles.
- **Smooth Navigation (`nav.js`)**: Sticky header bar with dynamic section spy, smooth scrolling to anchor links, and automatic active link highlights.

### Custom Post Types (CPTs)
The theme registers seven specialized custom post types tailored specifically for CV and portfolio management in [`functions.php`](file:///c:/Users/matti/git/mattikiviharju/functions.php):

1. **Experience** (`cv_experience`):
   - Professional roles, employer details, and summary descriptions.
   - Built-in meta fields for Employer (`cv_exp_employer`), Role (`cv_exp_role`), Date Range (`cv_exp_dates`), and Key Responsibilities/Achievements (`cv_exp_summary`).
2. **Projects** (`cv_project`):
   - Portfolio works showcasing featured thumbnails, external website/repository URLs, tech stack metadata (`cv_project_meta`), multiple YouTube video embeds, and multiple screenshot gallery images.
3. **Education** (`cv_education`):
   - Academic degrees, institutions, and dates (`cv_edu_school`, `cv_edu_degree`, `cv_edu_dates`, `cv_edu_details`).
4. **Courses & Certifications** (`cv_course`):
   - Ongoing training and certifications with provider and date fields (`cv_course_provider`, `cv_course_dates`, `cv_course_details`).
5. **Skills** (`cv_skill`):
   - Categorized technical and soft skills, tools, and methodologies with custom ordering.
6. **Profile Badges** (`cv_badge`):
   - Quick achievement badges and profile highlights rendered in the hero snapshot section.
7. **References / Testimonials** (`cv_reference`):
   - Client and colleague recommendations with fields for reference giver name, job title, company, email, direct phone number, and quote body.

### Smart Chronological Sorting for Experience
- **Date Parser Engine**: Includes [`cv_one_pager_parse_cv_dates()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L2066) and [`cv_one_pager_compare_cv_dates()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L2121), which parse complex natural-language date ranges in Finnish and English (e.g., `03/2021 - Present`, `2018 - 2020`, `Tammikuu 2022 - Nykyhetki`).
- **Automatic Menu Order Synchronization**: Automatically calculates and updates the WordPress `menu_order` property whenever an experience entry is created or updated, ensuring the most recent position is always on top.
- **Admin Columns & Bulk Resorter**: Adds custom columns for "Aikaväli / Dates" and "Järjestys / Order" in the WordPress admin list table, and provides an admin notice with a one-click button to recalculate and resort all experience items site-wide.

### Client & Colleague References System
- **Pre-seeded Multilingual Testimonials**: Automatically seeds localized default references in Finnish, English, and Swedish upon first load via [`cv_one_pager_seed_default_references()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L429).
- **Dedicated Administration Box**: Custom meta box allowing direct entry of reference contact details (email and telephone with sanitized `tel:` and `mailto:` links) and verification credentials.

### Advanced Custom Fields (ACF) Integration
- **Code-Registered Field Groups**: Programmatically registered directly in PHP via [`cv_one_pager_register_acf_fields()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L1479) and synced with JSON files in [`acf-json/group_cv_one_pager.json`](file:///c:/Users/matti/git/mattikiviharju/acf-json/group_cv_one_pager.json).
- **Comprehensive Front Page Controls**:
  - **Hero Section**: Headline, introduction paragraph, primary and secondary CTA buttons and URLs, and profile portrait upload.
  - **Profile Section**: Snapshot headline, bio narrative, GitHub URL, and YouTube profile link.
  - **Section Titles**: Localizable section header overrides for Experience, Education, Courses, Projects, Skills, References, and Contact.
  - **Contact Section**: Email, phone, Finnish Business ID (*Y-tunnus*), VAT number (*ALV-rek. nro*), LinkedIn URL and custom button label.

### Interactive Project Gallery & Lightbox
- **WordPress Media Uploader**: Interactive multi-image uploader inside the project editor (`admin-project-gallery.js`), letting administrators select multiple screenshots directly from the WordPress Media Library.
- **Responsive Lightbox Overlay (`gallery.js`)**: Clickable thumbnail gallery on the front end with full-screen lightbox modal, previous/next controls, keyboard navigation (Arrow keys and Escape), and smooth transitions.
- **Embedded YouTube Video Player**: Supports multiple YouTube links per project, featuring custom video play overlays that launch embedded YouTube iframes inside a pop-up modal.

### Multilingual Architecture (Polylang)
- **Native Polylang Integration**: Deep integration with Polylang for Finnish (`fi`), English (`en`), and Swedish (`sv`).
- **Translation Helper**: Centralized [`cv_one_pager_t()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L344) helper for UI strings with built-in translation dictionaries.
- **Translatable String Registry**: Automatically registers theme strings with Polylang via [`cv_one_pager_register_polylang_strings()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L501).
- **Language Switcher**: Floating/header language switcher with active language flags and seamless cross-page redirection.

### SEO & Open Graph Meta Tags
- **Dynamic Meta Generator**: Automatically generates `<meta>` tags for Open Graph (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`) and Twitter Cards (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) via [`cv_one_pager_output_og_meta_tags()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L683).
- **Intelligent Fallbacks**: Automatically falls back to the custom ACF profile image, featured image, or site logo, with language-specific descriptions.

### Performance & Cookie Consent Guard
- **Cookie Consent Flash Guard**: Inline script execution in [`header.php`](file:///c:/Users/matti/git/mattikiviharju/header.php) via [`cv_one_pager_cookie_consent_flash_guard_script()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L651) that inspects local storage and cookies to prevent flashing unstyled consent banners before page rendering.
- **Zero Asset Bloat**: Enqueues scripts conditionally only on templates where they are needed (e.g., project gallery scripts load strictly on the front page and CV template).

### Modern Blog System with AJAX Pagination
- **Custom Templates**: Includes dedicated blog archive page (`page-blog.php`), home feed (`home.php`), and single post display (`single.php`).
- **AJAX "Load More" Engine (`blog.js`)**: Asynchronous post loading through WordPress AJAX (`wp_ajax_cv_load_more_posts`), appending styled post cards with excerpts, reading times, and dates without reloading the page.

### Instant CV Export (PDF & Word DOCX)
- **Frontend Action Triggers**: Instant download buttons located in the hero and navigation sections:
  - `?cv_export_pdf=1`: Generates or streams a PDF export of the curriculum vitae.
  - `?cv_export_docx=1`: Generates a formatted Microsoft Word (`.docx`) file.

---

## WordPress Plugins Suite

The project includes eight custom-engineered plugins located in `wp-content/plugins/`:

```
wp-content/plugins/
├── ai-cv-tailor/                          # Job Seeker Autopilot AI & Live Interview Assistant
├── gemini-avatar-assistant/               # Talking AI Avatar & Voice Assistant (Google Gemini)
├── i4ware-ai-seo-agent/                   # AI SEO Analyzer, Metadata Generator & Bulk Optimizer
├── cv-pdf-to-acf-filler/                  # PDF & DOCX Parser, AI Translator & ACF CPT Populator
├── i4ware-cv-openai-polylang-translator/  # Enterprise Safe Single-Post Translator & Locking
├── cv-openai-polylang-translator/         # Core OpenAI Polylang Translator
├── word-to-blog-ai/                       # Word (.docx) to Blog Post Generator with AI Images
└── wp-reddit-autopost/                    # Automated Reddit Subreddit Publisher
```

---

### 1. i4ware® Job Seeker Autopilot AI & Interview Assistant
- **Directory**: [`wp-content/plugins/ai-cv-tailor/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/ai-cv-tailor/)
- **Plugin Name**: `i4ware® Job Seeker Autopilot AI Life-cycle Management System™`
- **Main File**: [`ai-cv-tailor.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/ai-cv-tailor/ai-cv-tailor.php)

#### What It Does
An enterprise-grade automation system that manages the complete job-seeking lifecycle:
1. Automatically crawls and fetches open positions from external RSS feeds, career sites, and job boards (e.g., Finitec, Duunitori).
2. Performs AI-driven comparative job analysis, computing an objective 0–100% Match Score against the applicant's CV database.
3. Automatically synthesizes audience-tailored cover and motivation letters specifically adapted for distinct stakeholders: **HR / Recruiter**, **CTO / Technical Lead**, and **CEO / Executive**.
4. Dynamically curates matching portfolio projects and work experiences aligned with the job posting's tech stack.
5. Generates private, secure application tokens and landing URLs for each target recipient.
6. Houses a **Live Interview Assistant** that pairs with desktop and browser applications to deliver real-time answer suggestions during live job interviews.
7. Automatically delivers application emails via built-in mailers and records comprehensive delivery analytics.

#### How It Works
- **Dual AI Engine**: Supports both **OpenAI** (GPT-4o, GPT-4o-mini) and **Google Gemini** (Gemini 2.5/3.5/3.8 Flash) through [`class-autopilot-openai.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/ai-cv-tailor/includes/class-autopilot-openai.php) and [`class-google-ai.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/ai-cv-tailor/includes/class-google-ai.php).
- **Background Cron & Automation**: Orchestrated via `wp-cron` and a full-featured WP-CLI command suite:
  ```bash
  wp ai-cv-tailor autopilot run --limit=5
  wp ai-cv-tailor autopilot score --post_id=123
  wp ai-cv-tailor autopilot generate --post_id=123
  ```
- **Interview Assistant Subsystem**:
  - Exposes REST API routes under `/wp-json/ai-cv-tailor/v1/interview/`:
    - `POST /interview/pair-code`: Generates a secure, temporary 6-character alphanumeric pairing code.
    - `POST /interview/pair`: Validates the pairing code and provisions long-lived auth tokens for desktop/extension clients.
    - `POST /interview/suggest-answer`: Analyzes live interviewer questions against CV data, projects, and target role parameters to return structured answers with key talking points.
- **Admin Dashboard**: Offers a complete cockpit for monitoring job queues, match scores, customized pitch previews, pairing management, and sending logs.

---

### 2. Gemini AI Avatar Assistant
- **Directory**: [`wp-content/plugins/gemini-avatar-assistant/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/gemini-avatar-assistant/)
- **Plugin Name**: `Gemini AI Avatar Assistant`
- **Main File**: [`gemini-avatar-assistant.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/gemini-avatar-assistant/gemini-avatar-assistant.php)

#### What It Does
A conversational talking AI avatar and interactive portfolio assistant for the public website that answers visitor questions about Matti Kiviharju's experience, skills, past projects, rates, and services.

#### How It Works
- **Knowledge Base Indexer**:
  - Automatically indexes published WordPress content across CPTs (`cv_experience`, `cv_project`, `cv_education`, `cv_course`, `cv_skill`, `cv_badge`, `cv_reference`, `page`, `post`).
  - Merges structured website data with verified custom knowledge entries (company ID, contact information, legal boundaries, and business terms).
  - Automatically invalidates and rebuilds index caches whenever posts are saved or updated.
- **Google Gemini Intelligence**:
  - Connects to Google AI Gemini models (`gemini-3.5-flash-lite`, `gemini-3.5-flash`, `gemini-3.8-flash`) with low latency and tight guardrails to prevent hallucinations.
- **Voice Synthesis (TTS) & Audio Playback**:
  - Integrates Gemini Flash TTS (`gemini-3.8-flash-tts`, `gemini-3.8-flash-lite-tts`) generating natural 24 kHz WAV audio.
  - Supports configurable voices (`Puck`, `Kore`, `Fenrir`, `Aoede`, `Zephyr`, `Orus`).
  - Includes browser-based speech-to-text voice input via the Web Speech API.
- **Front-End Widget & Standalone Popout**:
  - Floating corner widget with animated speaking states (lip-sync and audio visualizer waves).
  - Dedicated OBS/Streaming popout interface available via `?gemini_popout=1`.
  - Automatic language detection synchronized with Polylang (`fi`, `en`, `sv`).
  - Strict security guardrails: Nonce validation, IP-based rate limiting (per-minute and per-hour quotas), and input length caps.

---

### 3. i4ware AI SEO Agent
- **Directory**: [`wp-content/plugins/i4ware-ai-seo-agent/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/i4ware-ai-seo-agent/)
- **Plugin Name**: `i4ware AI SEO Agent`
- **Main File**: [`i4ware-ai-seo-agent.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/i4ware-ai-seo-agent/i4ware-ai-seo-agent.php)

#### What It Does
An AI-powered search engine optimization assistant that inspects post and page content to produce optimized SEO titles, meta descriptions, focus keywords, content readiness scores, and actionable ranking recommendations.

#### How It Works
- **Dual Operating Modes**:
  1. **Standalone Engine**: Directly renders clean `<meta name="description">` and `<meta name="keywords">` tags in the HTML `<head>` and filters `document_title_parts` if no third-party SEO plugin is present.
  2. **SEO Plugin Integration**: Automatically detects and writes directly into **Yoast SEO** (`_yoast_wpseo_title`, `_yoast_wpseo_metadesc`, `_yoast_wpseo_focuskw`) or **Rank Math** (`rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`).
- **Post Editor Meta Box**:
  - Single-click "Generate SEO with AI" button in the WordPress editor sidebar.
  - Character counter bars with visual threshold indicators (green/orange/red) for standard Google SERP snippet limits.
- **Bulk SEO Optimizer**:
  - Dedicated admin tab that scans the entire site, highlights posts missing SEO metadata, and runs batch optimizations.
  - Features real-time progress bars, live console logging, and cumulative API token and dollar cost estimators.
- **Customizable Tone of Voice**:
  - Supports Professional & Authoritative, Catchy & Click-Worthy, Informative & Educational, and Friendly & Conversational tones.

---

### 4. CV PDF & Word (.docx) to ACF Filler
- **Directory**: [`wp-content/plugins/cv-pdf-to-acf-filler/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/cv-pdf-to-acf-filler/)
- **Plugin Name**: `CV PDF & Word (.docx) to ACF Filler`
- **Main File**: [`cv-pdf-to-acf-filler.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/cv-pdf-to-acf-filler/cv-pdf-to-acf-filler.php)

#### What It Does
Extracts comprehensive CV information from uploaded PDF and Microsoft Word (`.docx`) files, extracts embedded project screenshot images into the WordPress Media Library, detects YouTube links, translates the parsed data into Finnish, English, and Swedish, and automatically populates the custom post types and ACF fields.

#### How It Works
- **Multi-Format Ingestion**:
  - Extracts text from PDF files using `smalot/pdfparser` (with `pdftotext` CLI fallback).
  - Unpacks Word `.docx` documents via PHP's `ZipArchive` and XML parsing.
- **Embedded Image Extraction**:
  - Automatically identifies, extracts, and decodes PNG and JPEG image screenshots embedded inside Word files, uploading them directly to the WordPress Media Library and associating them with project galleries.
- **Structured AI Extraction & Translation**:
  - Uses OpenAI (`gpt-4o`) to extract unstructured document text into a rigorous JSON schema containing Experience, Education, Courses, Skills, Badges, and Projects.
  - Concurrently translates all extracted data across Finnish, English, and Swedish.
- **ACF & CPT Synchronization**:
  - Creates or updates Polylang-linked CPT records (`cv_experience`, `cv_project`, `cv_education`, `cv_course`, `cv_skill`, `cv_badge`).
  - Updates home page ACF repeaters across all three languages.
- **Dedicated Project Importer**:
  - Includes a standalone project import tool to append or replace portfolio projects from `.docx` files without modifying existing work experience, education, or badge records.
- **Frontend Document Exporter**:
  - Bundles PHPWord and FPDF to dynamically compile and stream PDF and Word `.docx` downloads directly to visitors on the frontend.

---

### 5. i4ware CV OpenAI Polylang Translator (Enterprise Edition)
- **Directory**: [`wp-content/plugins/i4ware-cv-openai-polylang-translator/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/i4ware-cv-openai-polylang-translator/)
- **Plugin Name**: `CV OpenAI Polylang Translator (Enterprise)`
- **Main File**: [`i4ware-cv-openai-polylang-translator.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/i4ware-cv-openai-polylang-translator/i4ware-cv-openai-polylang-translator.php)

#### What It Does
A resource-controlled translation engine that converts WordPress posts, pages, and ACF fields from Finnish into B2B Modern Standard Swedish (tailored for Dubai/GCC and Nordic B2B markets) and English using OpenAI models with strict security and server protection.

#### How It Works
- **Resource Control & Global Locking**:
  - Employs a transient-based locking mechanism (`cv_oai_pll_global_lock`) with an automatic 10-minute expiry to ensure only one heavy translation task runs site-wide at any time, protecting database memory and preventing gateway timeouts.
- **Single-Post Sidebar Metabox**:
  - Executes translations on-demand directly from the post editor sidebar, allowing granular selection of elements (Title, Excerpt, Content, selected ACF fields, Alt tags).
- **Gutenberg-Safe Parser**:
  - Intelligently extracts text blocks without breaking Gutenberg block comments, JSON attributes, or block structure.
- **Recursive ACF Field Traversal**:
  - Recursively navigates nested ACF Groups, Repeaters, and flexible layout blocks, translating string values while preserving field keys and IDs.
- **Strict Integrity Guard**:
  - Enforces prompt rules and post-processing filters to prevent the AI from altering URLs, email addresses, telephone numbers, code blocks, or protected Latin trademarks (`i4ware`, `Atlassian`, `Jira`, `Forge`).
- **Human-in-the-Loop Safeguard**:
  - Saves all translations as `draft` status and requires an explicit "Translation reviewed by a human" checkbox before publication, preventing accidental hallucinations.
  - Maintains full audit logs of translation jobs.

---

### 6. CV OpenAI Polylang Translator (Core Edition)
- **Directory**: [`wp-content/plugins/cv-openai-polylang-translator/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/cv-openai-polylang-translator/)
- **Plugin Name**: `CV OpenAI Polylang Translator`
- **Main File**: [`cv-openai-polylang-translator.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/cv-openai-polylang-translator/cv-openai-polylang-translator.php)

#### What It Does
The streamlined, lightweight predecessor to the enterprise translator. Provides direct automated translations for custom post types and pages between Finnish, English, and Swedish using OpenAI's API.

#### How It Works
- Integrates directly with Polylang language mappings.
- Sends post content, excerpts, and titles to OpenAI GPT models (`gpt-4o`, `gpt-4o-mini`).
- Creates linked translation posts in Polylang with matching taxonomies and status.

---

### 7. Word to Blog AI
- **Directory**: [`wp-content/plugins/word-to-blog-ai/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/word-to-blog-ai/)
- **Plugin Name**: `Word to Blog AI`
- **Main File**: [`word-to-blog-ai.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/word-to-blog-ai/word-to-blog-ai.php)

#### What It Does
Converts Microsoft Word documents (`.docx`, `.doc`) into fully formed, SEO-friendly WordPress blog articles, complete with contextual AI-generated illustrations.

#### How It Works
- **Document Text Ingestion**:
  - Administrators upload a Word document via the dedicated admin page. Text and structural sections are parsed using PHPWord.
- **Article Generation**:
  - Uses OpenAI (`gpt-4o`) to transform raw document text into an engaging, structured blog post with an SEO-optimized headline, introduction, formatted headings (`<h2>`, `<h3>`), bullet points, and concluding remarks in Finnish.
- **Contextual Image Generation**:
  - Synthesizes 1 to 4 unique image prompts derived directly from the generated article's section topics.
  - Generates custom illustrations using OpenAI's image generator (`gpt-image-1` / DALL-E) without watermarks or text overlays.
  - Automatically imports images into the WordPress Media Library and inserts them into the post HTML body.
- **Editorial Safety**:
  - Saves the generated post as a WordPress **Draft** for editorial review before publishing.

---

### 8. WP Reddit Autopost
- **Directory**: [`wp-content/plugins/wp-reddit-autopost/`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/wp-reddit-autopost/)
- **Plugin Name**: `WP Reddit Autopost`
- **Main File**: [`wp-reddit-autopost.php`](file:///c:/Users/matti/git/mattikiviharju/wp-content/plugins/wp-reddit-autopost/wp-reddit-autopost.php)

#### What It Does
Automatically broadcasts newly published WordPress blog posts directly to a target Reddit subreddit as link submissions.

#### How It Works
- **Publish Trigger**:
  - Hooks into WordPress's `transition_post_status` action, activating only when a post status transitions from any state to `publish`.
- **Reddit OAuth2 API**:
  - Authenticates securely via a Reddit Script App using Client ID, Client Secret, Reddit Username, and Password (`class-reddit-api.php`).
  - Submits the post title and canonical permalink to the configured subreddit (e.g., `r/i4ware_modpilot_dev`).
- **Duplicate Protection**:
  - Stores a post meta flag `_posted_to_reddit` with a timestamp upon successful submission, guaranteeing no post is published to Reddit more than once.

---

## Companion Applications

The repository also includes companion client applications located in the `apps/` and `i4ware-modpilot/` directories:

### 1. Interview Assistant Desktop (`apps/interview-assistant-desktop/`)
- An **Electron / TypeScript** desktop app providing floating transparent overlay displays during video interviews.
- Connects securely to the WordPress `ai-cv-tailor` REST API via a 6-character pairing code.
- Captures system and microphone audio, feeds live speech transcriptions to the AI, and displays suggested answers, CV citations, and technical hints in real time.

### 2. Meeting Assistant Extension (`apps/meeting-assistant-extension/`)
- A **Google Chrome Extension** designed for Google Meet and Microsoft Teams.
- Intercepts live meeting subtitles/closed captions and streams the interviewer's queries directly to the `ai-cv-tailor` backend for instant assistance.

### 3. i4ware ModPilot (`i4ware-modpilot/`)
- A **Reddit Devvit** application providing automated subreddit moderation, content validation, and community management tools.

---

## Repository File Structure

```
mattikiviharju/
├── acf-json/
│   └── group_cv_one_pager.json              # ACF Field Group definitions (JSON sync)
├── apps/
│   ├── interview-assistant-desktop/         # Electron desktop app for live interview help
│   ├── meeting-assistant-extension/         # Chrome extension for Teams & Google Meet
│   ├── ISADORA_INTEGRATION_GUIDE.md         # Video streaming & Isadora integration guide
│   └── MEETING_INTEGRATIONS_GUIDE.md        # Video conferencing integration documentation
├── assets/                                  # Static theme images, logos, and icons
├── css/                                     # Supplemental stylesheets
├── i4ware-modpilot/                         # Reddit Devvit moderation bot
├── js/
│   ├── admin-project-gallery.js             # WP Media Library multi-image selector
│   ├── blog.js                              # AJAX "Load More" pagination handler
│   ├── gallery.js                           # Project screenshot lightbox & modal controls
│   └── nav.js                               # Sticky nav & smooth scrolling engine
├── wp-content/
│   └── plugins/
│       ├── ai-cv-tailor/                    # Job Seeker Autopilot AI & Interview Assistant
│       ├── cv-openai-polylang-translator/   # Core OpenAI Polylang Translator
│       ├── cv-pdf-to-acf-filler/            # PDF/DOCX to ACF and CPT parser
│       ├── gemini-avatar-assistant/         # Talking AI Avatar & Voice Assistant (Gemini)
│       ├── i4ware-ai-seo-agent/             # AI SEO Analyzer & Bulk Optimizer
│       ├── i4ware-cv-openai-polylang-translator/ # Enterprise Polylang Translator with locking
│       ├── word-to-blog-ai/                 # Word (.docx) to Blog Post with AI Images
│       └── wp-reddit-autopost/              # WordPress to Reddit autoposter
├── comments.php                             # Comments template
├── footer.php                               # Footer markup and script rendering
├── front-page.php                           # Front page router
├── functions.php                            # Core theme engine, CPTs, meta boxes, sorting, OG
├── header.php                               # Header markup, navigation, cookie flash guard
├── home.php                                 # Default blog post index
├── index.php                                # General fallback template
├── page.php                                 # Standard WordPress page template
├── page-blog.php                            # Dedicated blog archive template with AJAX
├── single.php                               # Single blog post template
├── style.css                                # Theme stylesheet and CSS variables
├── template-cv.php                          # One-page CV presentation template
└── README.md                                # Project documentation
```

---

## Requirements & Installation

### System Requirements
- **WordPress**: 6.0+ (Tested through WordPress 6.7+)
- **PHP**: 8.0+ (PHP 8.1 or 8.2 recommended)
- **Composer**: For managing plugin dependencies (`phpword`, `smalot/pdfparser`, etc.)
- **WordPress Plugins**:
  - [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) (Free or Pro)
  - [Polylang](https://polylang.pro/) (For multilingual Finnish, English, and Swedish support)
- **API Keys**:
  - OpenAI API Key (for GPT-4o, DALL-E, and translations)
  - Google Gemini API Key (for Gemini Avatar Assistant & Autopilot)
  - Reddit App Credentials (for Reddit Autopost)

### Installation Steps

1. **Install Theme**:
   - Clone or copy this repository into your WordPress themes folder:
     `wp-content/themes/mattikiviharju/`
   - In WordPress Admin, navigate to **Appearance > Themes** and activate **CV One Pager Theme**.

2. **Install Custom Plugins**:
   - Ensure the plugins under `wp-content/plugins/` are present in your WordPress installation.
   - Run `composer install` inside folders with a `composer.json` file:
     ```bash
     cd wp-content/plugins/cv-pdf-to-acf-filler && composer install
     cd ../word-to-blog-ai && composer install
     ```
   - In WordPress Admin, navigate to **Plugins > Installed Plugins** and activate the custom plugins:
     - `i4ware® Job Seeker Autopilot AI`
     - `Gemini AI Avatar Assistant`
     - `i4ware AI SEO Agent`
     - `CV PDF & Word (.docx) to ACF Filler`
     - `CV OpenAI Polylang Translator (Enterprise)`
     - `Word to Blog AI`
     - `WP Reddit Autopost`

3. **Configure Polylang**:
   - Navigate to **Languages > Languages** in WordPress Admin.
   - Add **Finnish** (`fi` - Default), **English** (`en`), and **Swedish** (`sv`).
   - Set up your front page as a static page and create its English and Swedish translations.

4. **Configure API Keys**:
   - **Google Gemini**: Configure in **Settings > Gemini AI Avatar** or define `GEMINI_API_KEY` in `wp-config.php`.
   - **OpenAI**: Configure in **Settings > AI SEO Agent**, **Settings > OpenAI Polylang**, and **Word to Blog AI**.
   - **Reddit**: Configure in **Settings > Reddit Auto-Post**.

---

## Developer Reference & Hooks

### Theme Actions & Filters

| Hook Name | Type | Description |
|---|---|---|
| `cv_one_pager_setup` | Action | Theme setup (thumbnails, logo, title tag support). |
| `cv_one_pager_register_post_types` | Action | Registers all 7 CPTs (`cv_experience`, `cv_project`, etc.). |
| `cv_one_pager_assets` | Action | Enqueues styles, Google Fonts, Bootstrap Icons, and scripts. |
| `cv_one_pager_register_polylang_strings` | Action | Registers theme UI strings with Polylang. |
| `cv_one_pager_add_meta_boxes` | Action | Registers custom meta boxes for projects, experience, references. |
| `wp_ajax_cv_load_more_posts` | Action | AJAX endpoint for loading additional blog post cards. |
| `pll_get_post_types` | Filter | Filters post types managed by Polylang. |

### Helper Functions

- [`cv_one_pager_t($string, $lang = null)`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L344): Returns translated string from Polylang or internal dictionary.
- [`cv_one_pager_front_page_id()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L1457): Retrieves the front page ID for the currently active language.
- [`cv_one_pager_parse_cv_dates($dates_str)`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L2066): Parses date range strings into comparable timestamps.
- [`cv_one_pager_sync_all_experience_menu_orders()`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L2240): Recalculates `menu_order` for all experience posts chronologically.
- [`cv_one_pager_render_post_card($post)`](file:///c:/Users/matti/git/mattikiviharju/functions.php#L1391): Generates HTML for an individual blog post card.

---

## License & Credits

- **Author**: Matti Kiviharju / [i4ware Software](https://www.i4ware.fi/)
- **Theme URI**: [https://mattikiviharju.i4ware.fi/](https://mattikiviharju.i4ware.fi/)
- **License**: GNU General Public License v2 or later
