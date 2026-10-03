<?php

namespace Database\Seeders\Local;

use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Seeders\RecruiterOperations\RecruiterRolesSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Two development-only logins for checking Recruiter Training in a browser:
 * a Recruiter Lead (manages training) and a Recruiter (learner). It refuses
 * to run outside the local and testing environments.
 *
 *   php artisan db:seed --class="Database\Seeders\Local\LocalTrainingTestAccountsSeeder"
 *
 * Settings (config/recruiter-training.php, local_test_accounts):
 * LOCAL_TEST_LEAD_EMAIL and LOCAL_TEST_RECRUITER_EMAIL (defaults
 * training.lead@vsp.test and training.recruiter@vsp.test), and
 * LOCAL_TEST_PASSWORD, or a random password printed once when it is not set.
 * Re-running resets both passwords and keeps the same accounts.
 */
class LocalTrainingTestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LocalTrainingTestAccountsSeeder only runs in the local or testing environment.');
        }

        $this->call(RecruiterRolesSeeder::class);

        $settings = (array) config('recruiter-training.local_test_accounts');
        $password = (string) ($settings['password'] ?? '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16, symbols: false);
        }

        $department = Department::query()->where('code', 'OPT-RECRUITING')->first() ?? Department::query()->firstOrFail();
        $accounts = [
            [(string) ($settings['lead_email'] ?? 'training.lead@vsp.test'), 'Training Lead (local test)', SystemRole::RecruiterLead, 'OPT-HEAD', 'LOCAL-LEAD'],
            [(string) ($settings['recruiter_email'] ?? 'training.recruiter@vsp.test'), 'Training Recruiter (local test)', SystemRole::Recruiter, 'OPT-RECRUITER', 'LOCAL-RECRUITER'],
        ];

        foreach ($accounts as [$email, $name, $role, $designationCode, $employeeCode]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($password),
                'user_type' => UserType::Internal,
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $user->syncRoles($role->value);

            $designation = Designation::query()->where('code', $designationCode)->first() ?? Designation::query()->firstOrFail();

            Employee::query()->firstOrCreate(['user_id' => $user->id], [
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employee_code' => $employeeCode,
                'status' => EmployeeStatus::Active,
                'joined_on' => now()->subMonth(),
            ]);

            $this->command->info("{$role->label()}: {$email}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->warn($generated
            ? "Password for both accounts (shown once): {$password}"
            : 'Password for both accounts: the value of LOCAL_TEST_PASSWORD.');
    }
}
