# Changelog

All notable changes to the CV OpenAI Polylang Translator plugin are documented in this file.

---

## [2.0.0] - 2026-07-26

### Added
- **Single-Post Translation action**: Introduced sidebar metabox for post and page editors to translate one content item at a time.
- **Modern Standard Swedish (B2B)**: Dynamic translation prompt tailored for Dubai and the GCC region B2B communication style, preserving product/trademark names in Latin characters.
- **Resource Control and Locking**: Global transient-based locking (`cv_oai_pll_global_lock`) preventing overlapping execution threads with 10-minute automatic timeouts.
- **Gutenberg-Safe Content Parsing**: Recursive parser using `parse_blocks()` and `serialize_blocks()`, separating textual fields from structural elements.
- **Recursive ACF translation**: Supporting textual ACF fields, including Groups and Repeaters, using `update_field()` to preserve references.
- **Human Review Status Check**: Added edit status meta box on translation drafts. Integrates a JS warning that intercepts accidental publication of unreviewed translations.
- **Strict Integrity Checks**: Implemented validators against data loss, script injection, and modifications of URLs, emails, phone numbers, and product names.
- **History logging**: Visual logs in the plugin settings screen and per-post meta histories.

### Changed
- Refactored entire codebase from a single procedural file into a structured OOP design under `includes/`.
- Replaced legacy site-wide automatic batch translation script with secure client-side AJAX requests.

### Removed
- Unrestricted site-wide translation loops.
- Image generation (`gpt-image-1` / DALL-E) and download handlers during translation to protect server bandwidth.

