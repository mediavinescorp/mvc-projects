# MV Review Generator — WordPress Plugin
**by Media Vines Corp**

Generates branded Google review pages for clients. Each submission creates a WordPress Custom Post Type (CPT) entry with a permanently saved QR code image, accessible at a unique URL.

---

## Installation

1. Upload the `mv-review-generator` folder to `/wp-content/plugins/`
2. Activate the plugin via **WordPress Admin → Plugins**
3. That's it — no configuration required

---

## How to use

### Step 1 — Add the form to a page
Create or edit any WordPress page and add this shortcode:

```
[mv_review_form]
```

This renders the full input form where businesses enter their details.

### Step 2 — Client fills in the form
The form collects:
- Business logo (optional, uploaded to media library)
- Business name (required)
- Google Review link (required — must be from Google Business Profile)
- Thank-you message / tagline (optional)
- Industry (optional)

### Step 3 — Page is generated automatically
On submit, the plugin:
1. Creates a `review_page` CPT post
2. Uploads the logo to the media library
3. Calls the QR API **once** to download the QR image
4. Saves the QR PNG permanently to `wp-content/uploads/mv-qrcodes/`
5. Stores all data in post meta
6. Redirects the user to their new live review page

### Step 4 — Client shares their page
Every client gets a unique URL:
```
yourdomain.com/review/their-business-name
```

The page includes:
- Business logo or initials
- QR code (served from your server — no external dependencies)
- 5-step mobile instructions
- Tap-to-review CTA button
- Share via Email / Share via Text buttons
- "Created by Media Vines Corp" footer credit

---

## QR Code — how it works

The QR is generated **once** at form-submit time:
1. Plugin calls `api.qrserver.com` with the review link
2. Downloads the PNG image
3. Saves it to `wp-content/uploads/mv-qrcodes/qr-{slug}-{post-id}.png`
4. Stores the local URL in post meta

After that, the review page serves a **local static image** — no ongoing external dependency. If the QR image is unavailable for any reason, the template has a fallback that loads from the API directly.

---

## File naming convention

QR images are saved as:
```
qr-{business-slug}-{post-id}.png
```

Examples:
```
qr-value-max-quality-builders-42.png
qr-joes-pizza-brooklyn-43.png
qr-sunset-auto-repairs-44.png
```

The post ID ensures uniqueness even if two businesses have identical names.

---

## Viewing & managing review pages

All review pages are visible in **WordPress Admin → Review Pages**.

Each post shows:
- All submitted fields
- The QR code image preview
- The public page URL with a one-click Copy button

---

## Permalink structure

Review pages are served at:
```
yourdomain.com/review/{business-name-slug}
```

If you change your permalink structure, go to **Settings → Permalinks** and click **Save Changes** to flush the rewrite rules.

---

## Plugin file structure

```
mv-review-generator/
├── mv-review-generator.php     — Main plugin file
├── includes/
│   ├── cpt.php                 — Registers CPT + meta fields + admin meta box
│   ├── qr-generator.php        — QR fetch, download, and save logic
│   ├── form-handler.php        — AJAX form processor
│   └── shortcode.php           — [mv_review_form] shortcode
├── templates/
│   └── single-review-page.php  — Public review page template
├── assets/
│   ├── css/style.css           — Form and review page styles
│   └── js/form.js              — Logo preview, validation, AJAX, share actions
└── README.md                   — This file
```

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Outbound HTTP requests enabled on your server (for QR generation at submit time)

---

## Credits

Built for **Media Vines Corp**
Plugin Version: 1.0.0
