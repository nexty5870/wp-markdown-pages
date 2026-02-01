# Markdown Page Creator - WordPress Plugin

Create WordPress pages from structured markdown files. Perfect for tool landing pages.

## Installation

1. **Download/copy** the `wp-markdown-pages` folder
2. **Upload** to your WordPress: `wp-content/plugins/wp-markdown-pages/`
3. **Activate** in WordPress Admin → Plugins
4. **Use** via Tools → MD Page Creator

### Quick install via command line (if you have SSH access):

```bash
cd /path/to/wordpress/wp-content/plugins/
# Copy the plugin folder here
# Then activate in WP admin
```

## Usage

1. Go to **Tools → MD Page Creator**
2. Paste your markdown content
3. Click **Preview** to check the output
4. Click **Create/Update Page** to create a draft

## Markdown Format

```markdown
---
title: "ROI Calculator - Free Automation Tool"
slug: "roi-calculator"
meta_description: "Calculate your automation ROI in 60 seconds..."
meta_title: "Free ROI Calculator | MakeAutomation"
---

## Why Calculate Your Automation ROI?

Your content here. Supports **bold**, *italic*, [links](url), and more.

### The Calculator

<iframe src="https://tools.makeautomation.co/roi-calculator?embed=true" 
        style="width:100%;height:700px;border:none;border-radius:12px;"
        loading="lazy"
        title="Automation ROI Calculator"></iframe>

## Next Steps

- Book a [free discovery call](/contact)
- Check out our [services](/services)
```

## Features

- **Frontmatter support**: title, slug, meta_description, meta_title
- **Auto-generates slug** from title if not specified
- **SEO meta**: Works with Yoast and RankMath
- **Update existing**: If a page with the slug exists, it updates instead of creating duplicate
- **Preview**: See the rendered HTML before creating
- **Draft mode**: Creates pages as drafts so you can review before publishing

## File Structure

```
wp-markdown-pages/
├── markdown-page-creator.php   # Main plugin file
├── lib/
│   └── Parsedown.php           # Markdown parser
└── README.md
```

## Tips

- Pages are created as **drafts** - publish when ready
- The H1 from your markdown becomes the page title (if no frontmatter title)
- Iframes are preserved as-is (not sanitized away)
- If you update the markdown and re-submit with the same slug, it updates the existing page
