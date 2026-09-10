<?php

namespace Tests\Feature\Settings;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_active_user_can_upload_and_privately_view_a_profile_photo(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->putJson(route('profile-photo.store'), [
                'profile_photo' => $this->profilePhoto(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile photo updated successfully.')
            ->assertJsonMissingPath('profile_photo_path');

        $user->refresh();

        $this->assertSame('local', $user->profile_photo_disk);
        $this->assertSame('image/png', $user->profile_photo_mime_type);
        $this->assertMatchesRegularExpression(
            '#^profile-photos/'.$user->getKey().'/[0-9a-f-]{36}\.png$#',
            $user->profile_photo_path,
        );
        Storage::disk('local')->assertExists($user->profile_photo_path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->getKey(),
            'event' => 'user_profile_photo.uploaded',
        ]);

        $response = $this->get(route('profile-photo.show'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        auth()->logout();
        $this->get(route('profile-photo.show'))->assertRedirect(route('login'));
    }

    public function test_uploading_again_replaces_the_old_private_file(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->putJson(route('profile-photo.store'), [
            'profile_photo' => $this->profilePhoto('first.png'),
        ])->assertOk();

        $oldPath = $user->refresh()->profile_photo_path;

        $this->actingAs($user)->putJson(route('profile-photo.store'), [
            'profile_photo' => $this->profilePhoto('replacement.png'),
        ])->assertOk();

        $user->refresh();

        $this->assertNotSame($oldPath, $user->profile_photo_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($user->profile_photo_path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->getKey(),
            'event' => 'user_profile_photo.replaced',
        ]);
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->putJson(route('profile-photo.store'), [
            'profile_photo' => $this->profilePhoto(),
        ])->assertOk();

        $path = $user->refresh()->profile_photo_path;

        $this->deleteJson(route('profile-photo.destroy'))
            ->assertOk()
            ->assertJsonPath('message', 'Profile photo removed successfully.');

        $user->refresh();

        $this->assertNull($user->profile_photo_path);
        $this->assertNull($user->profile_photo_disk);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->getKey(),
            'event' => 'user_profile_photo.removed',
        ]);
    }

    public function test_invalid_or_oversized_profile_photos_are_rejected(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->putJson(route('profile-photo.store'), [
                'profile_photo' => UploadedFile::fake()->createWithContent('avatar.png', 'not an image'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');

        $this->actingAs($user)
            ->putJson(route('profile-photo.store'), [
                'profile_photo' => UploadedFile::fake()->create('large.png', 2049, 'image/png'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    private function profilePhoto(string $filename = 'avatar.png'): UploadedFile
    {
        $width = 100;
        $height = 100;
        $pixels = '';

        for ($y = 0; $y < $height; $y++) {
            $pixels .= "\x00";

            for ($x = 0; $x < $width; $x++) {
                $pixels .= pack('CCC', $x % 256, $y % 256, ($x + $y) % 256);
            }
        }

        $png = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$this->pngChunk('IDAT', gzcompress($pixels, 9))
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()->createWithContent($filename, $png);
    }

    private function pngChunk(string $type, string $data): string
    {
        $chunk = $type.$data;

        return pack('N', strlen($data)).$chunk.pack('N', crc32($chunk));
    }
}
