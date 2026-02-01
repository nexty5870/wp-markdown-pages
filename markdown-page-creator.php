<?php
/**
 * Plugin Name: Markdown Page Creator
 * Plugin URI: https://makeautomation.co
 * Description: Create WordPress pages from structured markdown files. Perfect for tool landing pages.
 * Version: 1.0.0
 * Author: MakeAutomation
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include Parsedown
require_once plugin_dir_path(__FILE__) . 'lib/Parsedown.php';

class MarkdownPageCreator {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Markdown Page Creator',
            'MD Page Creator',
            'manage_options',
            'markdown-page-creator',
            [$this, 'render_admin_page']
        );
    }
    
    public function enqueue_styles($hook) {
        if ($hook !== 'tools_page_markdown-page-creator') {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .mpc-container { max-width: 1200px; }
            .mpc-textarea { width: 100%; min-height: 400px; font-family: monospace; font-size: 13px; }
            .mpc-preview { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-top: 20px; }
            .mpc-preview h1 { font-size: 24px; margin-top: 0; }
            .mpc-meta { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
            .mpc-meta strong { display: inline-block; width: 120px; }
            .mpc-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
            .mpc-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
            .mpc-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            @media (max-width: 1200px) { .mpc-columns { grid-template-columns: 1fr; } }
        ');
    }
    
    public function parse_markdown($markdown) {
        $result = [
            'title' => '',
            'slug' => '',
            'meta_description' => '',
            'meta_title' => '',
            'content' => '',
            'raw_content' => ''
        ];
        
        $lines = explode("\n", $markdown);
        $in_frontmatter = false;
        $frontmatter_done = false;
        $content_lines = [];
        
        foreach ($lines as $line) {
            // Detect frontmatter
            if (trim($line) === '---' && !$frontmatter_done) {
                if (!$in_frontmatter) {
                    $in_frontmatter = true;
                    continue;
                } else {
                    $in_frontmatter = false;
                    $frontmatter_done = true;
                    continue;
                }
            }
            
            // Parse frontmatter
            if ($in_frontmatter) {
                if (preg_match('/^title:\s*(.+)$/i', $line, $m)) {
                    $result['title'] = trim($m[1], '"\'');
                } elseif (preg_match('/^slug:\s*(.+)$/i', $line, $m)) {
                    $result['slug'] = trim($m[1], '"\'');
                } elseif (preg_match('/^meta_description:\s*(.+)$/i', $line, $m)) {
                    $result['meta_description'] = trim($m[1], '"\'');
                } elseif (preg_match('/^meta_title:\s*(.+)$/i', $line, $m)) {
                    $result['meta_title'] = trim($m[1], '"\'');
                } elseif (preg_match('/^description:\s*(.+)$/i', $line, $m)) {
                    $result['meta_description'] = trim($m[1], '"\'');
                }
                continue;
            }
            
            // Collect content
            $content_lines[] = $line;
        }
        
        $raw_content = implode("\n", $content_lines);
        $result['raw_content'] = $raw_content;
        
        // If no title from frontmatter, try to get from first H1
        if (empty($result['title'])) {
            if (preg_match('/^#\s+(.+)$/m', $raw_content, $m)) {
                $result['title'] = trim($m[1]);
                // Remove the H1 from content since WP will show title
                $raw_content = preg_replace('/^#\s+.+\n?/m', '', $raw_content, 1);
            }
        }
        
        // Generate slug from title if not set
        if (empty($result['slug']) && !empty($result['title'])) {
            $result['slug'] = sanitize_title($result['title']);
        }
        
        // Parse markdown to HTML
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false);
        $result['content'] = $parsedown->text($raw_content);
        
        return $result;
    }
    
    public function create_page($data) {
        // Check if page with this slug exists
        $existing = get_page_by_path($data['slug']);
        
        $page_data = [
            'post_title'   => $data['title'],
            'post_content' => $data['content'],
            'post_status'  => 'draft',
            'post_type'    => 'page',
            'post_name'    => $data['slug']
        ];
        
        if ($existing) {
            $page_data['ID'] = $existing->ID;
            $page_id = wp_update_post($page_data);
            $action = 'updated';
        } else {
            $page_id = wp_insert_post($page_data);
            $action = 'created';
        }
        
        if (is_wp_error($page_id)) {
            return ['error' => $page_id->get_error_message()];
        }
        
        // Update meta description if Yoast or RankMath is active
        if (!empty($data['meta_description'])) {
            // Yoast
            update_post_meta($page_id, '_yoast_wpseo_metadesc', $data['meta_description']);
            // RankMath
            update_post_meta($page_id, 'rank_math_description', $data['meta_description']);
        }
        
        if (!empty($data['meta_title'])) {
            update_post_meta($page_id, '_yoast_wpseo_title', $data['meta_title']);
            update_post_meta($page_id, 'rank_math_title', $data['meta_title']);
        }
        
        return [
            'success' => true,
            'page_id' => $page_id,
            'action' => $action,
            'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit'),
            'view_url' => get_permalink($page_id)
        ];
    }
    
    public function render_admin_page() {
        $message = '';
        $parsed = null;
        $markdown = '';
        
        if (isset($_POST['mpc_action']) && check_admin_referer('mpc_nonce')) {
            $markdown = stripslashes($_POST['mpc_markdown'] ?? '');
            
            if (!empty($markdown)) {
                $parsed = $this->parse_markdown($markdown);
                
                if ($_POST['mpc_action'] === 'create' && !empty($parsed['title'])) {
                    $result = $this->create_page($parsed);
                    if (isset($result['error'])) {
                        $message = '<div class="mpc-error">Error: ' . esc_html($result['error']) . '</div>';
                    } else {
                        $message = '<div class="mpc-success">Page ' . $result['action'] . '! ';
                        $message .= '<a href="' . esc_url($result['edit_url']) . '">Edit page</a> | ';
                        $message .= '<a href="' . esc_url($result['view_url']) . '" target="_blank">View page</a></div>';
                    }
                }
            }
        }
        
        ?>
        <div class="wrap mpc-container">
            <h1>Markdown Page Creator</h1>
            <p>Paste your structured markdown below. Supports frontmatter (title, slug, meta_description) and standard markdown content.</p>
            
            <?php echo $message; ?>
            
            <form method="post">
                <?php wp_nonce_field('mpc_nonce'); ?>
                
                <div class="mpc-columns">
                    <div>
                        <h2>Markdown Input</h2>
                        <textarea name="mpc_markdown" class="mpc-textarea" placeholder="---
title: My Tool Page
slug: my-tool
meta_description: A great tool for...
---

# My Tool

Content goes here..."><?php echo esc_textarea($markdown); ?></textarea>
                        
                        <p style="margin-top: 10px;">
                            <button type="submit" name="mpc_action" value="preview" class="button button-secondary">Preview</button>
                            <button type="submit" name="mpc_action" value="create" class="button button-primary">Create/Update Page</button>
                        </p>
                    </div>
                    
                    <div>
                        <?php if ($parsed): ?>
                        <h2>Preview</h2>
                        
                        <div class="mpc-meta">
                            <p><strong>Title:</strong> <?php echo esc_html($parsed['title'] ?: '(none)'); ?></p>
                            <p><strong>Slug:</strong> <?php echo esc_html($parsed['slug'] ?: '(auto-generated)'); ?></p>
                            <p><strong>Meta Desc:</strong> <?php echo esc_html($parsed['meta_description'] ?: '(none)'); ?></p>
                        </div>
                        
                        <div class="mpc-preview">
                            <h1><?php echo esc_html($parsed['title']); ?></h1>
                            <?php echo wp_kses_post($parsed['content']); ?>
                        </div>
                        <?php else: ?>
                        <h2>Preview</h2>
                        <p>Paste markdown and click Preview to see the result.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <hr style="margin-top: 30px;">
            <h2>Markdown Format</h2>
            <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
---
title: "ROI Calculator - Free Automation Tool"
slug: "roi-calculator"
meta_description: "Calculate your automation ROI in 60 seconds..."
meta_title: "Free ROI Calculator | MakeAutomation"
---

## Why Calculate Your Automation ROI?

Your content here. Supports **bold**, *italic*, [links](url), and more.

### Embed an iframe

&lt;iframe src="https://tools.example.com/tool?embed=true" 
        style="width:100%;height:700px;border:none;"&gt;&lt;/iframe&gt;
            </pre>
        </div>
        <?php
    }
}

new MarkdownPageCreator();
