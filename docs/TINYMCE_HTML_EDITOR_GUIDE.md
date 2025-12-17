# TinyMCE HTML Editor - Quick Guide

**Added:** December 8, 2025  
**Status:** ✅ Active

---

## Overview

A **free, powerful HTML editor** has been added to the article creation and editing pages, allowing you to style blog content with rich formatting, images, tables, and more.

---

## Features

### Text Formatting
- ✅ **Bold, Italic, Underline** - Style your text
- ✅ **Headings** - H1, H2, H3, H4, H5, H6 for structure
- ✅ **Font Colors** - Text and background colors
- ✅ **Text Alignment** - Left, center, right, justify
- ✅ **Lists** - Bulleted and numbered lists
- ✅ **Indentation** - Increase/decrease indent

### Content Elements
- ✅ **Links** - Insert hyperlinks (opens in new tab by default)
- ✅ **Images** - Upload images directly from editor
- ✅ **Tables** - Create formatted tables
- ✅ **Media** - Embed videos (YouTube, Vimeo, etc.)
- ✅ **Special Characters** - Insert symbols and emojis
- ✅ **Horizontal Rules** - Visual separators

### Advanced Features
- ✅ **HTML Code View** - Edit raw HTML
- ✅ **Fullscreen Mode** - Distraction-free writing
- ✅ **Undo/Redo** - Unlimited history
- ✅ **Word Count** - Track article length
- ✅ **Search & Replace** - Find and replace text
- ✅ **Paste from Word** - Clean formatting from Word docs
- ✅ **Copy Images** - Paste images directly from clipboard

---

## Where to Find It

### Article Add Page
- **URL:** `/admin/article-add.php`
- **Location:** Main content area → "Content" field
- **Height:** 500px editor window

### Article Edit Page
- **URL:** `/admin/article-edit.php?id=123`
- **Location:** Main content area → "Content" field
- **Height:** 500px editor window

---

## How to Use

### Basic Text Formatting

1. **Select text** you want to format
2. Click toolbar buttons:
   - **B** = Bold
   - **I** = Italic
   - **U** = Underline
   - **Color icon** = Text/background color
3. Use dropdown to select **Paragraph** or **Heading** styles

### Insert Images

**Method 1: Upload from Computer**
1. Click **Image** icon in toolbar
2. Click **Upload** tab
3. Choose file from computer
4. Add description (optional)
5. Click **Save**

**Method 2: Paste from Clipboard**
1. Copy image from anywhere
2. Right-click in editor → **Paste**
3. Image automatically uploads

**Method 3: Insert by URL**
1. Click **Image** icon
2. Enter image URL
3. Add dimensions and alt text
4. Click **Save**

### Insert Links

1. Select text to link
2. Click **Link** icon
3. Enter URL (e.g., `https://example.com`)
4. Optionally set **Title** for tooltip
5. Click **Save**
   - Links open in new tab by default

### Create Tables

1. Click **Table** icon in toolbar
2. Select grid size (rows × columns)
3. Table appears in editor
4. Right-click table for options:
   - Insert/delete rows
   - Insert/delete columns
   - Merge cells
   - Table properties

### Embed Videos

1. Click **Media** icon
2. Paste video URL (YouTube, Vimeo, etc.)
3. Adjust size (width/height)
4. Click **Save**

### Code View (HTML)

1. Click **Code** icon (< > symbol)
2. Edit raw HTML directly
3. Click **Code** again to return to visual mode

### Fullscreen Mode

1. Click **Fullscreen** icon
2. Editor expands to fill screen
3. Press **ESC** or click icon again to exit

---

## Toolbar Layout

```
┌──────────────────────────────────────────────────────────────────────┐
│ Undo  Redo │ Blocks ▼ │ B I Color Color │ ≡ ≡ ≡ ≡ │ • 1. ← → │      │
│             │          │                 │         │       ⎌  ⎌  │      │
│ Remove │ 🔗 🖼️ 📹 ▦ │ Code Fullscreen │ ? Help                      │
└──────────────────────────────────────────────────────────────────────┘
```

**Toolbar Sections:**
- **History:** Undo/Redo
- **Blocks:** Paragraph, Headings
- **Format:** Bold, Italic, Colors
- **Alignment:** Left, Center, Right, Justify
- **Lists:** Bullets, Numbers, Indent
- **Insert:** Link, Image, Media, Table
- **Tools:** Code view, Fullscreen, Help

---

## Menu Bar

The editor includes a full menu bar with:

### File
- New document
- Print

