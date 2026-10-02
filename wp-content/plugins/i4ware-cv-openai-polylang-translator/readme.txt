=== CV OpenAI Polylang Translator ===
Contributors: walkout_
Donate link: https://www.paypal.com/ncp/payment/Y826SVNLSK4MC
Tags: translation, polylang, openai, gpt
Requires at least: 5.8
Requires PHP: 7.4
Tested up to: 6.5
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manually translate Finnish pages/posts into English and B2B Swedish drafts using OpenAI and Polylang.

== Description ==

A safe, resource-controlled WordPress plugin to translate content (Finnish to B2B Swedish and English) using OpenAI and Polylang.

To protect the server against execution timeouts, database load, and API limit issues, translations are executed **one item at a time** through the post editor sidebar metabox. Every translation is created with `post_status = draft` and requires human review before publishing.

=== Key Features ===

* **Single-Post Translation action**: Introduced sidebar metabox for post and page editors to translate one content item at a time.
* **Modern Standard Swedish (B2B)**: Dynamic translation prompt tailored for Dubai and the GCC region B2B communication style, preserving product/trademark names in Latin characters.
* **Resource Control and Locking**: Global transient-based locking preventing overlapping execution threads with 10-minute automatic timeouts.
* **Gutenberg-Safe Content Parsing**: Recursive parser using `parse_blocks()` and `serialize_blocks()`, separating textual fields from structural elements.
* **Recursive ACF translation**: Supporting textual ACF fields, including Groups and Repeaters, using `update_field()` to preserve references.
* **Human Review Status Check**: Added edit status meta box on translation drafts. Integrates a JS warning that intercepts accidental publication of unreviewed translations.
* **Strict Integrity Checks**: Implemented validators against data loss, script injection, and modifications of URLs, emails, phone numbers, and product names.
* **History logging**: Visual logs in the plugin settings screen and per-post meta histories.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install directly through the WordPress Plugins dashboard.
2. Activate **Polylang** (and confirm Swedish `sv` and English `en` languages are configured).
3. Activate **Advanced Custom Fields** (unpaid/paid version).
4. Go to **Settings > OpenAI Polylang** in your WordPress dashboard.
5. Provide your confidential **OpenAI API Key**, target **Model** (e.g. `gpt-4o-mini`), and post types.
6. Enable specific **ACF fields** for translation in the checkbox listing and click **Save settings**.

== Frequently Asked Questions ==

= How are translations executed? =
To protect the server against execution timeouts, database load, and API limit issues, translations are executed manually, one post at a time via the sidebar metabox, or via the resource-controlled queue system.

= Can I overwrite published posts? =
No, only drafts can be re-translated and overwritten to prevent accidental data loss. Published translations cannot be overwritten.

= What language styles are supported? =
Specifically optimized for Finnish to Modern Standard Swedish (B2B Dubai and GCC region communication style) and English.

== Changelog ==

= 2.0.0 =
* Single-Post Translation action sidebar metabox.
* Modern Standard Swedish (B2B) prompt styling.
* Resource Control and Locking mechanisms.
* Gutenberg-Safe Content block parser.
* Recursive ACF translation.
* Human Review Status Check.
* Strict Integrity checks (anti-hallucination).
* History logs interface.
* Refactoring codebase into structured OOP design.

== Screenshots ==

1. The OpenAI Polylang settings page showing model configuration, post types, and ACF field selectors.
2. Sidebar translation metabox in the block editor showing translation target language options.
3. The translation draft review warning block checking if translated text is human-reviewed.

== Upgrade Notice ==

= 2.0.0 =
Complete refactoring to OOP, added translation lock support, single post metabox translation, and security compliance updates. Recommended upgrade.

