# Bangron Studio – Design System v3 “Clarity”

Tujuan: konsisten, cepat dipahami, RBAC-aware.

## Design Tokens

Color
- bg: #0f1117 / #10131b
- surface: #161922 / slate-900
- surface-hover: #1c2030
- border: rgba(255,255,255,0.07) / slate-800
- text-primary: #e6e8f0
- text-secondary: #8b92a7
- text-muted: #5a627a
- primary: indigo-500 #6366f1 → violet-600
- success: emerald-400
- warning: amber-400
- danger: red-400
- info: sky-400

Type
- font: Inter / ui-sans-serif system
- h1: 24px / 700
- h2: 18px / 700
- body: 14px / 400
- caption: 12px
- mono: JetBrains Mono / ui-monospace – untuk _id, db, code

Spacing
- page padding: 24px mobile, 32px desktop
- card padding: 20px
- gap grid: 16px
- radius: 16px (card), 12px (input), 9999px (badge/pill)

Elevation
- card: bg-slate-900/60 + backdrop-blur + border-slate-800
- hover: border-indigo-500/30 + shadow-indigo-900/10
- modal: bg-slate-900 border-slate-700 shadow-2xl

## Layout – App Shell v3

```
┌──────────────┬─────────────────────────────────────┐
│              │ Topbar 56px                         │
│  Sidebar     │  [breadcrumbs]  [search ⌘K] [🔔] [👤] │
│  260px       ├─────────────────────────────────────┤
│  fixed       │                                     │
│              │  Main Content                       │
│  [Logo]      │  max-w-7xl mx-auto                  │
│              │  padding 24-32px                    │
│  NAV GROUPS  │                                     │
│  · Overview  │  ┌─────────┐ ┌─────────┐          │
│  · Content   │  │ KPI    │ │ KPI    │ …          │
│    DB tree   │  └─────────┘ └─────────┘          │
│  · Access*   │  ┌ Role Widgets ─────────┐         │
│  · System*   │  │ … dynamic …           │         │
│              │  └───────────────────────┘         │
│              │  ┌ Database Cards ───────┐         │
│  [user pill] │  │ …                     │         │
└──────────────┴─────────────────────────────────────┘
* Access/System muncul sesuai role
```

Sidebar sections (RBAC filtered):
- General
  - Dashboard / Overview  (all)
- Content
  - Databases tree (read+)
  - Query Builder (read+)
- Access Control  – visible admin+, superadmin
  - Users
  - Roles
  - Permissions  [NEW badge]
  - Tokens / API Keys
  - ACL Matrix
- Workflow – visible editor+
  - My Drafts
  - Publish Queue
- System – superadmin only
  - Health
  - Audit Log
  - Config
  - Encryption

Topbar (56px sticky):
- left: breadcrumbs  “Databases / app / posts”
- center: global search ⌘K → fuzzy jump to db/collection/document
- right: 
  - 🔔 notifications (3)
  - role pill
  - user avatar dropdown: Profile • API Keys • Logout

## User Flow – yang disederhanakan

```
[ /auth/login ]  Session login
      │
      ▼
[ / ] Dashboard
  ├─ KPI cards (DB, collections, docs, health)
  ├─ Role Widget:
  │   editor → My Drafts
  │   admin  → Audit Log + Recent Users
  │   superadmin → System Health + quick admin links
  └─ Database Grid
      │
      ▼ click DB
[ /databases/{db} ]  Collections list
      │
      ▼ click collection
[ /databases/{db}/collections/{col} ]  Spreadsheet Data Browser
  ├─ toolbar: search | filter | column toggle | export | import
  ├─ DataTable: sort, inline edit, select rows
  └─ ► [New] → DocumentFormModal (auto-generated dari schema)
      ► Row click → Expand JSON / Edit
```
Admin flows terpisah jelas di sidebar kiri bawah (Access Control), tidak campur dengan data browsing.

2 jalur auth jelas di UI:
- Web: “Signed in as superadmin · session” – pill hijau di topbar
- API: tab “Tokens” → “Generate API Key” → copy once → docs curl example

## Component library – konsisten

Semua di `frontend/src/Components/UI/`:

- `<UiCard>` – `bg-slate-900/60 border-slate-800 rounded-2xl p-5`
- `<UiButton variant="primary|ghost|danger" size="sm|md">`
- `<UiInput>`, `<UiSelect>`, `<UiTextarea>` – bg-slate-950, border-slate-800, focus:ring-indigo-500
- `<UiBadge color="emerald|amber|red|indigo|slate">`
- `<UiTable>` – sticky header, zebra hover
- `<UiModal>` – centered, backdrop-blur
- `<UiEmptyState icon="" title="" action="">`
- `<UiPageHeader title="" description="" :actions="[]">`
- `<Topbar>` + `<Sidebar>` + `<Breadcrumbs>`

Semua page mulai dengan:
```vue
<UiPageHeader
  title="Users"
  description="Manage user accounts • auth.users – role = SINGLE relation"
  icon="Users"
>
  <template #actions>
    <UiButton @click="openCreate">New User</UiButton>
  </template>
</UiPageHeader>
```

## Role-based UI states

- Tombol yang user tidak punya permission → `disabled` + tooltip “Requires: export”
  bukan hilang total (biar user tahu fitur ada, tinggal minta akses)
- Sidebar nav item yang tidak boleh akses → hidden sepenuhnya
- Dashboard widget → render sesuai `role`
- Form field yang `readonly` / `hidden` di schema → otomatis disabled / tidak render (sudah via DocumentFormModal)

## Before → After

Before (v2.0):
- Nav campur: Databases + Users di 1 list flat, 2 mode Editor/Developer membingungkan
- Tiap page header beda style, ada yang pakai card-hover, ada yang tidak
- Users page: roles checkbox multiple, tidak jelas relation
- Roles page: permissions hardcode list, tidak grouped
- Tidak ada Permissions page
- Login simpan JWT di localStorage, token kadaluarsa silent
- Dashboard hanya KPI + DB cards – tidak ada context role

After (v2.3 Clarity):
- **AppShell 2-column konsisten**: Sidebar 260px (RBAC filtered) + Topbar 56px (search, user)
- **Design tokens locked**: 1 card style, 1 button style, 1 input style
- **User flow 3 klik**: Dashboard → Database → Collection → Document (spreadsheet)
- **Admin terpisah jelas**: section “Access Control” di sidebar bawah, hanya muncul untuk admin+
- **Auth hybrid jelas**: Web = Session cookie (badge hijau “session”), API = tab Tokens → Generate API Key
- **Role widgets di Dashboard**: editor→My Drafts, admin→Audit Log, superadmin→System Health
- **Permissions UI**: full CRUD di `/permissions`, Roles UI load dynamic grouped
- **Empty states + skeletons** konsisten di semua tabel
- **Breadcrumbs + command palette ⌘K** untuk navigasi cepat

## Aksesibilitas
- Kontras minimal WCAG AA
- Focus ring indigo-500 visible
- Keyboard: Tab order logical, Esc close modal
- Ukuran tap target minimal 40px (mobile bottom nav)

---

Implementasi sudah di-apply ke:
- `frontend/src/Layouts/AppShell.vue` (NEW – gantikan AppLayout)
- `frontend/src/Components/UI/*` (Button, Card, Input, Badge, PageHeader, EmptyState, DataTable)
- `frontend/src/Pages/Dashboard/Index.vue` – role widgets
- `frontend/src/Pages/Users/Index.vue` – role select SINGLE
- `frontend/src/Pages/Roles/Index.vue` – permissions dynamic grouped
- `frontend/src/Pages/Permissions/Index.vue` – NEW