### Edit
- Undo/Redo
- Cut/Copy/Paste
- Select All
- Find & Replace

### View
- Show blocks
- Show invisible characters
- Fullscreen

### Insert
- Image
- Link
- Media
- Table
- Horizontal rule
- Special character
- Date/time

### Format
- Bold/Italic/Underline
- Strikethrough
- Superscript/Subscript
- Code
- Clear formatting
- Text color
- Background color

### Tools
- Word count
- Source code

### Table
- Insert table
- Row/Column operations
- Cell operations
- Delete table

### Help
- Keyboard shortcuts
- Plugins
- Version info

---

## Image Upload

### Automatic Upload
When you paste or insert an image, it:
1. **Automatically uploads** to `/uploads/articles/`
2. **Generates unique filename** (e.g., `editor_1733644800_abc123.jpg`)
3. **Inserts image** with full URL
4. **No manual save needed** - instant upload

### File Requirements
- **Allowed formats:** JPG, JPEG, PNG, GIF, WEBP
- **Max file size:** 5MB per image
- **Auto-naming:** Prevents filename conflicts

### Upload Location
- **Server path:** `c:\xampp\htdocs\uploads\articles\`
- **Public URL:** `http://yourdomain.com/uploads/articles/editor_xxx.jpg`

---

## Tips & Best Practices

### Writing Tips
1. **Use Headings** - Structure content with H2, H3 for readability
2. **Break up text** - Use paragraphs and lists
3. **Add images** - Visual content improves engagement
4. **Use links** - Reference sources and related articles
5. **Preview often** - Check formatting before publishing

### Performance Tips
1. **Optimize images** - Compress before upload (use TinyPNG, etc.)
2. **Avoid huge tables** - Use simple layouts
3. **Limit media embeds** - Too many videos slow page load
4. **Clean paste** - Remove unnecessary formatting from Word

### SEO Tips
1. **Use descriptive alt text** for images
2. **Structure with headings** (H2, H3, H4)
3. **Add internal links** to related articles
4. **Use keywords** naturally in content
5. **Write compelling titles** for link text

---

## Keyboard Shortcuts

| Action | Windows/Linux | Mac |
|--------|---------------|-----|
| **Bold** | Ctrl + B | Cmd + B |
| **Italic** | Ctrl + I | Cmd + I |
| **Underline** | Ctrl + U | Cmd + U |
| **Undo** | Ctrl + Z | Cmd + Z |
| **Redo** | Ctrl + Y | Cmd + Y |
| **Copy** | Ctrl + C | Cmd + C |
| **Paste** | Ctrl + V | Cmd + V |
| **Select All** | Ctrl + A | Cmd + A |
| **Find** | Ctrl + F | Cmd + F |
| **Link** | Ctrl + K | Cmd + K |
| **Fullscreen** | Ctrl + Shift + F | Cmd + Shift + F |
| **Code View** | Ctrl + Shift + X | Cmd + Shift + X |

---

## Paste from Word

When copying content from Microsoft Word:

1. **Copy text** from Word document
2. **Paste into editor** (Ctrl + V)
3. **Automatic cleanup** - Editor removes:
   - Microsoft Office markup
   - Hidden formatting
   - Inline styles
   - Unnecessary tags
4. **Result:** Clean HTML ready for web

---

## Tables

### Create Table
1. Click **Table** → Select size (e.g., 3×3)
2. Table inserted with Bootstrap styling

### Format Table
- **Add row:** Right-click → Insert row above/below
- **Add column:** Right-click → Insert column left/right
- **Delete row/column:** Right-click → Delete
- **Merge cells:** Select cells → Right-click → Merge cells
- **Table properties:** Right-click → Table properties

### Default Styling
Tables automatically have:
- **Bootstrap class:** `table table-bordered`
- **100% width** by default
- Responsive on mobile

---

## Code View (HTML)

### When to Use
- Add custom HTML
- Embed iframes
- Insert scripts (use carefully)
- Fine-tune structure
- Clean up formatting

### How to Use
1. Click **Code** button (< > icon)
2. Edit HTML directly
3. Save changes
4. Click **Code** again to return

### Example HTML
```html
<h2>Article Section</h2>
<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text.</p>

<blockquote>
    This is a quote from someone important.
</blockquote>

<ul>
    <li>First item</li>
    <li>Second item</li>
</ul>

<img src="/uploads/articles/image.jpg" alt="Description" />
```

---

## Troubleshooting

### Editor Not Loading
**Problem:** Blank textarea instead of editor  
**Solution:**
1. Check browser console for errors (F12)
2. Verify internet connection (editor loads from CDN)
3. Clear browser cache
4. Try different browser

