<?php

namespace Tests\Integration\Supabase;

use App\Clients\Supabase\SupabaseStorageClient;
use App\Integrations\Supabase\SupabaseStorageService;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class StorageSecurityTest extends TestCase
{
    public function test_path_traversal_is_rejected_before_an_http_request(): void
    {
        $client = Mockery::mock(SupabaseStorageClient::class);
        $client->shouldNotReceive('upload');
        $service = new SupabaseStorageService($client);

        $this->expectException(InvalidArgumentException::class);
        $service->put('research-manuscripts', '../secret.pdf', 'content', 'application/pdf');
    }

    public function test_object_names_are_random_and_do_not_contain_original_filename(): void
    {
        $service = new SupabaseStorageService(Mockery::mock(SupabaseStorageClient::class));
        $path = $service->randomObjectPath('research/1', 'PDF');

        $this->assertMatchesRegularExpression('#^research/1/[0-9a-f-]{36}\.pdf$#', $path);
    }
}
