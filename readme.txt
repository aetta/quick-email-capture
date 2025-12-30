=== Aetta Email Capture ===
Contributors: aetta
Tags: email capture, newsletter, opt-in, lead capture, lightweight
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple, fast and lightweight email capture. No bloat.

== Description ==

Aetta Email Capture is a minimal email capture plugin for WordPress.

It focuses on one thing only: collecting email signups in a fast, clean and reliable way without unnecessary features, external services or heavy scripts.

No page builders.
No tracking.
No marketing automation.

Just a simple opt-in form that works with any theme.

= Key Features =

* Simple shortcode: `[aetta_email_capture]`
* Lightweight and fast (no JS frameworks)
* GDPR-friendly consent checkbox
* Built-in honeypot and basic anti-spam
* Email deduplication
* CSV export (Excel-safe)
* Optional IP and User-Agent storage
* Automatic data retention and purge
* Fully translation-ready
* Works with any theme or page builder

== Installation ==

1. Upload the `aetta-email-capture` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Add the shortcode `[aetta_email_capture]` to any page or post

No configuration is required to start.

== Usage ==

Add the shortcode anywhere:

[aetta_email_capture]

The form will handle validation, consent and submission automatically.

== Settings ==

You can configure the plugin under:

WP Admin → Aetta Email Capture → Settings

Available options include:

* Data retention period
* Rate limiting and anti-bot delay
* Whether to store IP and User-Agent
* Enable/disable built-in CSS
* Add custom CSS classes for easy theming
* Customize labels and messages

== Styling & Customization ==

The plugin uses clean HTML and CSS variables.

You can:

* Disable built-in CSS and style everything from your theme
* Override styles using CSS variables
* Add custom classes via the settings page

No inline styles or scripts are required.

== Privacy ==

Aetta Email Capture stores submitted data as private entries in WordPress.

Depending on your settings, it may store:

* Name
* Email address
* Consent status
* Submission date
* Optional IP address and User-Agent

The plugin integrates with WordPress Privacy tools and supports data export and erasure.

== Frequently Asked Questions ==

= Does this plugin integrate with Mailchimp / Brevo / etc? =
No. This plugin only collects emails. You can export the CSV and import it into any service you want.

= Is this plugin GDPR compliant? =
It includes explicit consent and privacy-friendly defaults. Final compliance depends on how you use it and your privacy policy.

= Does it work with my theme? =
Yes. It uses standard HTML and works with any WordPress theme.

== Changelog ==

= 1.0.1 =
* Initial public release

== Upgrade Notice ==

= 1.0.1 =
Initial release.
