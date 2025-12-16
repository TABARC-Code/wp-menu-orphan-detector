
## `CHANGELOG.md`

```markdown
# Changelog

## 1.0.0

- First public release.
- Added Appearance  
  Menu Orphans screen.
- Scans all nav menus on the site:
  - Counts total menus and items.
- Broken targets:
  - Detects menu items of type post type where the post no longer exists or is not publicly available.
  - Detects menu items of type taxonomy where the term no longer exists.
- Orphaned children:
  - Detects child items whose parent menu item is missing or points at missing content.
- Suspicious internal custom URLs:
  - Flags custom menu items using internal URLs that do not appear to resolve to any post or page.
- Read only inspection:
  - No automatic deletion or editing of menus.
- Licensed under GPL-3.0-or-later.
