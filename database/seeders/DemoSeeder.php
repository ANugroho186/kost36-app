<?php

namespace Database\Seeders;

use App\Enums\PaymentProofStatus;
use App\Enums\RentalInquiryStatus;
use App\Enums\TenancyDecisionType;
use App\Models\Announcement;
use App\Models\Complaint;
use App\Models\InventoryItem;
use App\Models\MaintenanceLog;
use App\Models\Parcel;
use App\Models\ParcelReport;
use App\Models\PaymentProof;
use App\Models\Property;
use App\Models\ReminderLog;
use App\Models\RentalInquiry;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyDecision;
use App\Models\TenancyOccupant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Data dummy untuk mencoba aplikasi. Semua nama, akun, dan kontak di sini fiktif.
 * Password semua akun: "password". Tanggal dihitung relatif dari hari ini.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private CarbonImmutable $today;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->today = CarbonImmutable::today();

        $owner = User::factory()->owner()->create([
            'name' => 'Pemilik Kost',
            'username' => 'pemilik',
            'email' => 'pemilik@example.com',
        ]);

        $property = Property::factory()->for($owner, 'owner')->create([
            'name' => 'Kost Contoh',
            'address' => 'Jl. Contoh No. 36, Kota Contoh',
            'description' => 'Kost dummy untuk mencoba aplikasi.',
            'rules' => 'Tamu menginap wajib lapor. Jam tenang pukul 22.00-06.00.',
        ]);

        $rooms = collect(['1', '2', '3', '4'])->map(fn (string $number): Room => Room::factory()
            ->for($property)
            ->published()
            ->create([
                'number' => $number,
                'facilities' => 'Kasur, lemari, meja, kipas angin',
            ]));

        $this->seedSharedRoom($rooms[0]);
        $this->seedRoomInGracePeriod($rooms[1]);
        $this->seedInactiveRoom($rooms[2]);
        $this->seedEmptyRoomWithHistory($rooms[3]);
        $this->seedPropertyRecords($property);
    }

    /**
     * Kamar 1: dihuni berdua, sewa lancar.
     */
    private function seedSharedRoom(Room $room): void
    {
        $start = $this->today->subMonthsNoOverflow(5);
        $tenancy = Tenancy::factory()->for($room)->create([
            'start_date' => $start,
            'paid_until' => $start->addMonthsNoOverflow(6),
        ]);

        $budi = $this->tenant('penghuni1', 'Budi Contoh', 'penghuni1@example.com');
        $andi = $this->tenant('penghuni2', 'Andi Contoh');

        TenancyOccupant::factory()->for($tenancy)->for($budi)->create([
            'nickname' => 'Budi',
            'moved_in_at' => $start,
        ]);
        TenancyOccupant::factory()->for($tenancy)->for($andi)->create([
            'nickname' => 'Andi',
            'moved_in_at' => $start->addMonthsNoOverflow(2),
        ]);

        // Bukti lama: sudah disetujui, dan filenya sudah terhapus otomatis.
        PaymentProof::factory()->for($tenancy)->for($budi, 'uploader')->create([
            'status' => PaymentProofStatus::Approved,
            'file_path' => null,
            'months_granted' => 1,
            'paid_until_before' => $tenancy->paid_until->subMonthsNoOverflow(4),
            'paid_until_after' => $tenancy->paid_until->subMonthsNoOverflow(3),
            'reviewed_at' => $this->today->subMonthsNoOverflow(4),
            'file_deleted_at' => $this->today->subDays(7),
        ]);

        // Bukti baru dari teman sekamar, menunggu verifikasi. Filenya belum ada sampai fitur unggah dibuat.
        PaymentProof::factory()->for($tenancy)->for($andi, 'uploader')->create([
            'file_path' => 'payment-proofs/demo-kamar-1.jpg',
        ]);

        Parcel::factory()->for($budi)->create(['item_name' => 'Buku kuliah']);
        Parcel::factory()->for($andi)->atOwner()->create(['item_name' => 'Charger HP']);
    }

    /**
     * Kamar 2: dihuni sendiri, masa tenggang H+3.
     */
    private function seedRoomInGracePeriod(Room $room): void
    {
        $paidUntil = $this->today->subDays(3);
        $start = $paidUntil->subMonthsNoOverflow(2);
        $tenancy = Tenancy::factory()->for($room)->create([
            'start_date' => $start,
            'paid_until' => $paidUntil,
        ]);

        $citra = $this->tenant('penghuni3', 'Citra Contoh', 'penghuni3@example.com');
        TenancyOccupant::factory()->for($tenancy)->for($citra)->create([
            'nickname' => 'Citra',
            'moved_in_at' => $start,
        ]);

        PaymentProof::factory()->for($tenancy)->for($citra, 'uploader')->rejected()->create([
            'file_path' => 'payment-proofs/demo-kamar-2.jpg',
        ]);

        foreach (['H-3' => -3, 'H-0' => 0, 'H+1' => 1] as $type => $offset) {
            ReminderLog::factory()->for($tenancy)->create([
                'due_date' => $paidUntil,
                'type' => $type,
                'sent_at' => $paidUntil->addDays($offset)->setTime(8, 0),
            ]);
        }

        $complaint = Complaint::factory()->for($room)->for($citra, 'reporter')->resolved()->create([
            'title' => 'Keran kamar mandi bocor',
            'description' => 'Air menetes terus dari keran.',
            'resolved_at' => $this->today->subDays(10),
        ]);
        MaintenanceLog::factory()->forRoom($room)->for($complaint)->create([
            'performed_at' => $this->today->subDays(10),
            'description' => 'Ganti keran kamar mandi.',
        ]);

        Parcel::factory()->for($citra)->receivedDirectly()->create(['item_name' => 'Kaos']);

        // Citra menerima paket orang lain. Laporannya belum dicocokkan pemilik.
        ParcelReport::factory()->for($citra, 'reporter')->create([
            'description' => 'Saya menerima paket resi XY1234567890 yang bukan milik saya.',
        ]);
    }

    /**
     * Kamar 3: sewa dinonaktifkan pemilik, pengosongan 5 hari lagi.
     */
    private function seedInactiveRoom(Room $room): void
    {
        $paidUntil = $this->today->subDays(15);
        $start = $paidUntil->subMonthsNoOverflow(3);
        $deactivatedAt = $paidUntil->addDays(11);

        $tenancy = Tenancy::factory()->for($room)->inactive()->create([
            'start_date' => $start,
            'paid_until' => $paidUntil,
            'deactivated_at' => $deactivatedAt,
            'vacate_by' => $this->today->addDays(5),
        ]);

        $dodi = $this->tenant('penghuni4', 'Dodi Contoh');
        TenancyOccupant::factory()->for($tenancy)->for($dodi)->create([
            'nickname' => 'Dodi',
            'moved_in_at' => $start,
        ]);
        // Penghuni tanpa akun: tetap tercatat, pemilik yang mengurus atas namanya.
        TenancyOccupant::factory()->for($tenancy)->withoutAccount()->create([
            'nickname' => 'Eko',
            'moved_in_at' => $start,
        ]);

        TenancyDecision::factory()->for($tenancy)->create([
            'type' => TenancyDecisionType::Deactivate,
            'days' => null,
            'new_date' => $this->today->addDays(5),
            'note' => 'Belum ada pembayaran sampai H+10.',
            'created_at' => $deactivatedAt,
        ]);
    }

    /**
     * Kamar 4: kosong, dengan riwayat sewa lama yang sudah dianonimkan.
     */
    private function seedEmptyRoomWithHistory(Room $room): void
    {
        $start = $this->today->subMonthsNoOverflow(14);
        $endDate = $this->today->subMonthsNoOverflow(2);

        $tenancy = Tenancy::factory()->for($room)->ended()->create([
            'start_date' => $start,
            'paid_until' => $endDate,
            'end_date' => $endDate,
        ]);

        // Akun sudah dihapus, nama dan kontak sudah dikosongkan.
        TenancyOccupant::factory()->for($tenancy)->withoutAccount()->create([
            'nickname' => null,
            'moved_in_at' => $start,
            'moved_out_at' => $endDate,
        ]);
    }

    private function seedPropertyRecords(Property $property): void
    {
        Announcement::factory()->for($property)->create([
            'title' => 'Kerja bakti hari Minggu',
            'body' => 'Kerja bakti membersihkan dapur dan area jemuran pukul 08.00.',
            'published_at' => $this->today->subDays(2),
            'expires_at' => $this->today->addDays(5),
        ]);
        Announcement::factory()->for($property)->expired()->create([
            'title' => 'Pemadaman listrik',
            'body' => 'Pemadaman listrik dari PLN pukul 09.00-12.00.',
        ]);

        InventoryItem::factory()->for($property)->create(['name' => 'Kompor gas']);
        InventoryItem::factory()->for($property)->needsRepair()->create([
            'name' => 'Mesin cuci',
            'notes' => 'Pengering kadang tidak berputar.',
        ]);
        foreach ($property->rooms as $room) {
            InventoryItem::factory()->forRoom($room)->create(['name' => 'Kasur']);
        }

        MaintenanceLog::factory()->for($property)->create([
            'performed_at' => $this->today->subDays(20),
            'description' => 'Kuras tandon air.',
        ]);

        RentalInquiry::factory()->for($property)->create();
        RentalInquiry::factory()->for($property)->create(['status' => RentalInquiryStatus::Contacted]);
        RentalInquiry::factory()->for($property)->done()->create();
    }

    private function tenant(string $username, string $name, ?string $email = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
        ]);
    }
}
