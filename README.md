# wp-menu-orphan-detector
 Scans nav menus for items that point at missing posts, terms or broken internal URLs and lists them so I know what is quietly rotting.  
<p align="center">
  <img src=".branding/tabarc-icon.svg" width="180" alt="TABARC-Code Icon">
</p>

# WP Menu Orphan Detector

Nav menus age badly.

Pages get deleted. Categories get cleaned up. Campaign landing pages vanish. The one thing that never gets updated is the navigation. Users click around, hit dead links, and quietly lose confidence in the site.

This plugin does not rearrange your menus. It simply points at the items that are clearly lying about where they go.

## What it does

Under Appearance you get a screen called Menu Orphans.

It scans all nav menus and:

- Counts how many menu items exist in total
- Flags menu items that point at posts that no longer exist
- Flags menu items that point at terms that no longer exist
- Flags child items whose parent menu item is missing or broken
- Tries to spot custom URLs that look like broken internal links

Nothing is updated automatically. You still go to Appearance, Menus and do the manual surgery. This just hands you a list of likely infections.

## Checks it runs

### Items pointing at missing content

For menu items of type:

- `post_type`  
- `taxonomy`  

It checks:

- Does the referenced post or term exist  
- If it exists, is it at least publish or private  

If not, the item is listed with a reason such as:

- Linked post no longer exists
- Linked term no longer exists
- Linked post exists but has status draft

These are the obvious broken links inside your own site.

### Items with missing parents

For child menu items, it checkss:

- Does the parent menu item still exist  
- Does the parent itself point at something valid  

If the parent is missing or points at missing content, the child is reported as an orphan.

On the front end they may still appear, but the menu hierarchy is compromised and confusing.,

### Suspicious internal custom URLs

Custom menu items are trickier.

This plugin only cares about custom menu items where:

- The URL starts with your site home URL  
- It does not appear to match any current post or page  

It uses `url_to_postid` as a roughh signal and then asks:

- Does this resolve to a live post or page  
- If not, it is listed as suspicious  

External links are ignored. External sites are allowed to be broken on their own time.

## What it does not do

This is intentionally limited.

It does not:

- Delete menu items
- Edit menu items
- Clean up menus on save
- Perform live HTTP checks against URLs
- Guess what you meant to link to

It only inspects the structural relationship between menus and your own content.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- Ability to manage theme options

Nothing exotic beyond that.

## Installation

Clone or download the repository:

```bash
git clone https://github.com/TABARC-Code/wp-menu-orphan-detector.git
Place it in:

text.
Copy code
wp-content/plugins/wp-menu-orphan-detector
Activate it in the Plugins screen.

Then go to:

Appearance

Menu Orphans

If you do not see it, check your permissions.

How to use it sensibly
Step 1
Look at the summarry box.

You get:

Number of menus scanned

Total items

Count of items pointing at missing content

Count of children with missing parents

Count of suspicious internal custom URLs

If those last three are zero, you have other problems today. If they are not, this is where you start.

Step 2
Review items pointing at missing content.

For each:

Note the menu

Note the label

Read the reason

Open Appearance, Menus in another tab, find the menu and either:

Fix the target

Replace the item

Remove it if it is genuinely obsolete

Do this calmly. The navigation is one of the few parts of a site users constantly see. Broken links here hurt more than broken links in old posts.

Step 3
Review orphan children

If you see a lot of children with missing parents:

You probably deleted some top level items without cleaning the tree

The menu hierarchy will be unclear to humans

Reparent those children or flatten them, based on what makes sense for the site.

Step 4
Review suspicious custom URLs

These are not guaranteed broken, but:

They look internal

They do not resolve to a known post or page

Click them in a browser before you delete anything. Some may point at custom endpoints or shortcode driven templates.

Limitations
This is a first pass, not a full navigation reconstruction engine.

Limitations include:

url_to_postid is not perfect. Some complex URLs will not be detected as valid.

Custom routing, front end frameworks and wild permalink rules will confuse the heuristics.

Only standard post type and taxonomy menu items are inspected in depth.

It does not account for legacy content intentionally left unpublished.

The output is a list of suspects, not a court verdict.

Good times to run this
Before redesigns that keep the same menu structure

After major content audits

Before handing a site back to a client

When you inherit a site and want to know how rotten the navigation is

You do not need this running every day. Use it as a periodic health check.

Roadmap
Things that might appear later if patience holds:

CSV export of all flagged items

Per menu filter in the UI

Integration with theme locations so you can focus on primary navigation

A compact summary widget on the dashboard

Things that should not appear:

Automatic deletion

Live crawling of every URL in every menu

Magic repair buttons that guess target content

This is meant to stay narrow and honest.
