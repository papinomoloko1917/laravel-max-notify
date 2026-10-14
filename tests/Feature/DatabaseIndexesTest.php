<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_data_has_indexes_for_current_query_and_foreign_key_paths(): void
    {
        $cameraEventIndexes = array_column(Schema::getIndexes('camera_events'), null, 'name');
        $cameraClientIndexes = array_column(Schema::getIndexes('camera_client'), null, 'name');

        $this->assertSame(
            ['camera_id'],
            $cameraEventIndexes['camera_events_camera_id_index']['columns'],
        );
        $this->assertSame(
            ['status', 'id'],
            $cameraEventIndexes['camera_events_status_id_index']['columns'],
        );
        $this->assertSame(
            ['client_id'],
            $cameraClientIndexes['camera_client_client_id_index']['columns'],
        );
    }
}
