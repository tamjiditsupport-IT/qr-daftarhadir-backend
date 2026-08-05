<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Asatidz;
use App\Models\Unit;
use App\Models\Position;
use App\Models\MeetingType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $superAdmin = Role::create(['name' => 'super_admin']);
        $adminYayasan = Role::create(['name' => 'admin_yayasan']);
        $adminInstansi = Role::create(['name' => 'admin_instansi']);

        // Users
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@simas.com',
            'password' => Hash::make('password')
        ]);
        $admin->assignRole($superAdmin);

        // Unit
        $yayasan = Unit::create(['name' => 'Yayasan Pondok']);
        $pendidikan = Unit::create(['name' => 'Pendidikan', 'parent_id' => $yayasan->id]);
        $wustho = Unit::create(['name' => 'Wustho', 'parent_id' => $pendidikan->id]);

        // Position
        $kepala = Position::create(['name' => 'Kepala']);
        $guru = Position::create(['name' => 'Guru / Pengajar']);

        // Asatidz
        $asatidz1 = Asatidz::create(['id_asatidz' => 'AST0001', 'name' => 'Ust. Ahmad', 'phone' => '08123456789']);
        $asatidz2 = Asatidz::create(['id_asatidz' => 'AST0002', 'name' => 'Ust. Hasan', 'phone' => '08123456780']);
        
        $asatidz1->units()->attach($wustho->id);
        $asatidz1->positions()->attach($guru->id);
        $asatidz1->qrCard()->create(['qr_code' => 'AST0001']);
        
        $asatidz2->units()->attach($pendidikan->id);
        $asatidz2->positions()->attach($kepala->id);
        $asatidz2->qrCard()->create(['qr_code' => 'AST0002']);

        // Meeting Type
        MeetingType::create(['name' => 'Rapat Bulanan']);
        MeetingType::create(['name' => 'Rapat Insidental']);
    }
}
