<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * plan/phases/phase-08-admin-qr.md M8.7 Test Gate.
 */
class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_is_primary_on_a_document_clears_it_on_the_stations_others(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $existingPrimary = $station->addMediaFromString('%PDF-1.4')
            ->usingFileName('a.pdf')
            ->withCustomProperties(['document_type' => 'report', 'title' => 'A', 'is_primary' => true])
            ->toMediaCollection('documents');

        $response = $this->actingAs($user)->post(route('admin.stations.documents.store', $station), [
            'file' => UploadedFile::fake()->createWithContent('b.pdf', '%PDF-1.4'),
            'document_type' => 'as_built',
            'title' => 'B',
            'is_primary' => true,
        ]);

        $response->assertSessionDoesntHaveErrors();

        $existingPrimary->refresh();
        $this->assertFalse((bool) $existingPrimary->getCustomProperty('is_primary'));

        $newPrimary = $station->fresh()->getMedia('documents')->firstWhere('file_name', 'b.pdf');
        $this->assertTrue((bool) $newPrimary->getCustomProperty('is_primary'));
    }

    public function test_making_an_existing_document_primary_clears_the_others_too(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $first = $station->addMediaFromString('%PDF-1.4')
            ->usingFileName('a.pdf')
            ->withCustomProperties(['document_type' => 'report', 'title' => 'A', 'is_primary' => true])
            ->toMediaCollection('documents');

        $second = $station->addMediaFromString('%PDF-1.4')
            ->usingFileName('b.pdf')
            ->withCustomProperties(['document_type' => 'report', 'title' => 'B', 'is_primary' => false])
            ->toMediaCollection('documents');

        $this->actingAs($user)->patch(route('admin.stations.documents.update', ['station' => $station, 'media' => $second->uuid]), [
            'document_type' => 'report',
            'title' => 'B',
            'is_primary' => true,
        ]);

        $this->assertFalse((bool) $first->fresh()->getCustomProperty('is_primary'));
        $this->assertTrue((bool) $second->fresh()->getCustomProperty('is_primary'));
    }

    public function test_uploading_a_3_to_1_panorama_is_rejected(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.panorama.store', $station), [
            'file' => UploadedFile::fake()->image('pano.jpg', 3000, 1000),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertNull($station->fresh()->getFirstMedia('panoramas'));
    }

    public function test_uploading_a_2_to_1_panorama_is_accepted(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.panorama.store', $station), [
            'file' => UploadedFile::fake()->image('pano.jpg', 4000, 2000),
            'initial_yaw' => 10,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertNotNull($station->fresh()->getFirstMedia('panoramas'));
    }

    public function test_uploading_a_dwg_without_a_preview_is_rejected(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.documents.store', $station), [
            'file' => UploadedFile::fake()->createWithContent('as-built.dwg', "AC1032\x00\x00".random_bytes(64)),
            'document_type' => 'as_built',
            'title' => 'As-Built Drawing',
        ]);

        $response->assertSessionHasErrors('preview_file');
        $this->assertCount(0, $station->fresh()->getMedia('documents'));
    }

    public function test_uploading_a_dwg_with_a_preview_is_accepted(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.documents.store', $station), [
            'file' => UploadedFile::fake()->createWithContent('as-built.dwg', "AC1032\x00\x00".random_bytes(64)),
            'preview_file' => UploadedFile::fake()->image('preview.png', 800, 600),
            'document_type' => 'as_built',
            'title' => 'As-Built Drawing',
        ]);

        $response->assertSessionDoesntHaveErrors();

        $documents = $station->fresh()->getMedia('documents');
        $this->assertCount(2, $documents); // the DWG original + its preview
    }

    public function test_deleting_a_document_also_deletes_its_paired_preview(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $this->actingAs($user)->post(route('admin.stations.documents.store', $station), [
            'file' => UploadedFile::fake()->createWithContent('as-built.dwg', "AC1032\x00\x00".random_bytes(64)),
            'preview_file' => UploadedFile::fake()->image('preview.png', 800, 600),
            'document_type' => 'as_built',
            'title' => 'As-Built Drawing',
        ]);

        $station->refresh();
        $original = $station->getMedia('documents')->firstWhere('file_name', 'as-built.dwg');

        $this->actingAs($user)->delete(route('admin.stations.documents.destroy', ['station' => $station, 'media' => $original->uuid]));

        $this->assertCount(0, $station->fresh()->getMedia('documents'));
    }
}
