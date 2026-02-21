# REDAXO Code Editor AddOn

Modern code editing experience for REDAXO CMS with Monaco Editor (VS Code) integration.

## Features

### 🎨 **Monaco Editor Integration**
- **Monaco Editor** (VS Code editor) for `.rex-code` textareas
- **Local installation** - no CDN dependencies, works offline
- **REDAXO Custom Themes** - Dark & Light themes matching REDAXO design
- **Syntax highlighting** for PHP, JavaScript, CSS, HTML, SQL, JSON
- **Code snippets** with 15+ REDAXO variable categories
- **Intelligent autocomplete** with context-aware suggestions
- **Multiple cursors** and advanced editing features

### 🛠️ **File Browser**
- **NextCloud-style interface** for file management
- **Live full-text search** across all project files
- **Line navigation** - jump directly to search results
- **Admin access only** - restricted to administrators
- **Auto-deactivation** - deactivates after 2 days of inactivity

### 🔐 **Safety Features**
- **Automatic backups** before every file modification
- **Virtual trash system** - restore deleted files
- **Protected system files** - prevents critical file deletion
- **Backup management** with restore functionality
- **Data cleanup** on deactivation

### 📝 **Editor Toolbar Controls**
- **Theme Switcher** - REDAXO Dark/Light, VS Dark/Light, High Contrast
- **Font Size** - adjustable from 10px to 22px
- **Line Numbers** - toggle on/off
- **Word Wrap** - toggle line wrapping
- **Minimap** - code overview panel
- **Whitespace** - show/hide spaces and tabs
- All settings saved per user via localStorage

## Auto-Deactivation System

The File Browser deactivates automatically after 2 days of inactivity for security:

- ✅ **AddOn stays active** - only File Browser/Search/Backups require reactivation
- ✅ **Monaco Editor keeps working** - `.rex-code` enhancement remains active
- ✅ **Code Snippets remain** - all editing features stay available
- ⏱️ **2-day timer** - resets on every File Browser access
- 🔓 **Easy reactivation** - click button with security warning
- 🗑️ **No data loss** - backups and trash are preserved

### Reactivation Workflow

When File Browser is deactivated:
1. Navigate to **Code → File Browser**
2. See security warning with 5 important points
3. Click **"File-Browser aktivieren (2 Tage)"**
4. Timer resets for 2 more days
5. Alternative: Enable in **Settings** page

## Configuration

Configure via **Code → Settings**:

### Editor Settings
- **Monaco Editor aktivieren** - Replace `.rex-code` textareas with Monaco Editor
- **File-Browser aktivieren** - Enable/disable File Browser (also Search & Backup)

### Backup Settings
- **Auto-Backup** - Create backup before every save
- **Backup-Limit** - Max backups per file (5-100)
- **Papierkorb-Limit** - Max files in trash (10-100)

## `.rex-code` Exclusion List

Some textareas are automatically excluded from Monaco Editor replacement:

### Manual Exclusion
Add class `code_disable` to any textarea:
```html
<textarea class="rex-code code_disable">
    This will NOT be replaced by Monaco Editor
</textarea>
```

### Automatic Exclusions
- **MBlock elements** - `.mblock-item textarea.rex-code`
- **MForm Repeater** - `.mform-repeater-item textarea.rex-code`

This prevents DOM conflicts with dynamic form builders.

## Code Snippets

15+ categories with 200+ snippets for REDAXO development:

- **REX_VALUE** - Module values with all parameters
- **REX_MEDIA** - Media handling and widgets
- **REX_MEDIALIST** - Media lists and loops
- **REX_LINK** - Article links and URLs
- **REX_LINKLIST** - Link lists and navigation
- **REX_ARTICLE** - Article content and fields
- **REX_CATEGORY** - Category data and navigation
- **REX_TEMPLATE** - Template handling
- **REX_CONFIG** - Configuration access
- **REX_CLANG** - Multi-language support
- **REX_USER** - User information
- **REDAXO Core** - Common API calls
- **MForm** - Form builder elements
- **MBlock** - Repeatable content blocks
- **Parameter** - Variable parameters

All snippets include PHP and placeholder variants.

