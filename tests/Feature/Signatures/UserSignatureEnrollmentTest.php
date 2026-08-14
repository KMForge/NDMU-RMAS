<?php

namespace Tests\Feature\Signatures;

use App\Enums\AccountStatus;
use App\Enums\UserType;
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
        $user = $this->activeUser(UserType::Student, 'student');

        $this->actingAs($user)
            ->putJson(route('signature.store'), [
                'signature' => $this->signatureImage(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Digital signature registered successfully.')
            ->assertJsonPath('signature.preview_url', route('signature.preview'))
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
            ->get(route('signature.preview'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        auth()->logout();
        $this->get(route('signature.preview'))->assertRedirect(route('login'));
    }

    public function test_registering_again_replaces_the_file_and_preserves_one_active_record(): void
    {
        $user = $this->activeUser(UserType::Faculty, 'thesis-adviser');

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
        $user = $this->activeUser(UserType::Faculty, 'panel-member');

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
        $user = $this->activeUser(UserType::Faculty, 'administrator');

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
        $administrator = User::factory()->create([
            'user_type' => UserType::Admin,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $administrator->assignRole('administrator');

        $this->actingAs($administrator)
            ->putJson(route('signature.store'), [
                'signature' => $this->signatureImage(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('user_signatures', 0);
    }

    private function activeUser(UserType $userType, string $role): User
    {
        $user = User::factory()->create([
            'user_type' => $userType,
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