### Images Not Uploading
**Problem:** "Upload failed" error  
**Solution:**
1. Check image file size (must be < 5MB)
2. Verify file format (JPG, PNG, GIF, WEBP only)
3. Check `/uploads/articles/` folder permissions (755)
4. Verify `upload_image.php` file exists in admin folder

### Content Not Saving
**Problem:** Editor content lost on submit  
**Solution:**
1. Content saves automatically when form submits
2. If issues occur, copy text to clipboard before saving
3. Use **Save Draft** first, then **Publish**

### Formatting Lost
**Problem:** Styles don't appear on frontend  
**Solution:**
1. HTML is saved correctly
2. Check article detail page CSS
3. Verify Bootstrap classes applied
4. Content might need custom CSS

---

## Content Storage

### How Content is Stored
- Editor content saved as **HTML** in database
- Column: `articles.content` (LONGTEXT)
- No conversion or processing
- Direct HTML storage

### Database Field
```sql
content LONGTEXT NOT NULL
```

### Example Stored HTML
```html
<h2>Article Heading</h2>
<p>This is the first paragraph with <strong>bold text</strong>.</p>
<p><img src="http://domain.com/uploads/articles/editor_123.jpg" alt="Image" /></p>
<ul>
    <li>List item 1</li>
    <li>List item 2</li>
</ul>
```

---

## Frontend Display

### Article Page
Content displays on:
- `article.php` - Main article detail page
- Mobile app - Article reader
- RSS feed - Content export

### Rendering
```php
<?= $article['content'] ?>
```

No processing needed - HTML renders directly.

---

## Security Notes

### XSS Protection
- Editor uses TinyMCE's built-in sanitization
- Dangerous tags removed automatically
- No `<script>` tags allowed in content
- Only safe HTML elements permitted

### File Upload Security
- File type validation (whitelist)
- File size limit (5MB)
- Unique filenames prevent overwrites
- Admin authentication required

---

## Version Information

**TinyMCE Version:** 7 (Latest)  
**License:** MIT (Free for all use)  
**CDN:** Official TinyMCE CDN  
**No API Key Required:** Using community version

---

## Configuration

### Current Settings
```javascript
{
    height: 500,                    // Editor height in pixels
    plugins: [/* 13 plugins */],    // All major features enabled
    promotion: false,               // No upgrade prompts
    branding: false,                // No "Powered by TinyMCE"
    automatic_uploads: true,        // Auto-upload pasted images
    link_default_target: '_blank',  // Links open in new tab
}
```

### Customization
To modify settings, edit:
- `admin/article-add.php` (line ~608)
- `admin/article-edit.php` (line ~780)

---

## Files Modified

### Backend Files
1. ✅ `admin/article-add.php` - Added TinyMCE initialization
2. ✅ `admin/article-edit.php` - Added TinyMCE initialization
3. ✅ `admin/upload_image.php` - Created image upload handler

### No Database Changes
- Uses existing `articles.content` field
- No schema modifications needed

---

## Testing Checklist

### Basic Functionality
- ✅ Editor loads on article-add.php
- ✅ Editor loads on article-edit.php
- ✅ Toolbar buttons work
- ✅ Text formatting applies
- ✅ Content saves to database

### Image Upload
- ✅ Insert image from computer
- ✅ Paste image from clipboard
- ✅ Image uploads to `/uploads/articles/`
- ✅ Image displays in editor
- ✅ Image appears on frontend

### Advanced Features
- ✅ Tables insert correctly
- ✅ Links work (open in new tab)
- ✅ Code view shows HTML
- ✅ Fullscreen mode works
- ✅ Undo/redo functions

---

## Support Resources

### TinyMCE Documentation
- **Website:** https://www.tiny.cloud/docs/
- **API Reference:** https://www.tiny.cloud/docs/tinymce/6/apis/
- **Examples:** https://www.tiny.cloud/docs/tinymce/6/examples/

### Video Tutorials
- YouTube: "TinyMCE Tutorial"
- Official channel: TinyMCE

---

## Future Enhancements

### Potential Additions
1. **Custom CSS** - Add custom styling options
2. **Template Library** - Pre-built content templates
3. **Emoji Picker** - Better emoji insertion
4. **LaTeX Math** - Mathematical equations
5. **Syntax Highlighting** - Code blocks with colors
6. **AI Assistant** - Content suggestions (premium)

---

**Status:** ✅ Production Ready  
**Version:** 1.0  
**Last Updated:** December 8, 2025
