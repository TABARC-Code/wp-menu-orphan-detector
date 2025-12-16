# IdiotsGuide  
WP Menu Orphan Detector

This is for the version of me who knows the menu is a mess but does not feel like hunting through twenty dropdowns in Appearance, Menus.

No judgement. Just a shortcut.

## What problem this is actually solving

Over time, every site ends up with:

- Menu items pointing at pages that got deleted
- Category links that go nowhere..
- Child items under parents that no longer exist
- Custom links glued to internal URLs that are wrong

Nobody notices until a user clicks something important and hits a dead end.

This plugin does one thing. It reads all the menus and says:

- These items are clearly broken
- These children are hanging under missing parents
- These custom internal links look suspicious

It does not fix anything. That part is still on you.

## Where to click

After activation:

1. Log into the WordPress admin.
2. Go to Appearance.
3. Click Menu Orphans.

If you do not see it, you probably do not have permission to edit theme options.

## Reading the summary

At the top you get a small summary table:

- Menus scanned
- Total menu items
- Items pointing at missing content
- Children with missing parents
- Suspicious internal custom URLs.,

If the last three lines are all zero, either your navigation is actually clean or the damage is in external links and custom logic.

If they are not zero, you have some work to do.

## What each section means

### 1. Items pointing at missing content

These are the direct lies.

The plugin checked items that should point at:

- Posts or pagess.
- Terms like categories or tags

It found that:

- The post or term does not exist
- Or exists but is not publicly available

These are almost always unintentional leftovers from old content.

When you see an item in this list:

- Note which menu it belongs to
- Note its label and id
- Go to Appearance, Menus and find it
- Decide whether to:
  - Point it at something real
  - Replace it with a new item
  - Remove it

If the reason says the post is draft or private, that might be deliberate. Check with whoever runs content before ripping it out.

### 2. Items with missing parents

These are the lost children.

Each one is:

- A menu item that claims to have a parent
- The parent item no longer exists
- Or the parent item points at missing content

The menu might still render them somewhere. The structure will be weird.

You can:

- Reassign them to a different parent
- Promote them to top levell.
- Remove them if they are relics

High counts here usually mean someone deleted a top level menu item and forgot about the stuff under it.

### 3. Suspicious internal custom URLs

These ones are awkward.

Each one is:

- A custom menu item
- Its URL starts with your site domain
- The target does not seem to map to a real post or page

Examples include:

- Old slugs after a permalink change
- Hard coded paths that no longer exist
- Links to one off landing pages that were removed

For each:

- Click it in a browser
- If it still goes somewhere meaningful, leave it
- If it is broken, point it at somewhere real or remove it

Do not blindly delete these. A few will be false positives, especially on sites with custom routing.

## Safe way to use this

If you want to avoid making things worse:

1. Run this on a staging copy first if you have one.
2. Start with the items pointing at missing content.
3. Fix or remove a handful at a time.
4. Thn look at orphan children..
5. Then deal with suspicious custom URLs.

After each batch of changes:

- View the front end
- Click through the menus yourself
- Make sure you did not break anything that was still working

Slow and slightly boring beats fast and destructive.

## Things this plugin wil not tell you

There are limits.

It will not:

- Tell you if a link goes to the wrong page, only if it goes to nothing
- Know if a custom external URL is broken
- Understand custom routing from weird theme frameworks
- Handle deeply bespoke menu walkers or JavaScript menus that bypass standard data

It lives entirely in normal WordPress menus and their relationship to core content.

## When to run it

Times this is worth five minutes:

- Before handing a site over to a client
- Before or after a big content pruning session
- After changing permalinks or reworking information architecture
- When you inherit a terrifying old site and need a quick nav sanity check

You do not need to keep this running every day. Install, scan, clean, uninstall if you like. It is a maintenance tool, not a daily dashboard.

## Mental shortcuts

If you are tired and just want the headlines:

- First section = hard broken links to internal content
- Second section = weird tree structure, children with dead parents
- Third section = internal custom URLs that do not match real content

Fix the first list. Clean the second list if you care about structure. Treat the third list with caution.

## Final note

WordPress happily lets menus point at nowhere and never warns you.

This plugin is the slightly grumpy colleague who taps the menu and says:

"You know half of these links are lying, right"

What you do about that is your call.
