<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Club\ClubProfile;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Test suite for Club Update API - QR Code & Zalo Link features
 *
 * EXAMPLE FILE - Copy and modify as needed for your test suite
 */
class ClubUpdateQrZaloTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create admin user
        $this->admin = User::factory()->create();

        // Create club
        $this->club = Club::factory()->create([
            'name' => 'Test Club',
            'created_by' => $this->admin->id,
        ]);

        // Create club profile
        ClubProfile::create([
            'club_id' => $this->club->id,
        ]);

        // Add admin as club member
        ClubMember::create([
            'club_id' => $this->club->id,
            'user_id' => $this->admin->id,
            'role' => ClubMemberRole::Admin,
            'membership_status' => ClubMembershipStatus::Joined,
            'status' => ClubMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_update_zalo_link()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/clubs/{$this->club->id}", [
                'zalo_link' => 'zalo.me/g/pickleballsaigonpho',
                'zalo_enabled' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Cập nhật câu lạc bộ thành công',
            ]);

        $this->club->refresh();
        $profile = $this->club->profile;

        $this->assertEquals('zalo.me/g/pickleballsaigonpho', $profile->social_links['zalo']);
        $this->assertTrue($profile->settings['zalo_enabled']);
    }

    /** @test */
    public function admin_can_upload_qr_code_image()
    {
        $qrImage = UploadedFile::fake()->image('qr-code.png', 512, 512)->size(1024); // 1MB

        $response = $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'qr_code_image_url' => $qrImage,
                'qr_code_enabled' => true,
            ]);

        $response->assertStatus(200);

        $this->club->refresh();
        $profile = $this->club->profile;

        $this->assertNotNull($profile->qr_code_image_url);
        $this->assertTrue($profile->settings['qr_code_enabled']);

        // Check file exists in storage
        $rawPath = $profile->getRawQrCodeImagePath();
        Storage::disk('public')->assertExists($rawPath);
    }

    /** @test */
    public function admin_can_update_both_zalo_and_qr_code()
    {
        $qrImage = UploadedFile::fake()->image('qr-code.png', 512, 512)->size(2048); // 2MB

        $response = $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'zalo_link' => 'zalo.me/g/testclub',
                'zalo_enabled' => true,
                'qr_code_image_url' => $qrImage,
                'qr_code_enabled' => true,
            ]);

        $response->assertStatus(200);

        $this->club->refresh();
        $profile = $this->club->profile;

        $this->assertEquals('zalo.me/g/testclub', $profile->social_links['zalo']);
        $this->assertTrue($profile->settings['zalo_enabled']);
        $this->assertNotNull($profile->qr_code_image_url);
        $this->assertTrue($profile->settings['qr_code_enabled']);
    }

    /** @test */
    public function admin_can_toggle_zalo_off()
    {
        // First, set zalo enabled
        $this->club->profile->update([
            'social_links' => ['zalo' => 'zalo.me/g/testclub'],
            'settings' => ['zalo_enabled' => true],
        ]);

        // Toggle off
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/clubs/{$this->club->id}", [
                'zalo_enabled' => false,
            ]);

        $response->assertStatus(200);

        $this->club->refresh();
        $this->assertFalse($this->club->profile->settings['zalo_enabled']);
        // Zalo link should still exist, just disabled
        $this->assertEquals('zalo.me/g/testclub', $this->club->profile->social_links['zalo']);
    }

    /** @test */
    public function admin_can_toggle_qr_code_off()
    {
        // First, set qr enabled
        $this->club->profile->update([
            'qr_code_image_url' => 'qr_codes/test.png',
            'settings' => ['qr_code_enabled' => true],
        ]);

        // Toggle off
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/clubs/{$this->club->id}", [
                'qr_code_enabled' => false,
            ]);

        $response->assertStatus(200);

        $this->club->refresh();
        $this->assertFalse($this->club->profile->settings['qr_code_enabled']);
        // QR image should still exist, just disabled
        $this->assertNotNull($this->club->profile->getRawQrCodeImagePath());
    }

    /** @test */
    public function qr_code_image_must_be_valid_image_type()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'qr_code_image_url' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qr_code_image_url']);
    }

    /** @test */
    public function qr_code_image_cannot_exceed_5mb()
    {
        $largeImage = UploadedFile::fake()->image('large-qr.png')->size(6000); // 6MB

        $response = $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'qr_code_image_url' => $largeImage,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qr_code_image_url']);
    }

    /** @test */
    public function old_qr_code_image_is_deleted_when_uploading_new_one()
    {
        // Upload first QR
        $firstQr = UploadedFile::fake()->image('qr1.png')->size(1024);
        $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'qr_code_image_url' => $firstQr,
            ]);

        $this->club->refresh();
        $firstQrPath = $this->club->profile->getRawQrCodeImagePath();

        // Upload second QR
        $secondQr = UploadedFile::fake()->image('qr2.png')->size(1024);
        $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'qr_code_image_url' => $secondQr,
            ]);

        $this->club->refresh();
        $secondQrPath = $this->club->profile->getRawQrCodeImagePath();

        // Old QR should be deleted
        Storage::disk('public')->assertMissing($firstQrPath);
        // New QR should exist
        Storage::disk('public')->assertExists($secondQrPath);
        // Paths should be different
        $this->assertNotEquals($firstQrPath, $secondQrPath);
    }

    /** @test */
    public function non_admin_cannot_update_club_qr_or_zalo()
    {
        $nonAdmin = User::factory()->create();

        $response = $this->actingAs($nonAdmin, 'api')
            ->putJson("/api/clubs/{$this->club->id}", [
                'zalo_link' => 'zalo.me/g/hacker',
                'zalo_enabled' => true,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Chỉ admin/manager mới có quyền cập nhật CLB',
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_club()
    {
        $response = $this->putJson("/api/clubs/{$this->club->id}", [
            'zalo_link' => 'zalo.me/g/test',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function response_includes_zalo_and_qr_fields()
    {
        // Setup data
        $qrImage = UploadedFile::fake()->image('qr.png')->size(1024);
        $this->actingAs($this->admin, 'api')
            ->put("/api/clubs/{$this->club->id}", [
                'zalo_link' => 'zalo.me/g/testclub',
                'zalo_enabled' => true,
                'qr_code_image_url' => $qrImage,
                'qr_code_enabled' => true,
            ]);

        // Get club details
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/clubs/{$this->club->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'profile' => [
                        'qr_code_image_url',
                        'social_links',
                        'settings',
                    ],
                ],
            ]);

        $responseData = $response->json('data.profile');
        $this->assertEquals('zalo.me/g/testclub', $responseData['social_links']['zalo']);
        $this->assertTrue($responseData['settings']['zalo_enabled']);
        $this->assertTrue($responseData['settings']['qr_code_enabled']);
        $this->assertNotNull($responseData['qr_code_image_url']);
    }

    /** @test */
    public function zalo_link_can_be_removed()
    {
        // First, set zalo link
        $this->club->profile->update([
            'social_links' => ['zalo' => 'zalo.me/g/testclub'],
        ]);

        // Remove zalo link by sending empty string or null
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/clubs/{$this->club->id}", [
                'zalo_link' => null,
            ]);

        $response->assertStatus(200);

        $this->club->refresh();
        $this->assertNull($this->club->profile->social_links['zalo'] ?? null);
    }
}
