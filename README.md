# Slide Merge for ProPresenter

Turn one ProPresenter slide and a spreadsheet into a set of presentations, one per row, ready to drop into your library.

Slide Merge is a mail merge for ProPresenter. Design a speaker or name slide once, paste in a list of names and titles, and download a zip of finished `.pro` files. It runs entirely in the browser, so there's nothing to install and your list never leaves your computer.

**Try it:** https://grantbivens.com/propresenter-slide-merge/

## Features

- **Uses your own design.** Start from any `.pro` file. Fonts, colors, backgrounds and layout carry over unchanged.
- **Paste from any spreadsheet.** Copy cells from Excel, Numbers or Google Sheets, or upload a CSV.
- **Combine columns and text.** Fill a box with `{First Name} {Last Name}`, `Pastor {Last Name}`, or any mix of columns and words.
- **Keep or clear the template text.** Leave a box as designed, or blank it out on every slide.
- **Preview every slide.** Page through with the arrow buttons or the ↑ ↓ keys. Text that may not fit is flagged.
- **Name the files your way.** Build file names from columns, for example `Fall Retreat - {Last Name}`. Duplicate names are numbered.
- **Private by design.** Templates and lists are read and built in the browser. Nothing is uploaded.
- **Optional usage counter.** If you host it with PHP and MySQL, an anonymous count of merges is recorded. No names or files are stored.

## How to use it

1. **Build one slide** in ProPresenter the way every copy should look. Type placeholder text, such as "Name" and "Title," into each box you want filled in. Use one style per box.
2. **Find the `.pro` file.** On a Mac, libraries are stored in
   `~/Library/Application Support/RenewedVision/ProPresenter/UserWorkspaces/ProPresenter/Libraries`, one folder per library.
3. **Open Slide Merge**, drop in the `.pro` file and paste your list.
4. **Choose what goes in each text box** and how to name the presentations.
5. **Download the zip**, unzip it, and drag the `.pro` files onto a library in ProPresenter.

## Hosting your own copy

### Just the tool

`index.html` is a single self-contained file. Put it on any web host, or open it directly in a browser. The usage counter is skipped if `count.php` isn't there.

### With the usage counter (PHP and MySQL)

Requirements: PHP 7.4 or later with PDO MySQL, and a MySQL or MariaDB database.

1. Upload the folder to your web server.
2. Create a database and a user with **SELECT**, **INSERT** and **CREATE** privileges. The table is created on first use. To grant only SELECT and INSERT, run `schema.sql` once first.
3. Copy `config.example.php` to `config.php` and fill in your database details and a stats password.
4. Open `stats.php` and sign in to see merges by day and month.

Each merge stores only the time and how many presentations were made. `.htaccess` keeps the settings files off the web on Apache, and `config.php` and `lib.php` print nothing if requested directly on other servers.

## Files

| File | Purpose |
|---|---|
| `index.html` | The tool. Runs in the browser. |
| `count.php` | Records an anonymous count after each download. |
| `stats.php` | Password-protected usage totals. |
| `config.example.php` | Settings template. Copy to `config.php`. |
| `lib.php` | Shared database code. |
| `schema.sql` | Optional table setup. |
| `.htaccess` | Blocks web access to settings files on Apache. |

## How it works

A `.pro` file is a Protocol Buffers message, with each text box's content stored as RTF. Slide Merge parses the file into a generic field tree, so every setting it doesn't understand is written back byte for byte. For each row it:

- swaps the text inside each mapped box while keeping the RTF font, size and color,
- updates the character ranges ProPresenter uses for styling such as All Caps,
- gives the presentation, slides and elements new IDs so ProPresenter treats each file as a new presentation,
- keeps the reference to the background media unchanged.

## Limitations

- Tested with ProPresenter 21.4 on macOS. Other versions may work, but the `.pro` format isn't published and could change in an update. Try one file before importing a large batch, and back up your library first.
- Each row becomes its own presentation. Multiple slides per presentation aren't supported yet.
- Text boxes that mix styles inside their text, such as one bold word, are left unchanged.
- Backgrounds and other media are linked by their location on the computer that made the template. Import on a computer that has the same media.
- Fit warnings are estimates, because the browser doesn't have your slide's exact fonts.
- Excel files can't be uploaded directly. Paste the cells or save as CSV.

## License

Released under the [MIT License](LICENSE). You're free to use, change and share it.

## Disclaimer

Slide Merge is an independent project. It isn't made by or affiliated with Renewed Vision. ProPresenter is a trademark of Renewed Vision.
