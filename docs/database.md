# Desain Database

Database: PostgreSQL. Tabel bawaan Laravel (`sessions`, `password_reset_tokens`, `cache`, `jobs`, `failed_jobs`, `notifications`) tidak digambar di sini.

Tidak ada kolom uang di tabel mana pun. Sistem tidak memproses pembayaran dan tidak menyimpan nominal transfer.

## ERD

```mermaid
erDiagram
    USERS ||--o{ PROPERTIES : memiliki
    PROPERTIES ||--|{ ROOMS : punya
    ROOMS ||--o{ ROOM_PHOTOS : punya
    ROOMS ||--o{ TENANCIES : disewa
    USERS |o--o{ TENANCIES : login_sebagai
    TENANCIES ||--o{ PAYMENT_PROOFS : punya
    USERS |o--o{ PAYMENT_PROOFS : mengunggah
    TENANCIES ||--o{ TENANCY_DECISIONS : punya
    TENANCIES ||--o{ REMINDER_LOGS : mencatat
    ROOMS ||--o{ COMPLAINTS : punya
    USERS |o--o{ COMPLAINTS : melapor
    COMPLAINTS ||--o{ COMPLAINT_PHOTOS : punya
    PROPERTIES ||--o{ ANNOUNCEMENTS : punya
    PROPERTIES ||--o{ MAINTENANCE_LOGS : punya
    ROOMS |o--o{ MAINTENANCE_LOGS : dirawat
    COMPLAINTS |o--o{ MAINTENANCE_LOGS : ditindaklanjuti
    PROPERTIES ||--o{ INVENTORY_ITEMS : punya
    ROOMS |o--o{ INVENTORY_ITEMS : berisi
    PROPERTIES ||--o{ RENTAL_INQUIRIES : menerima

    USERS {
        bigint id PK
        string name
        string username UK
        string email UK "nullable"
        string password "hash"
        string role "owner atau tenant, default tenant"
    }
    PROPERTIES {
        bigint id PK
        bigint owner_id FK
        string name
        text address
        text description
        text rules
    }
    ROOMS {
        bigint id PK
        bigint property_id FK
        string number
        text facilities
        boolean is_published
    }
    ROOM_PHOTOS {
        bigint id PK
        bigint room_id FK
        string path "disk publik"
        int sort_order
    }
    TENANCIES {
        bigint id PK
        bigint room_id FK
        bigint user_id FK "nullable, nullOnDelete"
        string nickname "nullable"
        date start_date
        date paid_until
        date extended_until "nullable"
        string status "active, inactive, ended"
        timestamp deactivated_at "nullable"
        date vacate_by "nullable"
        date end_date "nullable"
        string notify_channel "nullable: telegram, email"
        string notify_contact "nullable"
    }
    PAYMENT_PROOFS {
        bigint id PK
        bigint tenancy_id FK
        bigint uploaded_by FK "nullable, nullOnDelete"
        string file_path "nullable, disk privat"
        string status "pending, approved, rejected"
        smallint months_granted "nullable"
        date paid_until_before "nullable"
        date paid_until_after "nullable"
        text review_note "nullable"
        timestamp reviewed_at "nullable"
        timestamp file_deleted_at "nullable"
    }
    TENANCY_DECISIONS {
        bigint id PK
        bigint tenancy_id FK
        string type "extend, deactivate, reactivate, postpone_vacate, end"
        smallint days "nullable"
        date new_date "nullable"
        text note "nullable"
        timestamp created_at
    }
    REMINDER_LOGS {
        bigint id PK
        bigint tenancy_id FK
        date due_date
        string type "H-3, H-0, H+1, H+4, H+7, H+10"
        timestamp sent_at
    }
    COMPLAINTS {
        bigint id PK
        bigint room_id FK
        bigint reporter_id FK "nullable, nullOnDelete"
        string title
        text description
        string status "reported, in_progress, resolved"
        timestamp resolved_at "nullable"
    }
    COMPLAINT_PHOTOS {
        bigint id PK
        bigint complaint_id FK
        string path "disk privat"
    }
    ANNOUNCEMENTS {
        bigint id PK
        bigint property_id FK
        string title
        text body
        timestamp published_at
        timestamp expires_at "nullable"
    }
    MAINTENANCE_LOGS {
        bigint id PK
        bigint property_id FK
        bigint room_id FK "nullable"
        bigint complaint_id FK "nullable"
        date performed_at
        text description
    }
    INVENTORY_ITEMS {
        bigint id PK
        bigint property_id FK
        bigint room_id FK "nullable"
        string name
        string condition "good, needs_repair, broken"
        text notes "nullable"
    }
    RENTAL_INQUIRIES {
        bigint id PK
        bigint property_id FK
        string name
        string contact
        date preferred_date "nullable"
        text message "nullable"
        string status "new, contacted, done"
    }
```