## Installation

1. **Upload AddOn** to REDAXO
2. **Monaco Editor Setup**: 
   ```bash
   cd redaxo/src/addons/code
   npm install
   npm run build
   ```
3. **Install and activate** the AddOn
4. **Configure** in Code → Settings

## System Requirements

- **REDAXO 5.18+**
- **PHP 8.1+**
- **Node.js & NPM** (for Monaco Editor setup)
- **Administrator permissions**

## Security Considerations

### ⚠️ File Browser Access
- **Admin-only** - restricted to administrator accounts
- **Auto-deactivation** - File Browser deactivates after 2 days
- **Security warning** - shown before reactivation
- **Audit trail** - all actions logged

### 🔒 Protected Files
System-critical files cannot be deleted:
- `index.php`, `.htaccess`
- `config.yml`, `package.yml`
- Core system files

### 💾 Backup System
- **Automatic backups** before file modifications
- **Configurable retention** (5-100 backups per file)
- **Restore functionality** via Backup & Trash page
- **Manual cleanup** or automatic on deactivation

## Usage

### Monaco Editor for Modules
The Monaco Editor automatically replaces `.rex-code` textareas in:
- Module Input/Output
- Template editor
- YForm action fields
- Any custom `.rex-code` textarea

**Exclude specific textareas:**
```html
<textarea class="rex-code code_disable">
    <!-- This textarea keeps default editor -->
</textarea>
```

### File Browser
1. Navigate to **Code → File Browser**
2. Browse project files (if enabled)
3. Click file to open in editor
4. Edit and save (automatic backup created)
5. Use search for quick file finding

### Search
1. Go to **Code → Code Search**
2. Enter search term (minimum 2 characters)
3. Results show filename, line number, context
4. Click result to open file at specific line

### Backup & Trash
1. Navigate to **Code → Backup & Trash**
2. **Backups tab** - restore previous file versions
3. **Trash tab** - restore or permanently delete files

## Keyboard Shortcuts

In Monaco Editor:
- `Ctrl/Cmd + S` - Save file
- `Ctrl/Cmd + F` - Find in file
- `Ctrl/Cmd + H` - Replace in file
- `Ctrl/Cmd + /` - Toggle line comment
- `Alt + Click` - Multiple cursors
- `Ctrl/Cmd + D` - Select next occurrence
- `F11` - Fullscreen mode (File Browser only)

## Troubleshooting

### Monaco Editor not loading
1. Check browser console for errors
2. Verify Monaco is built: `npm run build`
3. Clear browser cache
4. Check file permissions on `assets/` folder

### `.rex-code` replacement not working
1. Check Settings: "Monaco Editor aktivieren" must be "Aktiviert"
2. Verify textarea has class `rex-code`
3. Check if textarea is inside `.mblock-item` or `.mform-repeater-item`
4. Check for `code_disable` class

### File Browser deactivated
This is normal after 2 days of inactivity:
1. Go to Code → File Browser
2. Read security warning carefully
3. Click "File-Browser aktivieren (2 Tage)"
4. Or enable in Settings page

## Configuration via package.yml

```yaml
config:
    # Auto-deactivation after X days (0 = disabled)
    auto_deactivate_after_days: 2
    # Clean data when File Browser deactivates
    cleanup_data_on_deactivate: true
```

## Development

### Building Monaco Editor
```bash
npm install
npm run build
```

This creates optimized Monaco Editor bundle in `assets/monaco-editor/`.

### Adding Snippets
Edit `assets/code-editor.js` → `getSnippets()` method.

### Custom Themes
Monaco themes are defined in `assets/code-editor.js`:
- `redaxo-dark` (default)
- `redaxo-light`

## License

MIT License - see [LICENSE](LICENSE) file.

## Author

**Friends Of REDAXO**
Developed for REDAXO CMS

## Support

- **GitHub Issues**: Report bugs and feature requests
- **REDAXO Slack**: Community support in #addons channel
- **Documentation**: See MONACO_SETUP.md for editor details

---

**Remember:** This AddOn provides powerful file editing capabilities. Use responsibly, especially on production servers. The auto-deactivation system helps prevent long-term security risks.
