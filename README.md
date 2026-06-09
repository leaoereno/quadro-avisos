# module-zbx-notice-board

Zabbix 7.0 LTS module for communicating incidents, change requests and events
to teams directly inside Zabbix. Supports Markdown/HTML, scheduling,
user group filtering and themed cards.

---

## Features

### Dashboard Widget
- Widget available at **Dashboard > Notice Board**
- Shows only **active** notices (within the inicio/fim window)
- Respects the **usergroup** of the logged-in user
- Click-to-expand modal with full content
- CSS adapts to the user theme (dark / blue / default / high-contrast)

### Admin Menu
- New **Notice Board** menu item under **Monitoring** (all users) and **Administration** (Super Admin)
- Visible to **Admin** (type 2) and **Super Admin** (type 3) in admin mode
- List all notices with search, type and status filters
- Create, edit and delete per card

### Notice Form
- **Markdown and HTML** support in content
- Editor with tabs: Editor / Preview / Split
- Live card preview before saving
- Border type / severity:
  - `info`    -- Informational (blue)
  - `success` -- Resolved (green)
  - `warning` -- Warning (yellow)
  - `danger`  -- Critical / Urgent (red)
  - `mudanca` -- Change Request (purple)
  - `evento`  -- Event / Maintenance (cyan)
- Scheduling: display from/until
- User group selection (single for Admin, multi for Super Admin)

### REST API (v1.4.0)
- `GET  /api/avisos`       -- List notices with filters and pagination
- `GET  /api/avisos/{id}`  -- Get notice by ID
- `POST /api/avisos`       -- Create notice from external source
- Bearer Token / X-Api-Token authentication
- `source` field to identify remote origin (Grafana, ServiceNow, etc.)
- Interactive Swagger UI at `api/docs.html`

---

## File Structure

```
modules/module-zbx-notice-board/
+-- manifest.json
+-- Module.php
+-- install.sql
+-- README.md
+-- actions/
|   +-- CControllerNoticeBoardCreate.php
|   +-- CControllerNoticeBoardDashboard.php
|   +-- CControllerNoticeBoardDelete.php
|   +-- CControllerNoticeBoardEdit.php
|   +-- CControllerNoticeBoardSave.php
|   +-- CControllerNoticeBoardView.php
+-- views/
|   +-- notice.board.create.php
|   +-- notice.board.dashboard.php
|   +-- notice.board.view.php
|   +-- widget.notice_board.view.php
+-- assets/
|   +-- css/notice_board.css
|   +-- js/notice_board.js
+-- locale/
|   +-- en_US/LC_MESSAGES/module.po
|   +-- pt_BR/LC_MESSAGES/module.po
+-- api/
    +-- index.php
    +-- docs.html
    +-- migrate_v1.2_to_v1.3.1.sql
    +-- migrate_v1.4.sql
```

---

## Installation

### Fresh install

#### 1. Database
```bash
mysql -u root -p zabbix < /path/to/modules/module-zbx-notice-board/install.sql
```

#### 2. Copy the module
```bash
cp -r module-zbx-notice-board /usr/share/zabbix/modules/
chown -R www-data:www-data /usr/share/zabbix/modules/module-zbx-notice-board
```

#### 3. Enable in Zabbix
1. Go to **Administration > General > Modules**
2. Find **Notice Board** and click **Enable**

#### 4. Add widget to Dashboard
1. Go to a **Dashboard** > **Edit**
2. Click **Add Widget** > choose **Notice Board**

---

## Upgrading from quadro-avisos

### From v1.2 to v1.3.1 (adds para_todos column)
```bash
mysql -u root -p zabbix < api/migrate_v1.2_to_v1.3.1.sql
```

### From v1.3.1 to v1.4.0 (renames table, adds source column)
```bash
mysql -u root -p zabbix < api/migrate_v1.4.sql
```

---

## Permissions

| Action                  | User | Admin | Super Admin |
|-------------------------|------|-------|-------------|
| View widget             |  v   |   v   |      v      |
| View Notice Board menu  |  x   |   v   |      v      |
| Create / Edit notice    |  x   |  v*   |      v      |
| Delete notice           |  x   |  v*   |      v      |
| Manage any group        |  x   |   x   |      v      |

*Admin can only manage notices they created, within their own groups.*

---

## API

Configure the token in `api/index.php`:
```php
$API_TOKENS = [
    'your-secret-token',
];
```

Open the Swagger UI at:
```
http://your-zabbix/zabbix/modules/module-zbx-notice-board/api/docs.html
```

---

## Dependencies

- **marked.js** loaded via CDN for Markdown rendering.
  For air-gap environments, download and place at `assets/js/marked.min.js`
  then update the CDN URL in `assets/js/notice_board.js`.

---

## Compatibility

- **Zabbix:** 7.0 LTS
- **PHP:** 8.0+
- **MySQL / MariaDB:** 5.7+ / 10.3+
- **Browsers:** Chrome 90+, Firefox 88+, Edge 90+

---

## License

Modulo livre - fork and be happy

**Author:** Rafael M. A. Leao Ereno
**Email:** leao@leaoereno.com.br
**LinkedIn:** https://www.linkedin.com/in/leaoereno/
**GitHub:** https://github.com/leaoereno/module-zbx-notice-board

---

## Buy me a Coffee

If this module was useful for you or your team, consider supporting development!

https://www.buymeacoffee.com/leaoereno
