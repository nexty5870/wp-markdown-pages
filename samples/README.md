# WordPress Pages

Ready-to-use content for MakeAutomation blog pages.

## Pages

| File | Tool | Status |
|------|------|--------|
| `roi-calculator.md` | ROI Calculator | Ready |
| `readiness-assessment.md` | Automation Readiness | TODO |
| `workflow-diagram.md` | Workflow Diagram Tool | TODO |
| `cost-estimator.md` | Cost Estimator | TODO |
| `integration-checker.md` | Integration Checker | TODO |

## Usage

1. Copy the content from the markdown file
2. Create new page in WordPress
3. Set SEO fields (title, meta, slug) from the table at top
4. Paste embed code where indicated
5. Add FAQ block with the FAQ content
6. Publish

## Embed Pattern

All tools support `?embed=true` for clean iframe embedding:

```html
<iframe 
  src="https://tools.makeautomation.co/[tool-slug]?embed=true" 
  style="width:100%;height:700px;border:none;border-radius:12px;" 
  loading="lazy" 
  title="[Tool Name]">
</iframe>
```