Kolom `created_at`/`updated_at` (`timestamps()`) ada di semua tabel kecuali `tenancy_decisions` dan `reminder_logs`, yang hanya mencatat waktu kejadian. Kolom 2FA dan `remember_token` bawaan Fortify tetap ada di `users`.

## Data yang disimpan tentang penghuni

Hanya ini:

- `nickname` dan kamar, untuk identifikasi.
- `start_date` dan `paid_until`, untuk pengingat.
- Akun login (opsional): `username`, `password`, dan `email` (opsional).
- Satu kontak notifikasi (opsional): `notify_channel` + `notify_contact`. Untuk Telegram, isinya `chat_id` yang didapat otomatis saat penghuni membuka link bot dan menekan Start.

**Tidak disimpan:** NIK, foto KTP, alamat asal, tanggal lahir, tempat kerja/kampus, kontak darurat. Data ini dicek atau dicatat offline oleh pemilik.

## Aturan dan batasan

### Status kamar (dihitung, tidak disimpan)

- **Terisi:** kamar punya sewa berstatus `active` atau `inactive`. Penghuni nonaktif masih menempati kamar sampai `vacate_by`.
- **Tersedia:** tidak ada sewa seperti itu.
- `rooms.is_published = false` menyembunyikan kamar dari halaman publik.
- Halaman publik hanya menampilkan "tersedia/terisi", tanpa nama penghuni.

### Satu kamar, satu sewa yang menempati

Dijaga di level database dengan partial unique index (raw SQL di migration `tenancies`):

```sql
CREATE UNIQUE INDEX tenancies_room_occupying_unique
    ON tenancies (room_id)
    WHERE status IN ('active', 'inactive');
```

### Pembayaran dan masa tenggang

Semua tanggal dihitung dalam zona waktu **Asia/Jakarta**. Batas yang berlaku adalah `extended_until` kalau terisi, selain itu `paid_until`.

| Kondisi (dibanding `paid_until`) | Status turunan                        | Akses penghuni                   |
| -------------------------------- | ------------------------------------- | -------------------------------- |
| Hari ini ≤ `paid_until`          | Lancar                                | Normal                           |
| H+1 sampai H+7                   | Masa tenggang                         | Normal, menerima notifikasi      |
| H+8 sampai H+9                   | Lewat batas                           | Normal, tanpa notifikasi baru    |
| H+10 dan seterusnya              | Perlu keputusan pemilik               | Normal sampai pemilik memutuskan |
| `extended_until` terlewat        | Perlu keputusan (tanpa tenggang baru) | Normal sampai pemilik memutuskan |

- Bukti **disetujui:** `paid_until` maju `months_granted` bulan (default 1) dengan `addMonthsNoOverflow`. `extended_until` dikosongkan. Kalau sewa `inactive`, statusnya kembali `active` dan `deactivated_at`/`vacate_by` dikosongkan. Nilai sebelum/sesudah disimpan di `payment_proofs`.
- Bukti **ditolak:** `review_note` wajib diisi sebagai alasan.
- Kalau masih ada bukti `pending`, pemilik diminta memverifikasinya sebelum bisa menonaktifkan.
- Setiap keputusan pemilik dicatat di `tenancy_decisions`.

### Pengingat tanpa duplikat

`reminder_logs` punya unique key `(tenancy_id, due_date, type)`. Scheduler yang berjalan dua kali tidak mengirim pengingat ganda.

### Penghuni keluar (status `ended`)

Hanya terjadi setelah pemilik mengonfirmasi kamar sudah kosong:

1. Akun `users` penghuni dihapus. FK yang menunjuk ke akun itu otomatis menjadi `NULL` (`nullOnDelete`).
2. `nickname` dan `notify_contact` dikosongkan, `status = ended`, dan `end_date` diisi.
3. File bukti transfer dihapus (`file_path = NULL`, `file_deleted_at` diisi). Barisnya tetap ada.
4. Riwayat keluhan, perawatan, dan masa sewa tetap tersimpan tanpa identitas.

### File

- `room_photos`: disk `public`.
- `complaint_photos` dan `payment_proofs`: disk privat. Hanya bisa dibuka lewat route yang mengecek Policy (pemilik, atau penghuni pemilik data). Batasi tipe file (jpg/png, bukti juga pdf) dan ukurannya, lalu ganti nama file saat disimpan.
- File bukti transfer dihapus otomatis beberapa bulan setelah ditinjau. Catatannya tetap disimpan.

### Lain-lain

- `rental_inquiries` dihapus otomatis beberapa waktu setelah statusnya `done`.
- `maintenance_logs`/`inventory_items` dengan `room_id` kosong berarti area umum (dapur, kamar mandi bersama).
- Nilai tetap (role dan semua kolom status/type) memakai enum PHP berbasis string di `app/Enums`.
