# CV OpenAI Polylang Translator

A safe, resource-controlled WordPress plugin to translate content (Finnish to B2B Swedish and English) using OpenAI and Polylang.

## Features & Capabilities

- **Single-Post Translation metabox**: Translate content manually one post at a time via the sidebar metabox, protecting your server against resource constraints.
- **B2B Modern Standard Swedish**: Custom AI prompts tailored for the Dubai and GCC region B2B communication style, while ensuring trademark names remain in Latin characters.
- **Resource Control & Translation Locking**: A transient-based global locking mechanism (`cv_oai_pll_global_lock`) with an automatic 10-minute timeout prevents concurrent, resource-heavy execution threads.
- **Gutenberg block-safe parser**: Separates text elements from Gutenberg markup structure dynamically.
- **Recursive ACF Translation**: Traverses and translates nested Advanced Custom Fields (Groups, Repeaters, text areas) while preserving structural layout references.
- **Human-in-the-loop validation**: Forces a human review checkmark on translated drafts before publication to prevent unintended AI hallucinations.
- **Strict Integrity Checks**: Automated validators protecting against code/script injection and preventing AI modification of URLs, emails, phone numbers, and trademark terms.
- **History Logs**: Full database-stored audit trails visible in settings and individual post meta histories.
- **Compliance & Security**: Implemented strict WordPress nonce verification fields, user role permission verification, and sanitize callbacks on all settings.

---

## 1. Prerequisites and Installation

### Staging Setup
1. **Back up the WordPress database** before activation or running translations.
2. Activate **Polylang** (and confirm Swedish `sv` and English `en` languages are configured).
3. Activate **Advanced Custom Fields** (unpaid/paid version).
4. Go to **Settings > OpenAI Polylang** in your WordPress dashboard.
5. Provide your confidential **OpenAI API Key**, target **Model** (e.g. `gpt-4o-mini`), and post types.
6. Enable specific **ACF fields** for translation in the checkbox listing and click **Save settings**.

---

## 2. Server-Safe Manual Workflow

To protect the server against execution timeouts, database load, and API limit issues, translations are executed **one item at a time** through the post editor sidebar metabox.

### Before Translation
1. Confirm Polylang Swedish is properly configured.
2. Confirm the OpenAI API key is configured.
3. Confirm ACF field selections.
4. Open the source page or blog post.
5. Check that the source language is Finnish (`fi`).
6. Save the source post (must not be an unsaved auto-draft).
7. Confirm that embedded media, YouTube videos, and images work correctly before translation.

### Translating One Item
1. Open the post/page editing screen.
2. Find the **OpenAI Polylang Translator** meta box on the sidebar.
3. Select your target language (**Swedish** or **English**).
4. Check which sections to translate (Title, Excerpt, Main Content, selected ACF fields, Captions, Alt text).
5. If a draft translation already exists, you will see a link to open it. Check **Retranslate and Overwrite** if you wish to override it (published translations cannot be overwritten).
6. Click **Translate with OpenAI**.
7. *Do not open multiple translation tabs. Only one translation job may run at a time site-wide due to global locking.*
8. Wait for the status indicator to show success, and click **Open Draft Translation** to view the draft.

---

## 3. Human Review Workflow

Every translation is created with `post_status = draft` and requires human review.

### Review Guidelines
In the translated draft post edit screen, review:
* **Title** and **Excerpt**
* **Main Content** blocks
* **ACF field values**
* **Product names & trademarks** (verify Latin names like `i4ware`, `Jira`, `Atlassian` remain unchanged)
* **Numeric values** (prices, currencies, dates, version numbers)
* **Links & Buttons**
* **Right-to-Left (RTL) layout** consistency
* **Media & Embeds** (original images, YouTube videos, and galleries)

---

## 4. Before Publishing Swedish
1. Preview the page in the browser.
2. Confirm `dir="rtl"` is applied automatically through the Swedish configuration.
3. Confirm Latin product names are legible and uncorrupted.
4. Confirm button alignments and navigation layout elements align correctly.
5. Confirm original images and YouTube videos still work.
6. Confirm URLs and email addresses are unmodified.
7. Confirm no AI hallucinations exist.
8. Check the **Translation reviewed by a human** box in the sidebar (saves status).
9. Publish the post manually.

