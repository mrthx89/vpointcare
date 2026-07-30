### Task 9: Breadcrumb Ticketing

**Tujuan:** Memulihkan breadcrumb pada empat halaman ticketing tanpa mengubah route, sidebar, atau permission.

**Files:**
- Modify: `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php`
- Modify: `src/app/Filament/Concerns/HasMenuBreadcrumbs.php`
- Modify: `src/app/Support/FilamentBreadcrumbs.php`
- Create: `src/tests/Feature/Filament/TicketingBreadcrumbTest.php`

**Interfaces:**
- Keempat page memakai `HasMenuBreadcrumbs`.
- Operational ticket memakai menu code `AccessPermissions::TICKET_VIEW`.
- Master status/prioritas/kategori memakai parent menu `ticket.view` dan resource navigation label sebagai current crumb.
- Label berasal dari `NavigationHelper` dan locale aktif.

- [ ] **Step 1: Tulis test merah empat route**

Dengan user berizin, GET `/admin/operational/tickets`, `/admin/ticketing/status-tickets`, `/admin/ticketing/prioritas/prioritas-tickets`, dan `/admin/ticketing/kategoris/kategori-tickets`. Assert status OK, group/menu/current label tampil, dan label Ticket tidak terduplikasi. Tambahkan case locale `id`/`en` serta user tanpa permission mengikuti behavior deny existing.

- [ ] **Step 2: Pasang trait pada page**

Tambahkan `use HasMenuBreadcrumbs;` dan property:

```php
protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_VIEW;
```

ke empat page. Jangan ubah `$resource`, route, action Create, resource visibility, `ticket.view`, atau `ticket.manage`.

- [ ] **Step 3: Cegah duplicate current label**

Jika helper menghasilkan `Ticket > Ticket`, perluas minimal `HasMenuBreadcrumbs`/`FilamentBreadcrumbs` agar parent berasal dari menu code dan current label berasal dari `static::getResource()::getNavigationLabel()`. Gunakan `array_values(array_unique(...))` hanya setelah membuang label kosong; jangan mengubah breadcrumb page lain.

- [ ] **Step 4: Jalankan breadcrumb test**

```powershell
cd src
php -l app/Support/FilamentBreadcrumbs.php
php -l app/Filament/Concerns/HasMenuBreadcrumbs.php
php -l app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php
php -l app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php
php -l app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php
php -l app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php
php artisan test --filter=TicketingBreadcrumbTest
```

Expected: semua route menampilkan breadcrumb terlokalisasi dan access behavior tidak berubah.

---

