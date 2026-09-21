<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Real PLUS North-South Expressway GCP data (plan/GCP_GDM2000.xls),
        // not fake demo stations — see PlusGcpStationSeeder's own docblock
        // and the plan/STATUS.md deviation log. DemoStationSeeder/
        // DemoPhotoSeeder/DemoDocumentSeeder still exist and are still used
        // directly by the test suite (their own fixtures, isolated from
        // this database) — only this real-data entry point stopped calling
        // them.
        $this->call(PlusGcpStationSeeder::class);
    }
}
