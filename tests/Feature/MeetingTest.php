<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Meeting;
use App\Models\MeetingType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'manage_meetings']);
        $role->givePermissionTo($permission);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    public function test_can_fetch_meetings_with_pagination()
    {
        MeetingType::create(['name' => 'Rapat Mingguan']);
        Meeting::factory()->count(25)->create([
            'meeting_type_id' => 1,
            'created_by' => $this->user->id,
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/meetings');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['data', 'current_page', 'last_page', 'total']]);
                 
        $this->assertCount(20, $response->json('data.data'));
        $this->assertEquals(25, $response->json('data.total'));
    }
}
