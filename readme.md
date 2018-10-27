# All-in-One Event Calendar Fixes

**Requires at least**: 4.8

**Tested up to**: 4.9.8

**Requires PHP**: 5.6

**License**: GPLv3

**License URI**: https://www.gnu.org/licenses/gpl.html


## Description

All-in-One Event Calendar Fixes And Event related improvements

## Version history

### v1.5.6: Cron scheduler, Menu page (+fix)

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.6)

- Added cron scheduler
  - changes category/tag assignment cron schedule and changes it:
    - hourly on day of newsletter (notification)
    - twicedaily otherwise
- Changed settings page from option to menu page (with icon)
- Fix: added .wrap class to settings page

### v1.5.5: Bugfix with autolink

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.5)

Bugfix with autolink function

### v1.5.4: Last sent date comparison bugfix, debug logging

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.4)

- Last sent date comparison bugfix
- Turned on debug logging at notification send time

### v1.5.3: Case sensitive keyword matching, refactoring of kwd matching

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.3)

- Case sensitive keyword matching by enclosing keyword in single quotes (" ' ")
- Refactored the keyword matching method

### v1.5.2: Tab title fix

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.2)

Tab title typo fix

### v1.5.1: whole keyword matching, negative matching

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.1)

- whole keyword matching except if starts/ends with '.'
  - multi-byte matching
- negative matching (starts with '!')

### v1.5.0: Matching new categories / tags (even if processed in past)

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.5.0)

- if there are new terms (categories/tags), all posts are rechecked if the new terms are matched
  - if single category checking is on, no rechecking of **categories** takes place for posts **with categories**
  - if relisting of posts already processed in the past is off, the new categories/tags are checked nevertheless
- whenever a post is checked for categories, it's checked for tags as well
- no terms are removed
- previously added terms are shown with 'already assigned' reason in reports

### v1.4.3: Keyword combinations + skipping empty keywords

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.4.3)

- added keyword combinations (defined by keywords separated by a '+' sign)
- added checking for empty keywords (if a comma/'+' was left at the start/end of the list)

### v1.4.2: added .org to link matcher

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.4.2)

- added .org to link matcher

### Ordering of categories

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.4.1)

Ordering of categories: festivals first, then parties, then the rest

### v1.4.0: link matching in excerpts

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.4.0)

- fixed excerpt lines being glued together without a space
- excerpts will contain nice anchor links (applies to event popups, newsletters)

### Bugfix: post ids saved when previewing

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.3.4)

- Bugfix: automatic term assignment: post IDs were being saved when previewing

### Fix: Making sure used post ids are saved and saving report if mail not sent

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.3.3)

- Making sure used post ids are saved and saving report if mail not sent

### Notification time check timezone bugfix

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.3.2)

- Bugfix: checking time with timezone offset when sending newsletter reminder

### Minor improvements

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.3.1)

- making sure notifications about used posts don't get through and that an empty mail is not sent

### Added possibility to exclude previously used events

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.3.0)

- added option to skip processing and notifications about events that had already been included in a past report

### Automatic category and tab assignment

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.2.0)

- automatic category and tab assignment finished
- email report with preview
- cleanup of options that weren't specific to a tab (with backward compatibility)

### Reminder bugfix on day matching

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.1.1)

- Bugfix: matching day in newsletter reminder cron

### Newsletter reminder

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.1.0)

- send notification to send newsletter on given day at given time
- CSS and JS version bump
- fixes in option saving
- remember selected option tab on reload

### Official first release

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v1.0.0)

- localisation
- options page (with CSS, JS scripts, HTML view)
- improved localisation fixes

### Initial release

[View on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases/tag/v0.1.0)

- add location overrides based on Venue, Contact name, Address
- add location override (metabox) for specific event
- add missing featured images
- parse contact and featured image at importing time


[View the rest on Github](https://github.com/charliecek/all-in-one-event-calendar-fixes/releases?after=v0.1.0)