<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class Milestone2DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_and_role_helpers(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $operator = User::factory()->operator()->create();
        $inactive = User::factory()->inactive()->create();

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isCashier());
        $this->assertFalse($owner->isOperator());
        $this->assertTrue($owner->isActive());

        $this->assertTrue($cashier->isCashier());
        $this->assertFalse($cashier->isOwner());
        $this->assertTrue($cashier->isActive());

        $this->assertTrue($operator->isOperator());
        $this->assertFalse($operator->isCashier());
        $this->assertTrue($operator->isActive());

        $this->assertFalse($inactive->isActive());
    }

    public function test_user_soft_deletes(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
        $this->assertNull(User::find($userId));
    }

    public function test_business_setting_get_and_set_type_casting(): void
    {
        BusinessSetting::set('test.bool.true', true, 'boolean', 'test');
        BusinessSetting::set('test.bool.false', false, 'boolean', 'test');
        BusinessSetting::set('test.int', 42, 'integer', 'test');
        BusinessSetting::set('test.decimal', '125000.50', 'decimal', 'test');
        BusinessSetting::set('test.json', ['k1' => 'v1', 'num' => 99], 'json', 'test');
        BusinessSetting::set('test.string', 'hello world', 'string', 'test');

        $this->assertTrue(BusinessSetting::get('test.bool.true'));
        $this->assertFalse(BusinessSetting::get('test.bool.false'));
        $this->assertSame(42, BusinessSetting::get('test.int'));
        $this->assertSame('125000.50', BusinessSetting::get('test.decimal'));
        $this->assertSame(['k1' => 'v1', 'num' => 99], BusinessSetting::get('test.json'));
        $this->assertSame('hello world', BusinessSetting::get('test.string'));

        // Fallbacks
        $this->assertSame('fallback_val', BusinessSetting::get('missing.key', 'fallback_val'));
        $this->assertNull(BusinessSetting::get('missing.key.null'));
    }

    public function test_business_setting_boolean_string_false_serialization(): void
    {
        BusinessSetting::set('test.bool.false_str', 'false', 'boolean');

        $this->assertDatabaseHas('business_settings', [
            'key' => 'test.bool.false_str',
            'value' => '0',
            'type' => 'boolean',
        ]);
        $this->assertFalse(BusinessSetting::get('test.bool.false_str'));
    }

    public function test_audit_log_immutability(): void
    {
        $user = User::factory()->create();
        $setting = BusinessSetting::create([
            'key' => 'audit.test',
            'value' => 'orig',
            'type' => 'string',
            'group' => 'test',
        ]);

        $log = AuditLog::record('update', $setting, ['val' => 'orig'], ['val' => 'new'], $user->id);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'update',
            'auditable_type' => BusinessSetting::class,
            'auditable_id' => $setting->id,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be updated.');
        $log->update(['action' => 'delete']);
    }

    public function test_audit_log_deletion_prohibited(): void
    {
        $user = User::factory()->create();
        $setting = BusinessSetting::create([
            'key' => 'audit.test.delete',
            'value' => 'v',
            'type' => 'string',
            'group' => 'test',
        ]);

        $log = AuditLog::record('create', $setting, null, ['val' => 'v'], $user->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be deleted.');
        $log->delete();
    }

    public function test_seeders_seed_exact_roles_permissions_users_and_settings(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(BusinessSettingSeeder::class);

        // Assert Roles
        $this->assertSame(3, Role::count());
        $this->assertNotNull(Role::where('name', 'owner')->first());
        $this->assertNotNull(Role::where('name', 'cashier')->first());
        $this->assertNotNull(Role::where('name', 'operator')->first());

        // Assert Permissions
        $this->assertSame(23, Permission::count());

        // Assert Permission Mappings
        $ownerRole = Role::where('name', 'owner')->first();
        $cashierRole = Role::where('name', 'cashier')->first();
        $operatorRole = Role::where('name', 'operator')->first();

        $this->assertCount(23, $ownerRole->permissions);
        $this->assertCount(12, $cashierRole->permissions);
        $this->assertCount(4, $operatorRole->permissions);

        // Assert Users
        $this->assertGreaterThanOrEqual(1, User::count());
        $ownerUser = User::where('email', 'owner@andiklaundry.test')->first();

        $this->assertNotNull($ownerUser);
        $this->assertSame('owner', $ownerUser->role);
        $this->assertTrue($ownerUser->hasRole('owner'));

        // Assert Settings
        $this->assertSame(15, BusinessSetting::count());
        $this->assertSame('Andik Laundry', BusinessSetting::get('store.name'));
        $this->assertSame('2.00', BusinessSetting::get('operational.default_min_weight'));
        $this->assertSame(7, BusinessSetting::get('operational.default_debt_due_days'));
        $this->assertSame(2, BusinessSetting::get('operational.max_rewash_limit'));
        $this->assertFalse(BusinessSetting::get('printer.auto_print'));
        $this->assertSame('wuzapi', BusinessSetting::get('whatsapp.provider'));
    }
}
