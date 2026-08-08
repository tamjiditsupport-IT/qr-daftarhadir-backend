<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role1 = Role::create(['name' => 'admin']);
        $permission1 = Permission::create(['name' => 'manage_meetings']);
        $role1->givePermissionTo($permission1);
        
        $role2 = Role::create(['name' => 'super_admin']);
        $permission2 = Permission::create(['name' => 'manage_users']);
        $role2->givePermissionTo($permission2);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_admin_can_access_meetings()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/meetings');
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_users()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_users()
    {
        $response = $this->actingAs($this->superAdmin)->getJson('/api/users');
        $response->assertStatus(200);
    }
}
