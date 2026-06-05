# Club Attendance & Registration System — Project Requirements

## Tech Stack
- **Backend:** PHP
- **Frontend:** HTML, CSS (with external libraries)
- **Database:** MySQL (via WAMP Server)
- **External Libraries:**
  - [Tailwind CSS](https://tailwindcss.com/) — utility-first CSS for modern, minimalist UI
  - [Font Awesome](https://fontawesome.com/) — icons
  - [DataTables](https://datatables.net/) — interactive member tables (search, sort, paginate)
  - [SweetAlert2](https://sweetalert2.github.io/) — modern alert/confirm dialogs

---

## Brand Colors
| Role | Hex |
|------|-----|
| Primary (Yellow) | `#fdd00d` |
| Secondary (Navy) | `#202959` |
| Background | `#f9f9f9` |
| Text | `#202959` |

---

## Database Schema

### Table: `clubs`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT, PK, AUTO_INCREMENT | Club ID |
| `club_name` | VARCHAR(100) | Name of the club |
| `created_at` | TIMESTAMP | Registration date |

---

### Table: `members`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT, PK, AUTO_INCREMENT | Member ID |
| `club_id` | INT, FK → clubs.id | Associated club |
| `full_name` | VARCHAR(100) | Full name |
| `phone_number` | VARCHAR(20) | Contact number |
| `role` | ENUM('President', 'Assistant President', 'Facilitator', 'Member') | Role in club |
| `level` | VARCHAR(20) | e.g., Diploma, Degree |
| `intake` | VARCHAR(20) | e.g., January 2024 |
| `group` | VARCHAR(10) | e.g., A, B, CS101 |
| `status` | ENUM('Active', 'Inactive') | Membership status |
| `created_at` | TIMESTAMP | Date joined |

---

### Table: `attendance`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT, PK, AUTO_INCREMENT | Attendance record ID |
| `club_id` | INT, FK → clubs.id | Club reference |
| `member_id` | INT, FK → members.id | Member reference |
| `date` | DATE | Attendance date |
| `status` | ENUM('Present', 'Absent', 'Late') | Attendance status |
| `created_at` | TIMESTAMP | Record timestamp |

---

## Pages & Features

### 1. Dashboard (`index.php`)
- Summary cards: Total Clubs, Total Members, Today's Attendance
- Quick links to all modules
- Color scheme: navy navbar, yellow accents

---

### 2. Register Club (`register_club.php`)
- Form fields:
  - Club Name
- On submit: insert into `clubs` table
- Show success/error via SweetAlert2
- List all registered clubs in a DataTable below the form

---

### 3. Register Member (`register_member.php`)
- Form fields:
  - Full Name
  - Phone Number
  - Role *(President / Assistant President / Facilitator / Member)*
  - Level *(e.g., Diploma, Degree)*
  - Intake *(e.g., January 2024)*
  - Group *(e.g., A, B)*
  - Club *(dropdown from `clubs` table)*
  - Status *(Active / Inactive)*
- Validation: Only **1 active President** and **1 active Assistant President** allowed per club
- On submit: insert into `members` table

---

### 4. Member List (`members.php`)
- Filter by: Club, Role, Level, Intake, Group, Status
- DataTable displaying:
  - Name
  - Phone Number
  - Role *(badge-styled: President = yellow, Asst. President = navy, Facilitator = grey, Member = light)*
  - Level
  - Intake
  - Group
  - Club
  - Status
- Actions: Edit | Delete per row

---

### 5. Club Detail View (`club_detail.php?id=X`)
- Club name header
- Sections:
  - **President** — name, phone number
  - **Assistant President** — name, phone number
  - **Facilitator(s)** — name(s), phone number(s)
  - **Members Table** — full member list with all fields

---

### 6. Attendance (`attendance.php`)
- Select Club → Select Date
- List all active members of the club
- Mark each as: `Present` / `Absent` / `Late`
- Submit saves all records to `attendance` table

---

### 7. Attendance Report (`attendance_report.php`)
- Filter by: Club, Member, Date range
- DataTable showing attendance records
- Per-member attendance summary: total present, absent, late
- Optional: export to PDF or CSV

---

## UI/UX Guidelines

- **Layout:** Sidebar navigation (navy `#202959`) + top header + main content area
- **Typography:** Clean sans-serif (e.g., Inter via Google Fonts)
- **Buttons:** Primary = `#fdd00d` with navy text; Secondary = `#202959` with white text
- **Cards:** White background, subtle box-shadow, rounded corners (`border-radius: 12px`)
- **Tables:** Alternating row shading, sticky header, responsive via DataTables
- **Forms:** Floating labels or clean label-above-input layout, full-width on mobile
- **Alerts:** SweetAlert2 for all confirmations and notifications
- **Mobile responsive:** All pages adapt to smaller screens

---

## File Structure (Suggested)

```
/project-root
│
├── index.php                  # Dashboard
├── register_club.php          # Register new club
├── register_member.php        # Register new member
├── members.php                # Member list
├── club_detail.php            # Club detail + roles view
├── attendance.php             # Take attendance
├── attendance_report.php      # View attendance reports
│
├── /includes
│   ├── db.php                 # MySQL connection
│   ├── header.php             # Navbar + sidebar
│   └── footer.php             # Footer + scripts
│
├── /assets
│   ├── /css
│   │   └── style.css          # Custom styles
│   └── /js
│       └── main.js            # Custom JS
│
└── /sql
    └── schema.sql             # Full database schema
```

---

## Notes for Codex / Code Generation
- Use **PDO** for all database interactions (prepared statements, no raw queries)
- Sanitize all user input server-side
- Use `session_start()` if admin login is added later
- Keep PHP logic separated from HTML as much as possible
- All forms use `POST` method
- Date format: `Y-m-d` (MySQL standard)
