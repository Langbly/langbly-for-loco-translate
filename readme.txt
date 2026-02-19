=== Langbly for Loco Translate ===
Contributors: langbly
Tags: translation, loco-translate, machine-translation, ai, localization
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered translations for Loco Translate. A drop-in Google Translate replacement — 5x cheaper with better quality.

== Description ==

**Langbly for Loco Translate** adds Langbly as a machine translation provider in the Loco Translate editor. Translate your WordPress plugins and themes with AI-powered translations that understand context, tone, and locale conventions.

= Why Langbly? =

* **5-10x cheaper** than Google Translate ($1.99-$4/1M characters vs $20/1M)
* **Better quality** — LLM-powered translations understand context and idioms
* **Locale formatting** — automatic decimal, date, and currency formatting per language
* **Placeholder safe** — preserves %s, %d, %1$s and other printf placeholders
* **Free tier** — 500K characters to get started, no credit card required

= How It Works =

1. Install and activate this plugin alongside Loco Translate
2. Add your Langbly API key to wp-config.php
3. Select "Langbly" as your translation provider in Loco Translate
4. Click "Auto-translate" in the Loco editor — done!

= Supported Languages =

Langbly supports all major languages including English, Dutch, German, French, Spanish, Portuguese, Italian, Chinese, Japanese, Korean, Arabic, Russian, and many more. Especially strong for Dutch translations.

== Installation ==

1. Install [Loco Translate](https://wordpress.org/plugins/loco-translate/) if not already installed.
2. Upload the `langbly-for-loco-translate` folder to `/wp-content/plugins/`.
3. Activate the plugin from the WordPress admin.
4. Add your API key to `wp-config.php`:

`define( 'LANGBLY_API_KEY', 'your-api-key-here' );`

5. In Loco Translate settings, select **Langbly** as your translation provider.

= Getting an API Key =

1. Sign up for free at [langbly.com/signup](https://langbly.com/signup)
2. Go to your dashboard and create an API key
3. Copy the key to your wp-config.php

== Frequently Asked Questions ==

= How much does it cost? =

Langbly offers a free tier with 500K characters. Paid plans start at $19/month for 5M characters. See [langbly.com/pricing](https://langbly.com/pricing) for details.

= Which languages are supported? =

All standard ISO 639-1 languages are supported. Langbly is especially strong for European languages like Dutch, German, and French.

= Is it compatible with Google Translate? =

Yes! Langbly uses the same API format as Google Translate v2, so the integration is seamless. If you've used Google Translate with Loco before, the experience is identical.

= Where do I find my API key? =

Sign up at [langbly.com/signup](https://langbly.com/signup) and go to your dashboard to create an API key. The free tier includes 500K characters.

= Does it preserve placeholders? =

Yes. Langbly automatically protects printf placeholders (%s, %d, %1$s) and other formatting in your translation strings.

== Changelog ==

= 1.0.2 =
* Wrap source locale detection in try/catch for better error resilience
* Use explicit cast for locale string conversion
* Simplify primary language subtag extraction
* Update User-Agent header to match version

= 1.0.1 =
* Version bump, minor internal improvements

= 1.0.0 =
* Initial release
* Batch translation support
* Automatic chunking for large translation jobs
* Locale-aware language code mapping
* Full error handling with user-friendly messages

== Upgrade Notice ==

= 1.0.2 =
Improved error handling for source locale detection. Recommended update.
