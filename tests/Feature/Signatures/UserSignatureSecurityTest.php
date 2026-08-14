<?php

namespace Tests\Feature\Signatures;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Models\UserSignature;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSignatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_faculty_with_powerful_custom_permission_is_not_reclassified_as_admin_for_enrollment(): void
    {
        $faculty = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $faculty->givePermissionTo('users.manage');

        $this->actingAs($faculty)
            ->putJson(route('signature.store'), [
                'signature' => $this->validPngImage(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Digital signature registered successfully.');

        $this->assertDatabaseHas('user_signatures', [
            'user_id' => $faculty->id,
        ]);
    }

    public function test_admin_base_account_cannot_enroll_academic_signature(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::Admin,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->putJson(route('signature.store'), [
                'signature' => $this->validPngImage(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('user_signatures', 0);
    }

    public function test_jpeg_is_normalized_to_real_png_if_jpeg_uploads_are_supported(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not available in CLI environment.');
        }

        $faculty = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Create genuine GD JPEG image
        $im = imagecreatetruecolor(400, 150);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagejpeg($im);
        $jpegBytes = ob_get_clean();
        imagedestroy($im);

        $jpegFile = UploadedFile::fake()->createWithContent('signature.jpg', (string) $jpegBytes);

        $this->actingAs($faculty)
            ->putJson(route('signature.store'), [
                'signature' => $jpegFile,
            ])
            ->assertOk();

        $sig = UserSignature::query()->where('user_id', $faculty->id)->first();
        $this->assertNotNull($sig);
        $this->assertSame('image/png', $sig->mime_type);
        $this->assertStringEndsWith('.png', $sig->storage_path);

        $storedBytes = Storage::disk('local')->get($sig->storage_path);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $storedBytes);
    }

    public function test_settings_show_and_signature_preview_are_distinct_routes(): void
    {
        $student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)->putJson(route('signature.store'), [
            'signature' => $this->validPngImage(),
        ])->assertOk();

        // show returns HTML page
        $this->actingAs($student)->get(route('signature.show'))
            ->assertOk()
            ->assertSee('Digital Signature Enrollment');

        // preview returns binary image stream
        $this->actingAs($student)->get(route('signature.preview'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_another_user_cannot_preview_owner_specimen(): void
    {
        $owner = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $other = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)->putJson(route('signature.store'), [
            'signature' => $this->validPngImage(),
        ])->assertOk();

        // Other user calling preview gets 404 because preview streams THEIR signature
        $this->actingAs($other)->get(route('signature.preview'))
            ->assertNotFound();
    }

    public function test_db_failure_during_enrollment_deletes_orphan_final_file(): void
    {
        $user = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Break DB table signature_audits to force transaction failure after file move
        Schema::dropIfExists('signature_audits');

        try {
            $this->actingAs($user)->putJson(route('signature.store'), [
                'signature' => $this->validPngImage(),
            ]);
        } catch (\Throwable $e) {
            // Expected database exception
        }

        // Verify no orphan files remain in signatures directory
        $files = Storage::disk('local')->allFiles("signatures/{$user->id}");
        $this->assertEmpty($files, 'Orphan signature file remained after DB transaction failure.');
    }

    private function validPngImage(): UploadedFile
    {
        if (function_exists('imagecreatetruecolor')) {
            $width = 400;
            $height = 150;
            $im = imagecreatetruecolor($width, $height);
            $bg = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $bg);
            ob_start();
            imagepng($im);
            $pngBytes = ob_get_clean();
            imagedestroy($im);

            return UploadedFile::fake()->createWithContent('signature.png', (string) $pngBytes);
        }

        // Minimal PNG chunk fallback
        $pngHeader = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\rIDATx\x9cc\xf8\xff\xff?\x03\x00\x05\xfe\x02\xfe\xa79\xfd\x05\x00\x00\x00\x00IEND\xaeB`\x82";

        return UploadedFile::fake()->createWithContent('signature.png', $pngHeader);
    }
}
