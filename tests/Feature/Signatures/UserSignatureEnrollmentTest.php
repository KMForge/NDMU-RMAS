<?php

namespace Tests\Feature\Signatures;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Models\UserSignature;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSignatureEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_administrator_can_register_and_privately_view_a_signature(): void
    {
        $user = $this->activeUser('student-researcher');

        $this->actingAs($user)
            ->putJson(route('signature.store'), [
                'signature' => $this->signatureImage(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Digital signature registered successfully.')
            ->assertJsonPath('signature.preview_url', route('signature.show'))
            ->assertJsonMissingPath('signature.storage_path');

        $signature = UserSignature::query()->sole();

        $this->assertSame($user->getKey(), $signature->user_id);
        $this->assertSame('image/png', $signature->mime_type);
        $this->assertMatchesRegularExpression(
            '#^signatures/'.$user->getKey().'/[0-9a-f-]{36}\.png$#',
            $signature->storage_path,
        );
        Storage::disk('local')->assertExists($signature->storage_path);
        $this->assertDatabaseHas('signature_audits', [
            'user_signature_id' => $signature->getKey(),
            'user_id' => $user->getKey(),
            'action' => 'registered',
        ]);

        $this->actingAs($user)
            ->get(route('signature.show'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        auth()->logout();
        $this->get(route('signature.show'))->assertRedirect(route('login'));
    }

    public function test_registering_again_replaces_the_file_and_preserves_one_active_record(): void
    {
        $user = $this->activeUser('research-adviser');

        $this->actingAs($user)->putJson(route('signature.store'), [
            'signature' => $this->signatureImage('first.png'),
        ])->assertOk();

        $oldPath = UserSignature::query()->sole()->storage_path;

        $this->actingAs($user)->putJson(route('signature.store'), [
            'signature' => $this->signatureImage('replacement.png'),
        ])->assertOk();

        $signature = UserSignature::query()->sole();

        $this->assertNotSame($oldPath, $signature->storage_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($signature->storage_path);
        $this->assertDatabaseCount('user_signatures', 1);
        $this->assertDatabaseHas('signature_audits', [
            'user_id' => $user->getKey(),
            'action' => 'replaced',
        ]);
    }

    public function test_owner_can_remove_a_registered_signature(): void
    {
        $user = $this->activeUser('panelist');

        $this->actingAs($user)->putJson(route('signature.store'), [
            'signature' => $this->signatureImage(),
        ])->assertOk();

        $path = UserSignature::query()->sole()->storage_path;

        $this->actingAs($user)
            ->deleteJson(route('signature.destroy'))
            ->assertOk()
            ->assertJsonPath('message', 'Digital signature removed successfully.');

        $this->assertDatabaseCount('user_signatures', 0);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('signature_audits', [
            'user_id' => $user->getKey(),
            'action' => 'removed',
        ]);
    }

    public function test_corrupted_disguised_and_unsupported_signature_files_are_rejected(): void
    {
        $user = $this->activeUser('research-facilitator');

        foreach ([
            UploadedFile::fake()->createWithContent('corrupt.png', 'not a real image'),
            UploadedFile::fake()->createWithContent('payload.php.png', '<?php echo "unsafe";'),
            UploadedFile::fake()->createWithContent('signature.svg', '<svg></svg>'),
        ] as $file) {
            $this->actingAs($user)
                ->putJson(route('signature.store'), ['signature' => $file])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('signature');
        }

        $this->assertDatabaseCount('user_signatures', 0);
    }

    public function test_system_administrator_cannot_register_a_signature(): void
    {
        $administrator = $this->activeUser('system-administrator');

        $this->actingAs($administrator)
            ->putJson(route('signature.store'), [
                'signature' => $this->signatureImage(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('user_signatures', 0);
    }

    private function activeUser(string $role): User
    {
        $user = User::factory()->create([
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function signatureImage(string $filename = 'signature.png'): UploadedFile
    {
        $width = 600;
        $height = 200;
        $pixels = '';

        for ($y = 0; $y < $height; $y++) {
            $pixels .= "\x00";

            for ($x = 0; $x < $width; $x++) {
                $pixels .= pack('CCCC', $x % 256, $y % 256, ($x + $y) % 256, 255);
            }
        }

        $png = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
            .$this->pngChunk('IDAT', gzcompress($pixels, 9))
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()->createWithContent($filename, $png);
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }
}
