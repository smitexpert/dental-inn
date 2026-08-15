# Bank & Cash — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the legacy currency / exchange / cash-drawer system with a unified **Bank & Cash** ledger: a single `bank_accounts` table, a `transfers` table, two `cash_movements` rows per transfer, and per-account reconciliation. No FX, no exchange rates, single system currency from settings.

**Architecture:** Hard cut — drop `currencies`, `exchange_rates`, `cash_drawers` and rebuild `cash_movements` against `bank_accounts`. Money movement is always a `Transfer` (two `cash_movements` rows sharing a UUID `transfer_group_id`). Reconciliation writes either nothing (match) or a transfer-pair against a seeded `External Adjustment` account (variance). A seeder installs the two system accounts. PaymentService and DashboardController are rewired onto the new model.

**Tech Stack:** Laravel 13 + Inertia 3 + Vue 3 (plain JS) + daisyUI 5 + Tailwind v4 + Spatie Activitylog + Spatie Permission. PHPUnit (backend), Vitest (frontend).

**Spec:** `docs/superpowers/specs/2026-08-15-bank-and-cash-design.md`

---

## File structure

**New**
- `app/Enums/BankAccountType.php`
- `app/Models/BankAccount.php`
- `app/Models/Transfer.php`
- `app/Repositories/Contracts/BankAccountRepository.php`
- `app/Repositories/Contracts/TransferRepository.php`
- `app/Repositories/Eloquent/BankAccountEloquentRepository.php`
- `app/Repositories/Eloquent/TransferEloquentRepository.php`
- `app/Services/BankAccountService.php`
- `app/Services/TransferService.php`
- `app/Services/ReconciliationService.php`
- `app/Http/Controllers/Bank/{DashboardController,AccountController,TransferController,ReconciliationController}.php`
- `app/Http/Requests/Bank/{StoreBankAccountRequest,UpdateBankAccountRequest,StoreTransferRequest,ReconcileAccountRequest}.php`
- `database/migrations/2026_08_15_000001_rebuild_exchange_as_bank_accounts.php`
- `database/factories/{BankAccount,Transfer}Factory.php`
- `database/seeders/SystemAccountsSeeder.php`
- `routes/bank.php`
- `resources/js/Pages/Bank/{Dashboard,Reconcile}.vue`
- `resources/js/Pages/Bank/Accounts/{Index,Create,Edit}.vue`
- `resources/js/Pages/Bank/Transfers/{Index,Create,Show}.vue`
- `tests/Feature/Bank/{AccountService,TransferService,ReconciliationService,AccountController,TransferController,ReconciliationController}Test.php`

**Modified**
- `app/Models/CashMovement.php`
- `app/Enums/CashMovementType.php`
- `app/Repositories/Contracts/CashMovementRepository.php`
- `app/Repositories/Eloquent/CashMovementEloquentRepository.php`
- `app/Services/PaymentService.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Billing/{PaymentController,ExpenseController}.php`
- `app/Providers/AppServiceProvider.php`
- `database/factories/CashMovementFactory.php`
- `database/seeders/{DatabaseSeeder,RoleSeeder}.php`
- `resources/js/Composables/useNav.js`
- `routes/web.php`
- `tests/Feature/Billing/PaymentServiceTest.php`
- `tests/Feature/DashboardControllerTest.php`

**Removed** (full list in spec §6.3 — `app/Models/{Currency,ExchangeRate,CashDrawer}.php`, `app/Repositories/{Contracts,Eloquent}/{Currency,ExchangeRate,CashDrawer}*Repository.php`, `app/Services/{CashDrawerService,ExchangeRateService,CashDrawerReconciliationService}.php`, the 4 `Exchange/*` controllers, the 4 `Exchange/*` requests, the artisan command, 3 factories, 3 seeders, 3 migrations, `routes/exchange.php`, all 7 Vue pages under `resources/js/Pages/Exchange/`, and the entire `tests/Feature/Exchange/` directory.)

---

## Task 1: Migration — rebuild exchange as bank_accounts

**Files:**
- Create: `database/migrations/2026_08_15_000001_rebuild_exchange_as_bank_accounts.php`
- Modify: `database/seeders/DatabaseSeeder.php` (only to call seeder in Task 2)

- [ ] **Step 1: Create the migration file with empty skeleton**

Create `database/migrations/2026_08_15_000001_rebuild_exchange_as_bank_accounts.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Drop the legacy tables in FK order.
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_drawers');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');

        // 2. Create the new bank_accounts table.
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 16); // cash | bank | external
            $table->string('bank_name', 120)->nullable();
            $table->string('account_name', 120)->nullable();
            $table->string('branch', 120)->nullable();
            $table->string('account_number', 64)->nullable();
            $table->string('currency_code', 3);
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('balance', 18, 4)->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->decimal('last_reconciled_balance', 18, 4)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['type', 'is_active']);
        });

        // (bank_name, account_number) uniqueness — composite index, MySQL
        // allows multiple NULLs so cash/external rows are unconstrained.
        Schema::table('bank_accounts', function (Blueprint $table): void {
            $table->unique(['bank_name', 'account_number'], 'bank_accounts_number_unique');
        });

        // 3. Create the transfers table.
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->decimal('amount', 18, 4);
            $table->timestamp('occurred_at');
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('transfer_group_id', 36)->unique();
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();

            $table->foreign('from_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('to_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['from_account_id', 'occurred_at']);
            $table->index(['to_account_id', 'occurred_at']);
        });

        // 4. Rebuild cash_movements against bank_accounts.
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('type', 20);
            $table->decimal('amount', 18, 4);
            $table->unsignedBigInteger('counter_account_id')->nullable();
            $table->decimal('counter_amount', 18, 4)->nullable();
            $table->string('transfer_group_id', 36)->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('counter_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['account_id', 'created_at']);
            $table->index('transfer_group_id');
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Hard cut — no rollback.
    }
};
```

- [ ] **Step 2: Run the migration against a fresh DB**

Run: `php artisan migrate:fresh`
Expected: all migrations run, the new `bank_accounts` / `transfers` / `cash_movements` tables are created, and the four legacy tables are absent.

- [ ] **Step 3: Verify table shape via sqlite/mysql**

Run: `php artisan db:show --counts`
Expected: `bank_accounts`, `transfers`, `cash_movements` present with the expected columns; `currencies`, `exchange_rates`, `cash_drawers` absent.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_08_15_000001_rebuild_exchange_as_bank_accounts.php
git commit -m "feat(bank): rebuild exchange as bank_accounts (hard cut)"
```

---

## Task 2: SystemAccountsSeeder + drop legacy seeders

**Files:**
- Create: `database/seeders/SystemAccountsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Delete: `database/seeders/{CurrencySeeder,ExchangeRateSeeder,CashDrawerSeeder}.php`
- Delete: `database/factories/{CurrencyFactory,ExchangeRateFactory,CashDrawerFactory}.php`
- Delete: `database/migrations/2026_08_14_000023_create_currencies_table.php`
- Delete: `database/migrations/2026_08_14_000024_create_exchange_rates_table.php`
- Delete: `database/migrations/2026_08_14_000025_create_cash_drawers_table.php`

- [ ] **Step 1: Create the new seeder**

Create `database/seeders/SystemAccountsSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

class SystemAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $currency = (string) settings('clinic.currency_code', 'BDT');

        $this->upsertSystem('Main Cash', $currency);
        $this->upsertSystem('External Adjustment', $currency);
    }

    private function upsertSystem(string $name, string $currency): void
    {
        $existing = BankAccount::query()->where('name', $name)->where('is_system', true)->first();
        if ($existing) {
            return;
        }

        $type = $name === 'Main Cash' ? 'cash' : 'external';

        BankAccount::query()->create([
            'name' => $name,
            'type' => $type,
            'currency_code' => $currency,
            'opening_balance' => 0,
            'balance' => 0,
            'is_system' => true,
            'is_active' => true,
        ]);
    }
}
```

- [ ] **Step 2: Delete legacy seeders and factories**

Run:

```bash
rm database/seeders/CurrencySeeder.php \
   database/seeders/ExchangeRateSeeder.php \
   database/seeders/CashDrawerSeeder.php \
   database/factories/CurrencyFactory.php \
   database/factories/ExchangeRateFactory.php \
   database/factories/CashDrawerFactory.php \
   database/migrations/2026_08_14_000023_create_currencies_table.php \
   database/migrations/2026_08_14_000024_create_exchange_rates_table.php \
   database/migrations/2026_08_14_000025_create_cash_drawers_table.php
```

- [ ] **Step 3: Wire the seeder into DatabaseSeeder**

Read `database/seeders/DatabaseSeeder.php`, find where `CurrencySeeder`, `ExchangeRateSeeder`, and `CashDrawerSeeder` are called, remove those three calls, and add `$this->call(SystemAccountsSeeder::class);` after `SettingsSeeder` (or wherever seeders are listed). Example final shape (adjust to match the file's actual order):

```php
public function run(): void
{
    $this->call([
        SettingsSeeder::class,
        RoleSeeder::class,
        SystemAccountsSeeder::class,
        // ...other existing seeders...
    ]);
}
```

- [ ] **Step 4: Run seed and verify two system accounts exist**

Run: `php artisan migrate:fresh --seed`
Expected: the seed runs without error. Verify with:

```bash
php artisan tinker --execute="echo \App\Models\BankAccount::count();"
```

Expected output: `2` (Main Cash + External Adjustment).

- [ ] **Step 5: Commit**

```bash
git add database/seeders/SystemAccountsSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(bank): seed Main Cash + External Adjustment system accounts"
```

---

## Task 3: BankAccountType enum

**Files:**
- Create: `app/Enums/BankAccountType.php`

- [ ] **Step 1: Create the enum**

Create `app/Enums/BankAccountType.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum BankAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank',
            self::External => 'External',
        };
    }
}
```

- [ ] **Step 2: Verify enum loads**

Run: `php artisan tinker --execute="echo \App\Enums\BankAccountType::Cash->label();"`
Expected output: `Cash`.

- [ ] **Step 3: Commit**

```bash
git add app/Enums/BankAccountType.php
git commit -m "feat(bank): add BankAccountType enum"
```

---

## Task 4: Updated CashMovementType enum

**Files:**
- Modify: `app/Enums/CashMovementType.php`

- [ ] **Step 1: Replace the enum body**

Open `app/Enums/CashMovementType.php` and replace the entire file contents with:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type of cash movement recorded against a bank_account.
 *
 * Sign convention:
 *   - Inflows (`cash_in`, `transfer_in`, `sale`, `refund`) → amount is positive.
 *   - Outflows (`cash_out`, `transfer_out`, `expense`)    → amount is negative.
 *   - `adjustment`                                        → sign reflects direction.
 */
enum CashMovementType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Expense = 'expense';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::CashIn => 'Cash in',
            self::CashOut => 'Cash out',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Expense => 'Expense',
            self::Refund => 'Refund',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CashIn, self::TransferIn, self::Sale, self::Refund => 'success',
            self::CashOut, self::TransferOut, self::Expense => 'danger',
            self::Adjustment => 'warning',
        };
    }

    public function isInflow(): bool
    {
        return in_array($this, [
            self::CashIn,
            self::TransferIn,
            self::Sale,
            self::Refund,
        ], true);
    }

    public function isOutflow(): bool
    {
        return in_array($this, [
            self::CashOut,
            self::TransferOut,
            self::Expense,
        ], true);
    }

    public function isTransfer(): bool
    {
        return $this === self::TransferIn || $this === self::TransferOut;
    }
}
```

- [ ] **Step 2: Verify enum loads**

Run: `php artisan tinker --execute="echo \App\Enums\CashMovementType::TransferIn->label();"`
Expected output: `Transfer in`.

- [ ] **Step 3: Commit**

```bash
git add app/Enums/CashMovementType.php
git commit -m "feat(bank): replace ExchangeIn/Out with TransferIn/Out"
```

---

## Task 5: BankAccount model + factory

**Files:**
- Create: `app/Models/BankAccount.php`
- Create: `database/factories/BankAccountFactory.php`

- [ ] **Step 1: Create the factory**

Create `database/factories/BankAccountFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Bank',
            'type' => 'bank',
            'bank_name' => $this->faker->company().' Bank',
            'account_name' => $this->faker->name(),
            'branch' => $this->faker->city(),
            'account_number' => (string) $this->faker->unique()->numerify('##########'),
            'currency_code' => 'BDT',
            'opening_balance' => 0,
            'balance' => 0,
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function cash(): self
    {
        return $this->state(fn () => [
            'type' => 'cash',
            'bank_name' => null,
            'account_name' => null,
            'branch' => null,
            'account_number' => null,
        ]);
    }

    public function external(): self
    {
        return $this->state(fn () => [
            'type' => 'external',
            'bank_name' => null,
            'account_name' => null,
            'branch' => null,
            'account_number' => null,
        ]);
    }
}
```

- [ ] **Step 2: Create the model**

Create `app/Models/BankAccount.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property ?string $bank_name
 * @property ?string $account_name
 * @property ?string $branch
 * @property ?string $account_number
 * @property string $currency_code
 * @property string $opening_balance
 * @property string $balance
 * @property bool $is_system
 * @property bool $is_active
 * @property ?Carbon $last_reconciled_at
 * @property ?string $last_reconciled_balance
 * @property ?int $created_by
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashMovement> $movements
 */
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'account_name',
        'branch',
        'account_number',
        'currency_code',
        'opening_balance',
        'balance',
        'is_system',
        'is_active',
        'last_reconciled_at',
        'last_reconciled_balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'balance' => 'decimal:4',
            'last_reconciled_balance' => 'decimal:4',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'last_reconciled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'bank_name', 'account_name', 'account_number', 'balance', 'is_active'])
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'account_id');
    }

    /**
     * @param  Builder<BankAccount>  $query
     * @return Builder<BankAccount>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

- [ ] **Step 3: Verify factory + model**

Run:

```bash
php artisan tinker --execute="\$a = \App\Models\BankAccount::factory()->bank()->create(); echo \$a->name;"
```

Expected: a bank account row is created, name printed.

- [ ] **Step 4: Commit**

```bash
git add app/Models/BankAccount.php database/factories/BankAccountFactory.php
git commit -m "feat(bank): add BankAccount model and factory"
```

---

## Task 6: Transfer model + factory

**Files:**
- Create: `app/Models/Transfer.php`
- Create: `database/factories/TransferFactory.php`

- [ ] **Step 1: Create the factory**

Create `database/factories/TransferFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'from_account_id' => BankAccount::factory(),
            'to_account_id' => BankAccount::factory(),
            'amount' => 100,
            'occurred_at' => now(),
            'reference' => $this->faker->optional()->bothify('REF-####'),
            'note' => $this->faker->optional()->sentence(),
            'transfer_group_id' => (string) Str::uuid(),
            'performed_by' => User::factory(),
        ];
    }
}
```

- [ ] **Step 2: Create the model**

Create `app/Models/Transfer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $from_account_id
 * @property int $to_account_id
 * @property string $amount
 * @property Carbon $occurred_at
 * @property ?string $reference
 * @property ?string $note
 * @property string $transfer_group_id
 * @property int $performed_by
 * @property-read BankAccount $fromAccount
 * @property-read BankAccount $toAccount
 * @property-read User $performer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashMovement> $movements
 */
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'occurred_at',
        'reference',
        'note',
        'transfer_group_id',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['from_account_id', 'to_account_id', 'amount', 'occurred_at', 'reference'])
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_account_id');
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'transfer_group_id', 'transfer_group_id');
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/Transfer.php database/factories/TransferFactory.php
git commit -m "feat(bank): add Transfer model and factory"
```

---

## Task 7: Updated CashMovement model + factory

**Files:**
- Modify: `app/Models/CashMovement.php`
- Modify: `database/factories/CashMovementFactory.php`

- [ ] **Step 1: Update the factory**

Read `database/factories/CashMovementFactory.php`, then replace the entire file with:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'account_id' => BankAccount::factory(),
            'type' => CashMovementType::CashIn,
            'amount' => 100,
            'reason' => 'Factory row',
            'performed_by' => User::factory(),
            'created_at' => now(),
        ];
    }

    public function transfer(string $groupId, int $fromId, int $toId, string $amount, CashMovementType $type): self
    {
        $counter = $type === CashMovementType::TransferIn ? $fromId : $toId;

        return $this->state(fn () => [
            'type' => $type,
            'amount' => $type === CashMovementType::TransferIn ? $amount : '-'.$amount,
            'counter_account_id' => $counter,
            'counter_amount' => $amount,
            'transfer_group_id' => $groupId,
            'reason' => null,
        ]);
    }
}
```

- [ ] **Step 2: Update the CashMovement model**

Open `app/Models/CashMovement.php` and replace the entire file with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashMovementType;
use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $account_id
 * @property CashMovementType $type
 * @property string $amount
 * @property ?int $counter_account_id
 * @property ?string $counter_amount
 * @property ?string $transfer_group_id
 * @property ?string $reason
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property int $performed_by
 * @property ?Carbon $created_at
 * @property-read BankAccount $account
 * @property-read ?BankAccount $counterAccount
 * @property-read User $performer
 * @property-read ?Model $reference
 */
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'counter_account_id',
        'counter_amount',
        'transfer_group_id',
        'reason',
        'reference_type',
        'reference_id',
        'performed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:4',
            'counter_amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('CashMovement rows are immutable.');
        });
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function counterAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'counter_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'amount', 'reason'])
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeOfType(Builder $query, CashMovementType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeForGroup(Builder $query, string $groupId): Builder
    {
        return $query->where('transfer_group_id', $groupId);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/CashMovement.php database/factories/CashMovementFactory.php
git commit -m "feat(bank): rebuild CashMovement against bank_accounts"
```

---

## Task 8: Repository contracts + implementations

**Files:**
- Create: `app/Repositories/Contracts/BankAccountRepository.php`
- Create: `app/Repositories/Contracts/TransferRepository.php`
- Create: `app/Repositories/Eloquent/BankAccountEloquentRepository.php`
- Create: `app/Repositories/Eloquent/TransferEloquentRepository.php`
- Modify: `app/Repositories/Contracts/CashMovementRepository.php`
- Modify: `app/Repositories/Eloquent/CashMovementEloquentRepository.php`
- Delete: `app/Repositories/Contracts/{Currency,ExchangeRate,CashDrawer}Repository.php`
- Delete: `app/Repositories/Eloquent/{Currency,ExchangeRate,CashDrawer}EloquentRepository.php`

- [ ] **Step 1: Create the BankAccountRepository contract**

Create `app/Repositories/Contracts/BankAccountRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BankAccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /** @return Collection<int, BankAccount> */
    public function allActive(): Collection;

    public function findById(int $id): ?BankAccount;

    public function findByIdOrFail(int $id): BankAccount;

    public function findSystemByName(string $name): ?BankAccount;

    /**
     * Lock the row for update. MUST be called inside a DB transaction.
     */
    public function lockById(int $id): BankAccount;

    public function incrementBalance(int $id, string $delta): void;

    public function stampReconciliation(int $id, string $countedBalance): void;

    public function create(array $data): BankAccount;

    public function update(BankAccount $account, array $data): BankAccount;
}
```

- [ ] **Step 2: Create the BankAccountEloquentRepository**

Create `app/Repositories/Eloquent/BankAccountEloquentRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\BankAccount;
use App\Repositories\Contracts\BankAccountRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BankAccountEloquentRepository implements BankAccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = BankAccount::query();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('name', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhere('account_number', 'like', $term);
            });
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function allActive(): Collection
    {
        return BankAccount::query()->active()->orderBy('name')->get();
    }

    public function findById(int $id): ?BankAccount
    {
        return BankAccount::query()->find($id);
    }

    public function findByIdOrFail(int $id): BankAccount
    {
        return BankAccount::query()->findOrFail($id);
    }

    public function findSystemByName(string $name): ?BankAccount
    {
        return BankAccount::query()->where('name', $name)->where('is_system', true)->first();
    }

    public function lockById(int $id): BankAccount
    {
        $row = BankAccount::query()->where('id', $id)->lockForUpdate()->first();
        if (! $row) {
            throw new RuntimeException("Bank account {$id} not found.");
        }

        return $row;
    }

    public function incrementBalance(int $id, string $delta): void
    {
        DB::table('bank_accounts')
            ->where('id', $id)
            ->update([
                'balance' => DB::raw('balance + '.((float) $delta)),
                'updated_at' => now(),
            ]);
    }

    public function stampReconciliation(int $id, string $countedBalance): void
    {
        DB::table('bank_accounts')
            ->where('id', $id)
            ->update([
                'last_reconciled_at' => now(),
                'last_reconciled_balance' => $countedBalance,
                'updated_at' => now(),
            ]);
    }

    public function create(array $data): BankAccount
    {
        $account = new BankAccount;
        $account->fill($data);
        $account->save();

        return $account;
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        $account->fill($data);
        $account->save();

        return $account;
    }
}
```

- [ ] **Step 3: Create the TransferRepository contract + implementation**

Create `app/Repositories/Contracts/TransferRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transfer;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransferRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int $id): Transfer;

    public function create(array $data): Transfer;
}
```

Create `app/Repositories/Eloquent/TransferEloquentRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Transfer;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TransferEloquentRepository implements TransferRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Transfer::query()->with(['fromAccount', 'toAccount', 'performer']);

        if (! empty($filters['date_from'])) {
            $q->whereDate('occurred_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('occurred_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['from_account_id'])) {
            $q->where('from_account_id', (int) $filters['from_account_id']);
        }

        if (! empty($filters['to_account_id'])) {
            $q->where('to_account_id', (int) $filters['to_account_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('reference', 'like', $term)->orWhere('note', 'like', $term);
            });
        }

        return $q->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): Transfer
    {
        return Transfer::query()->with(['fromAccount', 'toAccount', 'performer', 'movements.account'])
            ->findOrFail($id);
    }

    public function create(array $data): Transfer
    {
        $transfer = new Transfer;
        $transfer->fill($data);
        $transfer->save();

        return $transfer;
    }
}
```

- [ ] **Step 4: Update the CashMovementRepository**

Replace the contents of `app/Repositories/Contracts/CashMovementRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface CashMovementRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int $id): CashMovement;

    public function sumForAccountSince(int $accountId, Carbon $since): string;

    public function create(array $data): CashMovement;

    public function countByType(CashMovementType $type, ?int $accountId = null): int;
}
```

Replace the contents of `app/Repositories/Eloquent/CashMovementEloquentRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Repositories\Contracts\CashMovementRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class CashMovementEloquentRepository implements CashMovementRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = CashMovement::query()->with(['account', 'counterAccount', 'performer']);

        if (! empty($filters['account_id'])) {
            $q->where('account_id', (int) $filters['account_id']);
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $q->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('reason', 'like', $term)
                    ->orWhere('reference_type', 'like', $term);
            });
        }

        return $q->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): CashMovement
    {
        return CashMovement::query()->findOrFail($id);
    }

    public function sumForAccountSince(int $accountId, Carbon $since): string
    {
        $sum = CashMovement::query()
            ->forAccount($accountId)
            ->where('created_at', '>=', $since)
            ->sum('amount');

        return (string) $sum;
    }

    public function create(array $data): CashMovement
    {
        $data['created_at'] = $data['created_at'] ?? now();

        $movement = new CashMovement;
        $movement->fill($data);
        $movement->save();

        return $movement;
    }

    public function countByType(CashMovementType $type, ?int $accountId = null): int
    {
        $q = CashMovement::query()->ofType($type);
        if ($accountId) {
            $q->forAccount($accountId);
        }

        return $q->count();
    }
}
```

- [ ] **Step 5: Delete legacy repos**

Run:

```bash
rm app/Repositories/Contracts/CurrencyRepository.php \
   app/Repositories/Contracts/ExchangeRateRepository.php \
   app/Repositories/Contracts/CashDrawerRepository.php \
   app/Repositories/Eloquent/CurrencyEloquentRepository.php \
   app/Repositories/Eloquent/ExchangeRateEloquentRepository.php \
   app/Repositories/Eloquent/CashDrawerEloquentRepository.php
```

- [ ] **Step 6: Verify nothing else references the deleted contracts**

Run:

```bash
grep -rn "CurrencyRepository\|ExchangeRateRepository\|CashDrawerRepository" app/ database/ tests/ resources/ routes/ 2>/dev/null
```

Expected: no matches.

- [ ] **Step 7: Commit**

```bash
git add app/Repositories/
git commit -m "feat(bank): add BankAccount and Transfer repositories; drop legacy"
```

---

## Task 9: BankAccountService + TransferService + ReconciliationService

**Files:**
- Create: `app/Services/BankAccountService.php`
- Create: `app/Services/TransferService.php`
- Create: `app/Services/ReconciliationService.php`
- Delete: `app/Services/{CashDrawerService,ExchangeRateService,CashDrawerReconciliationService}.php`

- [ ] **Step 1: Create BankAccountService**

Create `app/Services/BankAccountService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BankAccountService
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly CashMovementRepository $movements,
        private readonly TransferRepository $transfers,
    ) {}

    public function create(array $data, int $userId): BankAccount
    {
        $type = $data['type'];

        if ($type === BankAccountType::Bank->value) {
            foreach (['bank_name', 'account_name', 'account_number'] as $req) {
                if (empty($data[$req])) {
                    throw new RuntimeException("Field {$req} is required for bank accounts.");
                }
            }
        } else {
            // cash / external — clear bank-only fields
            $data['bank_name'] = null;
            $data['account_name'] = null;
            $data['branch'] = null;
            $data['account_number'] = null;
        }

        $data['currency_code'] = $data['currency_code']
            ?? (string) settings('clinic.currency_code', 'BDT');
        $data['opening_balance'] = (string) ($data['opening_balance'] ?? 0);
        $data['balance'] = $data['opening_balance'];
        $data['is_system'] = false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_by'] = $userId;

        $opening = (float) $data['opening_balance'];
        $groupId = $opening > 0 ? (string) Str::uuid() : null;

        return DB::transaction(function () use ($data, $userId, $opening, $groupId): BankAccount {
            $account = $this->accounts->create($data);

            if ($opening > 0) {
                $external = $this->accounts->findSystemByName('External Adjustment');
                if (! $external) {
                    throw new RuntimeException('External Adjustment system account is missing. Run db:seed.');
                }

                $amount = number_format($opening, 4, '.', '');

                $this->transfers->create([
                    'from_account_id' => $external->id,
                    'to_account_id' => $account->id,
                    'amount' => $amount,
                    'occurred_at' => now(),
                    'reference' => 'Opening balance',
                    'note' => null,
                    'transfer_group_id' => $groupId,
                    'performed_by' => $userId,
                ]);

                $this->movements->create([
                    'account_id' => $external->id,
                    'type' => CashMovementType::TransferOut,
                    'amount' => '-'.$amount,
                    'counter_account_id' => $account->id,
                    'counter_amount' => $amount,
                    'transfer_group_id' => $groupId,
                    'reason' => 'Opening balance: '.$account->name,
                    'performed_by' => $userId,
                ]);

                $this->movements->create([
                    'account_id' => $account->id,
                    'type' => CashMovementType::TransferIn,
                    'amount' => $amount,
                    'counter_account_id' => $external->id,
                    'counter_amount' => $amount,
                    'transfer_group_id' => $groupId,
                    'reason' => 'Opening balance',
                    'performed_by' => $userId,
                ]);

                // account balance already set to opening_balance on create; external needs to drop
                $this->accounts->incrementBalance($external->id, '-'.$amount);
            }

            return $account;
        });
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        if ($account->is_system) {
            throw new RuntimeException('System accounts cannot be modified.');
        }

        if (isset($data['is_active']) && ! $data['is_active']) {
            if ($account->balance != 0) {
                throw new RuntimeException('Cannot deactivate an account with a non-zero balance.');
            }
        }

        return $this->accounts->update($account, $data);
    }

    /**
     * Record a non-transfer cash_movement against a bank account
     * (used by PaymentService for cash payments).
     */
    public function recordMovement(
        BankAccount $account,
        CashMovementType $type,
        string $amount,
        string $reason,
        ?Model $reference,
        int $userId,
    ): CashMovement {
        return DB::transaction(function () use ($account, $type, $amount, $reason, $reference, $userId): CashMovement {
            $locked = $this->accounts->lockById($account->id);

            if (! $locked->is_active) {
                throw new RuntimeException("Account {$account->name} is inactive.");
            }

            $signed = $type->isInflow() ? $amount : '-'.$amount;
            if ($type->isOutflow() && (float) $locked->balance < (float) $amount) {
                throw new RuntimeException("Insufficient balance on account {$account->name}.");
            }

            $data = [
                'account_id' => $account->id,
                'type' => $type,
                'amount' => $signed,
                'reason' => $reason,
                'performed_by' => $userId,
            ];

            if ($reference) {
                $data['reference_type'] = $reference->getMorphClass();
                $data['reference_id'] = (int) $reference->getKey();
            }

            $movement = $this->movements->create($data);

            $delta = $type->isInflow() ? (float) $amount : -(float) $amount;
            $this->accounts->incrementBalance($account->id, number_format($delta, 4, '.', ''));

            return $movement;
        });
    }

    public function findMainCash(): BankAccount
    {
        $cash = $this->accounts->findSystemByName('Main Cash');
        if (! $cash) {
            throw new RuntimeException('Main Cash system account is missing. Run db:seed.');
        }

        return $cash;
    }
}
```

- [ ] **Step 2: Create TransferService**

Create `app/Services/TransferService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TransferService
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly TransferRepository $transfers,
        private readonly CashMovementRepository $movements,
    ) {}

    /**
     * @return array{0: \App\Models\Transfer, 1: \App\Models\CashMovement, 2: \App\Models\CashMovement}
     */
    public function execute(int $fromId, int $toId, string $amount, ?string $occurredAt, ?string $reference, ?string $note, int $userId): array
    {
        if ($fromId === $toId) {
            throw new RuntimeException('Source and destination accounts must be different.');
        }

        if ((float) $amount <= 0) {
            throw new RuntimeException('Transfer amount must be positive.');
        }

        return DB::transaction(function () use ($fromId, $toId, $amount, $occurredAt, $reference, $note, $userId): array {
            // Lock both rows in a deterministic (lexical id) order to avoid deadlocks.
            [$first, $second] = $fromId < $toId ? [$fromId, $toId] : [$toId, $fromId];
            $this->accounts->lockById($first);
            $this->accounts->lockById($second);

            $from = $this->accounts->lockById($fromId);
            $to = $this->accounts->lockById($toId);

            if (! $from->is_active || ! $to->is_active) {
                throw new RuntimeException('Both accounts must be active to transfer.');
            }

            if ((float) $from->balance < (float) $amount) {
                throw new RuntimeException("Insufficient balance on account {$from->name}.");
            }

            $groupId = (string) Str::uuid();
            $signed = number_format((float) $amount, 4, '.', '');

            $transfer = $this->transfers->create([
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => $signed,
                'occurred_at' => $occurredAt ? \Illuminate\Support\Carbon::parse($occurredAt) : now(),
                'reference' => $reference,
                'note' => $note,
                'transfer_group_id' => $groupId,
                'performed_by' => $userId,
            ]);

            $out = $this->movements->create([
                'account_id' => $from->id,
                'type' => CashMovementType::TransferOut,
                'amount' => '-'.$signed,
                'counter_account_id' => $to->id,
                'counter_amount' => $signed,
                'transfer_group_id' => $groupId,
                'reason' => 'Transfer to '.$to->name,
                'performed_by' => $userId,
            ]);

            $in = $this->movements->create([
                'account_id' => $to->id,
                'type' => CashMovementType::TransferIn,
                'amount' => $signed,
                'counter_account_id' => $from->id,
                'counter_amount' => $signed,
                'transfer_group_id' => $groupId,
                'reason' => 'Transfer from '.$from->name,
                'performed_by' => $userId,
            ]);

            $this->accounts->incrementBalance($from->id, '-'.$signed);
            $this->accounts->incrementBalance($to->id, $signed);

            return [$transfer, $out, $in];
        });
    }
}
```

- [ ] **Step 3: Create ReconciliationService**

Create `app/Services/ReconciliationService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReconciliationService
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly TransferRepository $transfers,
        private readonly CashMovementRepository $movements,
        private readonly TransferService $transferService,
    ) {}

    public function snapshot(int $accountId): array
    {
        $account = $this->accounts->findByIdOrFail($accountId);
        $recent = $this->movements->paginate(10, ['account_id' => $accountId]);

        return [
            'account' => $account,
            'balance' => (string) $account->balance,
            'last_reconciled_at' => $account->last_reconciled_at,
            'last_reconciled_balance' => $account->last_reconciled_balance !== null
                ? (string) $account->last_reconciled_balance
                : null,
            'recent_movements' => $recent,
        ];
    }

    public function run(int $accountId, string $countedBalance, int $userId): void
    {
        $external = $this->accounts->findSystemByName('External Adjustment');
        if (! $external) {
            throw new RuntimeException('External Adjustment system account is missing. Run db:seed.');
        }

        DB::transaction(function () use ($accountId, $countedBalance, $userId, $external): void {
            $account = $this->accounts->lockById($accountId);
            $delta = (float) $countedBalance - (float) $account->balance;

            if (abs($delta) < 0.0001) {
                $this->accounts->stampReconciliation($accountId, $countedBalance);

                return;
            }

            $amount = number_format(abs($delta), 4, '.', '');

            if ($delta > 0) {
                $fromId = $external->id;
                $toId = $account->id;
            } else {
                $fromId = $account->id;
                $toId = $external->id;
            }

            // External Adjustment must be active — it is by default.
            $this->transferService->execute(
                $fromId,
                $toId,
                $amount,
                Carbon::now()->toIso8601String(),
                'Reconciliation variance',
                'Auto-generated by reconciliation on account '.$account->name,
                $userId,
            );

            $this->accounts->stampReconciliation($accountId, $countedBalance);
        });
    }
}
```

- [ ] **Step 4: Delete legacy services**

Run:

```bash
rm app/Services/CashDrawerService.php \
   app/Services/ExchangeRateService.php \
   app/Services/CashDrawerReconciliationService.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Services/
git commit -m "feat(bank): add BankAccount, Transfer, Reconciliation services"
```

---

## Task 10: AppServiceProvider rewiring

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Update imports and bindings**

Open `app/Providers/AppServiceProvider.php`. Replace the `use App\Repositories\Contracts\...` import block (lines 24–43) with the new set (add `BankAccountRepository`, `TransferRepository`; drop `CurrencyRepository`, `ExchangeRateRepository`, `CashDrawerRepository`):

```php
use App\Repositories\Contracts\AccountRepository;
use App\Repositories\Contracts\AppointmentRepository;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\DoctorRepository;
use App\Repositories\Contracts\DoctorScheduleRepository;
use App\Repositories\Contracts\DoctorTimeOffRepository;
use App\Repositories\Contracts\ExpenseCategoryRepository;
use App\Repositories\Contracts\ExpenseRepository;
use App\Repositories\Contracts\InvoiceRepository;
use App\Repositories\Contracts\PatientRepository;
use App\Repositories\Contracts\PaymentRepository;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\StockLocationRepository;
use App\Repositories\Contracts\StockMovementRepository;
use App\Repositories\Contracts\SupplierRepository;
use App\Repositories\Contracts\TransferRepository;
use App\Repositories\Contracts\TreatmentRecordRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\AccountEloquentRepository;
use App\Repositories\Eloquent\AppointmentEloquentRepository;
use App\Repositories\Eloquent\BankAccountEloquentRepository;
use App\Repositories\Eloquent\CashMovementEloquentRepository;
use App\Repositories\Eloquent\DoctorEloquentRepository;
use App\Repositories\Eloquent\DoctorScheduleEloquentRepository;
use App\Repositories\Eloquent\DoctorTimeOffRepository;
use App\Repositories\Eloquent\ExpenseCategoryEloquentRepository;
use App\Repositories\Eloquent\ExpenseEloquentRepository;
use App\Repositories\Eloquent\InvoiceEloquentRepository;
use App\Repositories\Eloquent\PatientEloquentRepository;
use App\Repositories\Eloquent\PaymentEloquentRepository;
use App\Repositories\Eloquent\ProductEloquentRepository;
use App\Repositories\Eloquent\StockLocationEloquentRepository;
use App\Repositories\Eloquent\StockMovementEloquentRepository;
use App\Repositories\Eloquent\SupplierEloquentRepository;
use App\Repositories\Eloquent\TransferEloquentRepository;
use App\Repositories\Eloquent\TreatmentRecordEloquentRepository;
use App\Repositories\Eloquent\UserEloquentRepository;
use App\Services\BankAccountService;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\LedgerService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\ReconciliationService;
use App\Services\TransferService;
```

In the `register()` method:

- Remove the three binding blocks for `CurrencyRepository`, `ExchangeRateRepository`, `CashDrawerRepository`.
- Replace the `CashMovementRepository` binding with no change (it already binds).
- Add after the `CashMovementRepository` binding:

```php
$this->app->bind(BankAccountRepository::class, BankAccountEloquentRepository::class);
$this->app->bind(TransferRepository::class, TransferEloquentRepository::class);
```

- Remove the three singleton bindings for `ExchangeRateService`, `CashDrawerService`, `CashDrawerReconciliationService`.
- Add (alongside the existing `NotificationService` singleton):

```php
$this->app->singleton(BankAccountService::class);
$this->app->singleton(TransferService::class);
$this->app->singleton(ReconciliationService::class);
```

- [ ] **Step 2: Verify the provider compiles**

Run: `php artisan about`
Expected: command runs without error and lists providers.

- [ ] **Step 3: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "chore(bank): rewire AppServiceProvider for bank accounts"
```

---

## Task 11: Form requests for the new controllers

**Files:**
- Create: `app/Http/Requests/Bank/StoreBankAccountRequest.php`
- Create: `app/Http/Requests/Bank/UpdateBankAccountRequest.php`
- Create: `app/Http/Requests/Bank/StoreTransferRequest.php`
- Create: `app/Http/Requests/Bank/ReconcileAccountRequest.php`
- Delete: `app/Http/Requests/Exchange/{StoreExchangeRateRequest,UpdateExchangeRateRequest,StoreCashMovementRequest,ReconcileDrawerRequest}.php`

- [ ] **Step 1: Create StoreBankAccountRequest**

Create `app/Http/Requests/Bank/StoreBankAccountRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use App\Enums\BankAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-accounts') ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(BankAccountType::class)],
            'branch' => ['nullable', 'string', 'max:120'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];

        if ($type === BankAccountType::Bank->value) {
            $rules['bank_name'] = ['required', 'string', 'max:120'];
            $rules['account_name'] = ['required', 'string', 'max:120'];
            $rules['account_number'] = [
                'required',
                'string',
                'max:64',
                Rule::unique('bank_accounts', 'account_number')
                    ->where('bank_name', $this->input('bank_name')),
            ];
        }

        return $rules;
    }
}
```

- [ ] **Step 2: Create UpdateBankAccountRequest**

Create `app/Http/Requests/Bank/UpdateBankAccountRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-accounts') ?? false;
    }

    public function rules(): array
    {
        $account = $this->route('account');

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];

        if ($this->input('type') === 'bank' || ($account && $account->type === 'bank')) {
            $rules['bank_name'] = ['sometimes', 'required', 'string', 'max:120'];
            $rules['account_name'] = ['sometimes', 'required', 'string', 'max:120'];
            $rules['account_number'] = [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('bank_accounts', 'account_number')
                    ->where('bank_name', $this->input('bank_name', $account?->bank_name))
                    ->ignore($account?->id),
            ];
        }

        return $rules;
    }
}
```

- [ ] **Step 3: Create StoreTransferRequest**

Create `app/Http/Requests/Bank/StoreTransferRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('record-transfers') ?? false;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'to_account_id' => [
                'required',
                'integer',
                'different:from_account_id',
                Rule::exists('bank_accounts', 'id'),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'occurred_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 4: Create ReconcileAccountRequest**

Create `app/Http/Requests/Bank/ReconcileAccountRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconcileAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reconcile-accounts') ?? false;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'counted_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

- [ ] **Step 5: Delete legacy Exchange requests**

Run:

```bash
rm -r app/Http/Requests/Exchange
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/Bank/ app/Http/Requests/Exchange
git commit -m "feat(bank): add Bank form requests; drop Exchange requests"
```

---

## Task 12: Bank controllers

**Files:**
- Create: `app/Http/Controllers/Bank/DashboardController.php`
- Create: `app/Http/Controllers/Bank/AccountController.php`
- Create: `app/Http/Controllers/Bank/TransferController.php`
- Create: `app/Http/Controllers/Bank/ReconciliationController.php`
- Delete: `app/Http/Controllers/Exchange/{DashboardController,AccountController,TransferController,ReconciliationController}*.php` (the whole `app/Http/Controllers/Exchange/` directory)
- Delete: `app/Http/Controllers/Exchange/ExchangeRateController.php`
- Delete: `app/Http/Controllers/Exchange/CashMovementController.php`
- Delete: `app/Http/Controllers/Exchange/ReconciliationController.php`

- [ ] **Step 1: Create Bank/DashboardController**

Create `app/Http/Controllers/Bank/DashboardController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BankAccountRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly BankAccountRepository $accounts) {}

    public function index(): Response
    {
        $accounts = $this->accounts->paginate(50);

        return Inertia::render('Bank/Dashboard', [
            'accounts' => $accounts,
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }
}
```

- [ ] **Step 2: Create Bank/AccountController**

Create `app/Http/Controllers/Bank/AccountController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankAccountRequest;
use App\Http\Requests\Bank\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Repositories\Contracts\BankAccountRepository;
use App\Services\BankAccountService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AccountController extends Controller
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly BankAccountService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Bank/Accounts/Index', [
            'accounts' => $this->accounts->paginate(
                request()->integer('per_page', 25),
                request()->only(['q', 'type']),
            ),
            'filters' => request()->only(['q', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bank/Accounts/Create', [
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated(), $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['opening_balance' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.accounts.index')->with('success', 'Account created.');
    }

    public function edit(BankAccount $account): Response
    {
        return Inertia::render('Bank/Accounts/Edit', [
            'account' => $account,
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        try {
            $this->service->update($account, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withErrors(['is_active' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.accounts.index')->with('success', 'Account updated.');
    }
}
```

- [ ] **Step 3: Create Bank/TransferController**

Create `app/Http/Controllers/Bank/TransferController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreTransferRequest;
use App\Models\Transfer;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\TransferRepository;
use App\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferRepository $transfers,
        private readonly BankAccountRepository $accounts,
        private readonly TransferService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Bank/Transfers/Index', [
            'transfers' => $this->transfers->paginate(
                request()->integer('per_page', 25),
                request()->only(['date_from', 'date_to', 'from_account_id', 'to_account_id', 'q']),
            ),
            'filters' => request()->only(['date_from', 'date_to', 'from_account_id', 'to_account_id', 'q']),
            'accounts' => $this->accounts->allActive()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bank/Transfers/Create', [
            'accounts' => $this->accounts->allActive()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'balance' => (string) $a->balance,
            ])->values(),
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        try {
            $this->service->execute(
                (int) $request->validated('from_account_id'),
                (int) $request->validated('to_account_id'),
                (string) $request->validated('amount'),
                $request->validated('occurred_at'),
                $request->validated('reference'),
                $request->validated('note'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.transfers.index')->with('success', 'Transfer recorded.');
    }

    public function show(Transfer $transfer): Response
    {
        $transfer = $this->transfers->findByIdOrFail($transfer->id);

        return Inertia::render('Bank/Transfers/Show', [
            'transfer' => $transfer,
            'movements' => $transfer->movements()->with('account')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Create Bank/ReconciliationController**

Create `app/Http/Controllers/Bank/ReconciliationController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\ReconcileAccountRequest;
use App\Repositories\Contracts\BankAccountRepository;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReconciliationController extends Controller
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly ReconciliationService $service,
    ) {}

    public function index(): Response
    {
        $rows = $this->accounts->allActive()->map(function ($account) {
            $snap = $this->service->snapshot($account->id);

            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $snap['balance'],
                'last_reconciled_at' => $account->last_reconciled_at?->toIso8601String(),
                'last_reconciled_balance' => $snap['last_reconciled_balance'],
            ];
        })->values();

        return Inertia::render('Bank/Reconcile', [
            'accounts' => $rows,
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }

    public function run(ReconcileAccountRequest $request): RedirectResponse
    {
        try {
            $this->service->run(
                (int) $request->validated('account_id'),
                (string) $request->validated('counted_balance'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['counted_balance' => $e->getMessage()]);
        }

        return redirect()->route('bank.reconcile.index')->with('success', 'Reconciled.');
    }
}
```

- [ ] **Step 5: Delete legacy Exchange controllers**

Run:

```bash
rm -r app/Http/Controllers/Exchange
```

- [ ] **Step 6: Verify nothing references the deleted controllers**

Run:

```bash
grep -rn "Exchange\\\\\(DashboardController\|ExchangeRateController\|CashMovementController\|ReconciliationController\)" app/ database/ tests/ resources/ routes/ 2>/dev/null
```

Expected: no matches.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Bank/ app/Http/Controllers/Exchange
git commit -m "feat(bank): add Bank controllers; drop Exchange controllers"
```

---

## Task 13: Routes file + RoleSeeder permissions

**Files:**
- Create: `routes/bank.php`
- Modify: `routes/web.php`
- Delete: `routes/exchange.php`
- Modify: `database/seeders/RoleSeeder.php`

- [ ] **Step 1: Create routes/bank.php**

Create `routes/bank.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Bank\AccountController;
use App\Http\Controllers\Bank\DashboardController;
use App\Http\Controllers\Bank\ReconciliationController;
use App\Http\Controllers\Bank\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-accounts'])
    ->get('/bank', [DashboardController::class, 'index'])
    ->name('bank.dashboard');

Route::middleware(['auth', 'active', 'permission:view-accounts'])
    ->prefix('bank/accounts')->name('bank.accounts.')->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'active', 'permission:manage-accounts'])
    ->prefix('bank/accounts')->name('bank.accounts.')->group(function (): void {
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::patch('/{account}', [AccountController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'active', 'permission:record-transfers'])
    ->prefix('bank/transfers')->name('bank.transfers.')->group(function (): void {
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
    });

Route::middleware(['auth', 'active', 'permission:view-transfers'])
    ->prefix('bank/transfers')->name('bank.transfers.')->group(function (): void {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/{transfer}', [TransferController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:reconcile-accounts'])
    ->prefix('bank/reconcile')->name('bank.reconcile.')->group(function (): void {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::post('/run', [ReconciliationController::class, 'run'])->name('run');
    });
```

- [ ] **Step 2: Update routes/web.php**

Open `routes/web.php`, find the line that loads `exchange.php` (likely `require __DIR__.'/exchange.php';`), and replace with:

```php
require __DIR__.'/bank.php';
```

- [ ] **Step 3: Delete routes/exchange.php**

Run: `rm routes/exchange.php`

- [ ] **Step 4: Update RoleSeeder permissions**

Open `database/seeders/RoleSeeder.php`. Find every line that grants or lists `manage-exchange-rates` or `view-exchange-rates` and:

- Replace `manage-exchange-rates` → `manage-accounts`
- Replace `view-exchange-rates` → `view-accounts`
- Find references to `record-cash-movements` → `record-transfers`
- Find references to `view-cash-movements` → `view-transfers`
- Find references to `reconcile-cash-drawer` → `reconcile-accounts`

Make sure all five new permissions (`manage-accounts`, `view-accounts`, `record-transfers`, `view-transfers`, `reconcile-accounts`) are present in the permission-list block of the seeder, and the per-role assignment list is updated accordingly (e.g., admin gets all five; doctor gets `view-accounts`, `view-transfers`; receptionist gets `view-accounts`, `record-transfers`, `view-transfers`).

- [ ] **Step 5: Run the seed and confirm permissions**

Run: `php artisan db:seed --class=RoleSeeder --force`
Expected: no errors.

Run: `php artisan tinker --execute="echo \\\\Spatie\\\\Permission\\\\Models\\\\Permission::whereIn('name', ['manage-accounts','view-accounts','record-transfers','view-transfers','reconcile-accounts'])->count();"`
Expected: `5`.

- [ ] **Step 6: Verify the route list**

Run: `php artisan route:list --path=bank`
Expected: at least the 11 routes listed in the spec §6.2.

- [ ] **Step 7: Commit**

```bash
git add routes/bank.php routes/web.php routes/exchange.php database/seeders/RoleSeeder.php
git commit -m "feat(bank): switch routes from exchange to bank; update permissions"
```

---

## Task 14: Delete remaining legacy code

**Files:**
- Delete: `app/Models/{Currency.php,ExchangeRate.php}`
- Delete: `app/Console/Commands/ExchangeReconcileCashVsLedger.php`
- Delete: `resources/js/Pages/Exchange/` (whole directory)
- Delete: `resources/js/Composables/useNav.js` Exchange group (modify instead of deleting the file)

- [ ] **Step 1: Delete legacy models and the artisan command**

Run:

```bash
rm app/Models/Currency.php app/Models/ExchangeRate.php app/Console/Commands/ExchangeReconcileCashVsLedger.php
```

- [ ] **Step 2: Delete the legacy Exchange Vue pages**

Run: `rm -r resources/js/Pages/Exchange`

- [ ] **Step 3: Update useNav.js to swap the Exchange group for a Bank & Cash group**

Open `resources/js/Composables/useNav.js`. Find the existing `Exchange` group entry (search for `Exchange` or `exchange` in the nav definitions). Replace it with a `Bank & Cash` group:

```js
{
  label: 'Bank & Cash',
  href: '/bank',
  icon: 'wallet',
  permission: 'view-accounts',
  children: [
    { label: 'Dashboard',  href: '/bank',                  permission: 'view-accounts' },
    { label: 'Accounts',   href: '/bank/accounts',         permission: 'view-accounts' },
    { label: 'Transfers',  href: '/bank/transfers',        permission: 'view-transfers' },
    { label: 'Reconcile',  href: '/bank/reconcile',        permission: 'reconcile-accounts' },
  ],
}
```

(Keep the rest of the file intact. If the existing nav uses a different shape — e.g. `labelKey`, `href: route('exchange.dashboard')` — adapt to the existing convention. The key change is `exchange` → `bank` and the permission names.)

- [ ] **Step 4: Verify no broken imports remain**

Run: `npx eslint resources/js --ext .js,.vue 2>&1 | head -30`
Expected: no errors mentioning `Exchange` or `exchange`.

- [ ] **Step 5: Commit**

```bash
git add -u
git commit -m "chore(bank): remove legacy exchange models, command, and pages"
```

---

## Task 15: Frontend — Bank pages (skeleton)

**Files:**
- Create: `resources/js/Pages/Bank/Dashboard.vue`
- Create: `resources/js/Pages/Bank/Accounts/Index.vue`
- Create: `resources/js/Pages/Bank/Accounts/Create.vue`
- Create: `resources/js/Pages/Bank/Accounts/Edit.vue`
- Create: `resources/js/Pages/Bank/Transfers/Index.vue`
- Create: `resources/js/Pages/Bank/Transfers/Create.vue`
- Create: `resources/js/Pages/Bank/Transfers/Show.vue`
- Create: `resources/js/Pages/Bank/Reconcile.vue`

- [ ] **Step 1: Build the Bank Dashboard page**

Create `resources/js/Pages/Bank/Dashboard.vue` (minimal, no design polish yet — this is a stub we'll harden in a follow-up):

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({ accounts: Object, currency: String });
</script>

<template>
  <Head title="Bank & Cash" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold">Bank & Cash</h2>
    </template>

    <div class="p-6 space-y-4">
      <p class="text-sm text-base-content/70">
        Total in {{ currency }}. Active accounts: {{ accounts.total }}.
      </p>

      <table class="table table-zebra">
        <thead>
          <tr><th>Name</th><th>Type</th><th class="text-right">Balance</th></tr>
        </thead>
        <tbody>
          <tr v-for="a in accounts.data" :key="a.id">
            <td>{{ a.name }}</td>
            <td>{{ a.type }}</td>
            <td class="text-right">{{ a.balance }}</td>
          </tr>
        </tbody>
      </table>

      <div class="flex gap-2">
        <Link :href="route('bank.accounts.create')" class="btn btn-primary">New account</Link>
        <Link :href="route('bank.transfers.create')" class="btn btn-secondary">New transfer</Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Build Accounts/Index, Create, Edit**

Create `resources/js/Pages/Bank/Accounts/Index.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ accounts: Object, filters: Object });

const form = useForm({ q: props.filters?.q ?? '', type: props.filters?.type ?? '' });
const submit = () => form.get(route('bank.accounts.index'), { preserveState: true });
</script>

<template>
  <Head title="Bank accounts" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Bank accounts</h2></template>

    <div class="p-6 space-y-4">
      <form @submit.prevent="submit" class="flex gap-2">
        <input v-model="form.q" placeholder="Search…" class="input input-bordered" />
        <select v-model="form.type" class="select select-bordered">
          <option value="">All types</option>
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="external">External</option>
        </select>
        <button class="btn">Filter</button>
      </form>

      <table class="table table-zebra">
        <thead><tr><th>Name</th><th>Type</th><th>Bank</th><th>Account #</th><th class="text-right">Balance</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in accounts.data" :key="a.id">
            <td>{{ a.name }}</td>
            <td>{{ a.type }}</td>
            <td>{{ a.bank_name }}</td>
            <td>{{ a.account_number }}</td>
            <td class="text-right">{{ a.balance }}</td>
            <td><Link :href="route('bank.accounts.edit', a.id)" class="btn btn-xs">Edit</Link></td>
          </tr>
        </tbody>
      </table>

      <Link :href="route('bank.accounts.create')" class="btn btn-primary">New account</Link>
    </div>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Bank/Accounts/Create.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ currency: String });

const form = useForm({
  name: '',
  type: 'bank',
  bank_name: '',
  account_name: '',
  branch: '',
  account_number: '',
  opening_balance: 0,
  is_active: true,
});

const submit = () => form.post(route('bank.accounts.store'));
</script>

<template>
  <Head title="New account" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">New account</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div>
        <label class="label">Name</label>
        <input v-model="form.name" class="input input-bordered w-full" />
        <p v-if="form.errors.name" class="text-error text-sm">{{ form.errors.name }}</p>
      </div>
      <div>
        <label class="label">Type</label>
        <select v-model="form.type" class="select select-bordered w-full">
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="external">External</option>
        </select>
      </div>
      <template v-if="form.type === 'bank'">
        <div><label class="label">Bank name</label><input v-model="form.bank_name" class="input input-bordered w-full" />
          <p v-if="form.errors.bank_name" class="text-error text-sm">{{ form.errors.bank_name }}</p>
        </div>
        <div><label class="label">Account name</label><input v-model="form.account_name" class="input input-bordered w-full" />
          <p v-if="form.errors.account_name" class="text-error text-sm">{{ form.errors.account_name }}</p>
        </div>
        <div><label class="label">Branch</label><input v-model="form.branch" class="input input-bordered w-full" /></div>
        <div><label class="label">Account number</label><input v-model="form.account_number" class="input input-bordered w-full" />
          <p v-if="form.errors.account_number" class="text-error text-sm">{{ form.errors.account_number }}</p>
        </div>
      </template>
      <div>
        <label class="label">Opening balance (in {{ currency }})</label>
        <input v-model="form.opening_balance" type="number" step="0.01" min="0" class="input input-bordered w-full" />
        <p v-if="form.errors.opening_balance" class="text-error text-sm">{{ form.errors.opening_balance }}</p>
      </div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.accounts.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Bank/Accounts/Edit.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ account: Object, currency: String });

const form = useForm({
  name: props.account.name,
  bank_name: props.account.bank_name,
  account_name: props.account.account_name,
  branch: props.account.branch,
  account_number: props.account.account_number,
  is_active: !!props.account.is_active,
});

const submit = () => form.patch(route('bank.accounts.update', props.account.id));
</script>

<template>
  <Head title="Edit account" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Edit account</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div><label class="label">Name</label><input v-model="form.name" class="input input-bordered w-full" /></div>
      <div><label class="label">Bank name</label><input v-model="form.bank_name" class="input input-bordered w-full" /></div>
      <div><label class="label">Account name</label><input v-model="form.account_name" class="input input-bordered w-full" /></div>
      <div><label class="label">Branch</label><input v-model="form.branch" class="input input-bordered w-full" /></div>
      <div><label class="label">Account number</label><input v-model="form.account_number" class="input input-bordered w-full" /></div>
      <div><label class="label">Opening balance</label><input :value="account.opening_balance" disabled class="input input-bordered w-full" /></div>
      <div class="flex items-center gap-2">
        <input v-model="form.is_active" type="checkbox" class="checkbox" />
        <label>Active</label>
      </div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.accounts.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Build Transfers pages**

Create `resources/js/Pages/Bank/Transfers/Index.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ transfers: Object, accounts: Array, filters: Object });

const form = useForm({
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  from_account_id: props.filters?.from_account_id ?? '',
  to_account_id: props.filters?.to_account_id ?? '',
  q: props.filters?.q ?? '',
});
const submit = () => form.get(route('bank.transfers.index'), { preserveState: true });
</script>

<template>
  <Head title="Transfers" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Transfers</h2></template>

    <div class="p-6 space-y-4">
      <form @submit.prevent="submit" class="flex flex-wrap gap-2">
        <input v-model="form.date_from" type="date" class="input input-bordered" />
        <input v-model="form.date_to" type="date" class="input input-bordered" />
        <input v-model="form.q" placeholder="Search…" class="input input-bordered" />
        <button class="btn">Filter</button>
      </form>

      <table class="table table-zebra">
        <thead><tr><th>Date</th><th>From</th><th>To</th><th class="text-right">Amount</th><th>Reference</th><th></th></tr></thead>
        <tbody>
          <tr v-for="t in transfers.data" :key="t.id">
            <td>{{ t.occurred_at }}</td>
            <td>{{ t.from_account?.name }}</td>
            <td>{{ t.to_account?.name }}</td>
            <td class="text-right">{{ t.amount }}</td>
            <td>{{ t.reference }}</td>
            <td><Link :href="route('bank.transfers.show', t.id)" class="btn btn-xs">View</Link></td>
          </tr>
        </tbody>
      </table>

      <Link :href="route('bank.transfers.create')" class="btn btn-primary">New transfer</Link>
    </div>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Bank/Transfers/Create.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ accounts: Array, currency: String });

const form = useForm({
  from_account_id: props.accounts[0]?.id ?? '',
  to_account_id: '',
  amount: '',
  occurred_at: new Date().toISOString().slice(0, 16),
  reference: '',
  note: '',
});

const submit = () => form.post(route('bank.transfers.store'));
</script>

<template>
  <Head title="New transfer" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">New transfer</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div>
        <label class="label">From</label>
        <select v-model="form.from_account_id" class="select select-bordered w-full">
          <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.balance }})</option>
        </select>
        <p v-if="form.errors.from_account_id" class="text-error text-sm">{{ form.errors.from_account_id }}</p>
      </div>
      <div>
        <label class="label">To</label>
        <select v-model="form.to_account_id" class="select select-bordered w-full">
          <option v-for="a in accounts" :key="a.id" :value="a.id" :disabled="a.id === form.from_account_id">{{ a.name }} ({{ a.balance }})</option>
        </select>
        <p v-if="form.errors.to_account_id" class="text-error text-sm">{{ form.errors.to_account_id }}</p>
      </div>
      <div>
        <label class="label">Amount ({{ currency }})</label>
        <input v-model="form.amount" type="number" step="0.01" min="0.01" class="input input-bordered w-full" />
        <p v-if="form.errors.amount" class="text-error text-sm">{{ form.errors.amount }}</p>
      </div>
      <div><label class="label">Date</label><input v-model="form.occurred_at" type="datetime-local" class="input input-bordered w-full" /></div>
      <div><label class="label">Reference</label><input v-model="form.reference" class="input input-bordered w-full" /></div>
      <div><label class="label">Note</label><textarea v-model="form.note" class="textarea textarea-bordered w-full" /></div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.transfers.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Bank/Transfers/Show.vue`:

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({ transfer: Object, movements: Array });
</script>

<template>
  <Head :title="`Transfer ${transfer.id}`" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Transfer #{{ transfer.id }}</h2></template>

    <div class="p-6 space-y-4">
      <div class="text-sm">
        <div><strong>From:</strong> {{ transfer.from_account?.name }}</div>
        <div><strong>To:</strong> {{ transfer.to_account?.name }}</div>
        <div><strong>Amount:</strong> {{ transfer.amount }}</div>
        <div><strong>Date:</strong> {{ transfer.occurred_at }}</div>
        <div><strong>Reference:</strong> {{ transfer.reference }}</div>
        <div><strong>Group:</strong> <code>{{ transfer.transfer_group_id }}</code></div>
      </div>

      <h3 class="font-semibold">Movements</h3>
      <table class="table table-zebra">
        <thead><tr><th>Account</th><th>Type</th><th class="text-right">Amount</th></tr></thead>
        <tbody>
          <tr v-for="m in movements" :key="m.id">
            <td>{{ m.account?.name }}</td>
            <td>{{ m.type }}</td>
            <td class="text-right">{{ m.amount }}</td>
          </tr>
        </tbody>
      </table>

      <Link :href="route('bank.transfers.index')" class="btn">Back</Link>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Build Reconcile page**

Create `resources/js/Pages/Bank/Reconcile.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({ accounts: Array, currency: String });

const selectedId = ref(props.accounts[0]?.id ?? null);
const selected = computed(() => props.accounts.find((a) => a.id === selectedId.value));

const form = useForm({ account_id: selectedId.value, counted_balance: 0 });
const choose = (id) => {
  selectedId.value = id;
  const acc = props.accounts.find((a) => a.id === id);
  form.account_id = id;
  form.counted_balance = Number(acc?.balance ?? 0);
};
const submit = () => form.post(route('bank.reconcile.run'));
</script>

<template>
  <Head title="Reconcile" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Reconcile account</h2></template>

    <div class="p-6 space-y-4 max-w-xl">
      <select :value="selectedId" @change="choose($event.target.value)" class="select select-bordered w-full">
        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} — {{ a.balance }} {{ currency }}</option>
      </select>

      <div v-if="selected" class="text-sm">
        <div>Last reconciled: {{ selected.last_reconciled_at ?? 'Never' }}</div>
        <div>Last reconciled balance: {{ selected.last_reconciled_balance ?? '—' }}</div>
      </div>

      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label class="label">Counted balance ({{ currency }})</label>
          <input v-model="form.counted_balance" type="number" step="0.01" min="0" class="input input-bordered w-full" />
          <p v-if="form.errors.counted_balance" class="text-error text-sm">{{ form.errors.counted_balance }}</p>
        </div>
        <button class="btn btn-primary" :disabled="form.processing">Reconcile</button>
        <Link :href="route('bank.dashboard')" class="btn ml-2">Cancel</Link>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Lint the new pages**

Run: `npx eslint resources/js/Pages/Bank`
Expected: no errors.

- [ ] **Step 6: Smoke-test the new pages render**

Run: `npx vite build`
Expected: build succeeds.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Bank
git commit -m "feat(bank): add Vue pages for bank dashboard, accounts, transfers, reconcile"
```

---

## Task 16: Backend tests (PHPUnit)

**Files:**
- Create: `tests/Feature/Bank/AccountServiceTest.php`
- Create: `tests/Feature/Bank/TransferServiceTest.php`
- Create: `tests/Feature/Bank/ReconciliationServiceTest.php`
- Create: `tests/Feature/Bank/AccountControllerTest.php`
- Create: `tests/Feature/Bank/TransferControllerTest.php`
- Create: `tests/Feature/Bank/ReconciliationControllerTest.php`
- Modify: `tests/Feature/Billing/PaymentServiceTest.php`
- Modify: `tests/Feature/DashboardControllerTest.php`
- Delete: `tests/Feature/Exchange/` (whole directory)

- [ ] **Step 1: Create AccountServiceTest**

Create `tests/Feature/Bank/AccountServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\BankAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BankAccountService
    {
        return $this->app->make(BankAccountService::class);
    }

    public function test_creates_cash_account_with_zero_opening_balance(): void
    {
        $user = User::factory()->create();
        $account = $this->service()->create([
            'name' => 'Petty Cash',
            'type' => BankAccountType::Cash->value,
        ], $user->id);

        $this->assertSame('Petty Cash', $account->name);
        $this->assertSame('0.0000', (string) $account->balance);
        $this->assertSame(0, CashMovement::query()->where('account_id', $account->id)->count());
    }

    public function test_creating_bank_account_without_account_number_throws(): void
    {
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->create([
            'name' => 'Acme',
            'type' => BankAccountType::Bank->value,
        ], $user->id);
    }

    public function test_opening_balance_creates_transfer_pair_against_external(): void
    {
        $user = User::factory()->create();
        $account = $this->service()->create([
            'name' => 'Acme Bank',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'Clinic',
            'account_number' => '123456',
            'opening_balance' => 5000,
        ], $user->id);

        $this->assertSame('5000.0000', (string) $account->balance);

        $movements = CashMovement::query()->where('account_id', $account->id)->get();
        $this->assertCount(1, $movements);
        $this->assertSame(CashMovementType::TransferIn, $movements->first()->type);
        $this->assertSame('5000.0000', (string) $movements->first()->amount);

        $external = BankAccount::query()->where('name', 'External Adjustment')->firstOrFail();
        $this->assertSame('-5000.0000', (string) $external->fresh()->balance);
    }

    public function test_unique_account_number_per_bank(): void
    {
        $user = User::factory()->create();
        $this->service()->create([
            'name' => 'Acme A',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'A',
            'account_number' => '111',
        ], $user->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->service()->create([
            'name' => 'Acme B',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'B',
            'account_number' => '111',
        ], $user->id);
    }

    public function test_system_account_cannot_be_modified(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service()->update(
            BankAccount::query()->where('name', 'Main Cash')->firstOrFail(),
            ['name' => 'Renamed'],
        );
    }
}
```

- [ ] **Step 2: Create TransferServiceTest**

Create `tests/Feature/Bank/TransferServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private function setupAccounts(int $cashBalance = 1000, int $bankBalance = 0): array
    {
        $user = User::factory()->create();

        $service = $this->app->make(\App\Services\BankAccountService::class);

        $cash = $service->create(['name' => 'Cash', 'type' => BankAccountType::Cash->value], $user->id);
        $bank = $service->create([
            'name' => 'Acme',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'Clinic',
            'account_number' => '123',
            'opening_balance' => $bankBalance,
        ], $user->id);

        if ($cashBalance > 0) {
            $this->app->make(TransferService::class)->execute(
                BankAccount::query()->where('name', 'External Adjustment')->first()->id,
                $cash->id,
                (string) $cashBalance,
                null,
                'Test setup',
                null,
                $user->id,
            );
        }

        return [$user, $cash, $bank];
    }

    public function test_cash_to_bank_transfer(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(1000, 0);

        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '300',
            null,
            null,
            null,
            $user->id,
        );

        $this->assertSame('700.0000', (string) $cash->fresh()->balance);
        $this->assertSame('300.0000', (string) $bank->fresh()->balance);

        $movements = CashMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame(CashMovementType::TransferOut, $movements[0]->type);
        $this->assertSame(CashMovementType::TransferIn, $movements[1]->type);
        $this->assertSame($movements[0]->transfer_group_id, $movements[1]->transfer_group_id);
    }

    public function test_insufficient_balance_throws(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '500',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_same_source_and_destination_rejected(): void
    {
        [$user, $cash] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $cash->id,
            '10',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_amount_must_be_positive(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '0',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_balances_rollback_on_failure(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        try {
            $this->app->make(TransferService::class)->execute(
                $cash->id,
                $bank->id,
                '500',
                null,
                null,
                null,
                $user->id,
            );
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame('100.0000', (string) $cash->fresh()->balance);
        $this->assertSame('0.0000', (string) $bank->fresh()->balance);
    }
}
```

- [ ] **Step 3: Create ReconciliationServiceTest**

Create `tests/Feature/Bank/ReconciliationServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\BankAccountService;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(int $opening): BankAccount
    {
        $user = User::factory()->create();
        return $this->app->make(BankAccountService::class)->create([
            'name' => 'Cash',
            'type' => BankAccountType::Cash->value,
            'opening_balance' => $opening,
        ], $user->id);
    }

    public function test_matching_count_stamps_timestamp(): void
    {
        $account = $this->makeAccount(500);
        $this->app->make(ReconciliationService::class)->run($account->id, '500', User::factory()->create()->id);

        $fresh = $account->fresh();
        $this->assertNotNull($fresh->last_reconciled_at);
        $this->assertSame('500.0000', (string) $fresh->last_reconciled_balance);
    }

    public function test_positive_variance_creates_transfer_to_account(): void
    {
        $account = $this->makeAccount(500);
        $movementsBefore = CashMovement::query()->count();

        $this->app->make(ReconciliationService::class)->run($account->id, '550', User::factory()->create()->id);

        $this->assertSame('550.0000', (string) $account->fresh()->balance);
        // 2 new movements: transfer_out (external), transfer_in (account)
        $this->assertSame($movementsBefore + 2, CashMovement::query()->count());
    }

    public function test_negative_variance_creates_transfer_from_account(): void
    {
        $account = $this->makeAccount(500);
        $this->app->make(ReconciliationService::class)->run($account->id, '450', User::factory()->create()->id);

        $this->assertSame('450.0000', (string) $account->fresh()->balance);
    }
}
```

- [ ] **Step 4: Create AccountControllerTest**

Create `tests/Feature/Bank/AccountControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['view-accounts', 'manage-accounts'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function test_index_lists_accounts(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view-accounts');
        BankAccount::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('bank.accounts.index'))
            ->assertOk();
    }

    public function test_store_persists_a_bank_account(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-accounts', 'manage-accounts']);

        $this->actingAs($user)->post(route('bank.accounts.store'), [
            'name' => 'Test Bank',
            'type' => 'bank',
            'bank_name' => 'Test',
            'account_name' => 'Clinic',
            'account_number' => '999',
            'opening_balance' => 100,
        ])->assertRedirect(route('bank.accounts.index'));

        $this->assertDatabaseHas('bank_accounts', ['name' => 'Test Bank', 'balance' => '100.0000']);
    }
}
```

- [ ] **Step 5: Create TransferControllerTest**

Create `tests/Feature/Bank/TransferControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['view-transfers', 'record-transfers'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function test_store_creates_transfer(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-transfers', 'record-transfers']);

        $from = BankAccount::factory()->cash()->create(['balance' => 500]);
        $to = BankAccount::factory()->create();

        $this->actingAs($user)->post(route('bank.transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 200,
        ])->assertRedirect(route('bank.transfers.index'));

        $this->assertSame('300.0000', (string) $from->fresh()->balance);
        $this->assertSame('200.0000', (string) $to->fresh()->balance);
    }

    public function test_store_rejects_same_source_and_destination(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-transfers', 'record-transfers']);
        $acc = BankAccount::factory()->cash()->create();

        $this->actingAs($user)->post(route('bank.transfers.store'), [
            'from_account_id' => $acc->id,
            'to_account_id' => $acc->id,
            'amount' => 10,
        ])->assertSessionHasErrors('to_account_id');
    }
}
```

- [ ] **Step 6: Create ReconciliationControllerTest**

Create `tests/Feature/Bank/ReconciliationControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReconciliationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('reconcile-accounts', 'web');
    }

    public function test_run_endpoint_reconciles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reconcile-accounts');
        $account = BankAccount::factory()->cash()->create(['balance' => 1000]);

        $this->actingAs($user)->post(route('bank.reconcile.run'), [
            'account_id' => $account->id,
            'counted_balance' => 1000,
        ])->assertRedirect(route('bank.reconcile.index'));

        $this->assertNotNull($account->fresh()->last_reconciled_at);
    }
}
```

- [ ] **Step 7: Update PaymentServiceTest**

Read `tests/Feature/Billing/PaymentServiceTest.php` and find every assertion that mentions a `cash_movement` row with `currency_code`. Replace with assertions against `account_id` and the new `CashMovementType::Sale`. The full replacement shape (apply to the existing test file, adapting to its style):

```php
// In the existing cash-payment test, replace the cash_movement assertion with:
$this->assertDatabaseHas('cash_movements', [
    'account_id' => $mainCash->id,
    'type' => \App\Enums\CashMovementType::Sale->value,
    'amount' => '500.0000',
    'reference_type' => 'payment',
    'reference_id' => $payment->id,
]);
```

Where `$mainCash = BankAccount::where('name', 'Main Cash')->firstOrFail();` is added at the top of the test.

- [ ] **Step 8: Update DashboardControllerTest**

Read `tests/Feature/DashboardControllerTest.php`. Find any assertion that checks the `cash_balance` stat — replace with:

```php
// assert cash_balance equals sum of bank_accounts.balance where type in {cash, bank}
$expected = (float) BankAccount::query()
    ->whereIn('type', ['cash', 'bank'])
    ->where('is_active', true)
    ->sum('balance');
$this->assertEqualsWithDelta($expected, $stats['cash_balance'], 0.01);
```

- [ ] **Step 9: Delete legacy tests directory**

Run: `rm -r tests/Feature/Exchange`

- [ ] **Step 10: Run the new test suite**

Run: `./vendor/bin/phpunit tests/Feature/Bank tests/Feature/Billing/PaymentServiceTest.php tests/Feature/DashboardControllerTest.php`
Expected: all green. (Fix any leftover references to the deleted classes if the suite reports them.)

- [ ] **Step 11: Commit**

```bash
git add tests/
git commit -m "test(bank): add bank feature tests; update payment + dashboard tests"
```

---

## Task 17: Rewire PaymentService + DashboardController + billing defaults

**Files:**
- Modify: `app/Services/PaymentService.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `app/Http/Controllers/Billing/PaymentController.php`
- Modify: `app/Http/Controllers/Billing/ExpenseController.php`

- [ ] **Step 1: Rewire PaymentService cash branch**

Open `app/Services/PaymentService.php`. Find the cash branch (it currently calls `CashDrawerService::cashIn`). Replace the call with the new `BankAccountService::recordMovement`:

```php
if ($payment->method === PaymentMethod::Cash) {
    $this->bankAccountService->recordMovement(
        $this->bankAccountService->findMainCash(),
        \App\Enums\CashMovementType::Sale,
        (string) $payment->amount,
        'Cash payment for invoice '.$payment->invoice->invoice_number,
        $payment,
        $userId,
    );
}
```

Add `BankAccountService` to the constructor (inject it). The `findMainCash()` helper throws a clear error if the seeder wasn't run.

- [ ] **Step 2: Update DashboardController cash_balance stat**

Open `app/Http/Controllers/DashboardController.php`. Find the `cash_balance` calculation (it queries `cash_drawers` * latest rate). Replace with:

```php
$cashBalance = (float) \App\Models\BankAccount::query()
    ->where('is_active', true)
    ->whereIn('type', [\App\Enums\BankAccountType::Cash->value, \App\Enums\BankAccountType::Bank->value])
    ->sum('balance');
```

Drop any helper that no longer exists (e.g. the `currencyConversionService` if it was used here).

- [ ] **Step 3: Default currency_code/exchange_rate in PaymentController and ExpenseController**

In `app/Http/Controllers/Billing/PaymentController.php` and `app/Http/Controllers/Billing/ExpenseController.php`, find the place where the model is created with `$request->validated()`. After that, force:

```php
$data['currency_code'] = $data['currency_code'] ?? (string) settings('clinic.currency_code', 'BDT');
$data['exchange_rate'] = $data['exchange_rate'] ?? '1.0';
```

(Keep these keys optional in the FormRequest — see Step 4.)

- [ ] **Step 4: Make currency_code/exchange_rate optional in the billing form requests**

Open the form requests used by these controllers (`StorePaymentRequest`, `UpdatePaymentRequest`, `StoreExpenseRequest`, `UpdateExpenseRequest`). Find `currency_code` and `exchange_rate` validation rules and prepend `sometimes`:

```php
'currency_code' => ['sometimes', 'string', 'size:3'],
'exchange_rate' => ['sometimes', 'numeric', 'min:0'],
```

(The defaults in the controller handle missing values.)

- [ ] **Step 5: Run the affected test suites**

Run: `./vendor/bin/phpunit tests/Feature/Billing tests/Feature/DashboardControllerTest.php`
Expected: all green.

- [ ] **Step 6: Commit**

```bash
git add app/Services/PaymentService.php app/Http/Controllers/DashboardController.php app/Http/Controllers/Billing/ resources/views/
git commit -m "feat(bank): rewire PaymentService and DashboardController; default currency_code"
```

(Adjust the git add path — there's no `resources/views`; just add the modified files individually.)

---

## Task 18: Frontend smoke tests

**Files:**
- Create: `tests/Js/Pages/Bank/CreateAccount.spec.js`
- Create: `tests/Js/Pages/Bank/CreateTransfer.spec.js`
- Modify: `resources/js/__tests__/Composables/useNav.test.js` (if it exists, extend it for the new permissions)

- [ ] **Step 1: Add the CreateAccount smoke test**

Create `tests/Js/Pages/Bank/CreateAccount.spec.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Create from '@/Pages/Bank/Accounts/Create.vue';

vi.mock('@inertiajs/vue3', () => ({
  Head: { name: 'Head', template: '<div />' },
  Link: { name: 'Link', template: '<a><slot /></a>' },
  useForm: () => ({
    name: '',
    type: 'bank',
    bank_name: '',
    account_name: '',
    branch: '',
    account_number: '',
    opening_balance: 0,
    is_active: true,
    errors: {},
    processing: false,
    post: vi.fn(),
  }),
}));

vi.mock('@/Layouts/AuthenticatedLayout.vue', () => ({
  default: { name: 'AuthenticatedLayout', template: '<div><slot /></div>' },
}));

describe('Bank/Accounts/Create', () => {
  it('renders the create form', () => {
    const wrapper = mount(Create, { props: { currency: 'BDT' } });
    expect(wrapper.text()).toContain('New account');
  });
});
```

- [ ] **Step 2: Add the CreateTransfer smoke test**

Create `tests/Js/Pages/Bank/CreateTransfer.spec.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Create from '@/Pages/Bank/Transfers/Create.vue';

vi.mock('@inertiajs/vue3', () => ({
  Head: { name: 'Head', template: '<div />' },
  Link: { name: 'Link', template: '<a><slot /></a>' },
  useForm: () => ({
    from_account_id: 1,
    to_account_id: '',
    amount: '',
    occurred_at: '2026-08-15T10:00',
    reference: '',
    note: '',
    errors: {},
    processing: false,
    post: vi.fn(),
  }),
}));

vi.mock('@/Layouts/AuthenticatedLayout.vue', () => ({
  default: { name: 'AuthenticatedLayout', template: '<div><slot /></div>' },
}));

describe('Bank/Transfers/Create', () => {
  it('renders the create form', () => {
    const wrapper = mount(Create, {
      props: { accounts: [{ id: 1, name: 'Cash', type: 'cash', balance: '100' }], currency: 'BDT' },
    });
    expect(wrapper.text()).toContain('New transfer');
  });
});
```

- [ ] **Step 3: Run vitest**

Run: `npm test -- --run`
Expected: existing + new tests pass. (If existing useNav tests assert on the Exchange group, update them to look for the Bank & Cash group instead.)

- [ ] **Step 4: Commit**

```bash
git add tests/Js/Pages/Bank resources/js/__tests__/
git commit -m "test(bank): add frontend smoke tests for new bank pages"
```

---

## Task 19: Quality gates + final verification

**Files:** (none — this task runs the gates end-to-end)

- [ ] **Step 1: Pint**

Run: `./vendor/bin/pint --test`
Expected: 0 errors (run `./vendor/bin/pint` to autofix if needed).

- [ ] **Step 2: PHPStan**

Run: `./vendor/bin/phpstan analyse --memory-limit=2G --no-progress`
Expected: 0 errors at level 6.

- [ ] **Step 3: PHPUnit**

Run: `./vendor/bin/phpunit`
Expected: full suite passes.

- [ ] **Step 4: Frontend lint**

Run: `npm run lint`
Expected: 0 errors.

- [ ] **Step 5: Vitest**

Run: `npm test -- --run`
Expected: 0 failures.

- [ ] **Step 6: Vite production build**

Run: `npx vite build`
Expected: build succeeds.

- [ ] **Step 7: Manual smoke run**

Run: `php artisan serve` in one terminal, then in another:

- `php artisan db:seed --class=SystemAccountsSeeder --force` to ensure system accounts exist.
- Browse to `/bank` (login as admin user). Verify Dashboard renders.
- Click "New account", create a bank account, verify the new row appears.
- Click "New transfer", move 200 from Cash to the new bank account, verify balances and the linked movements.
- Click "Reconcile", enter a counted balance that matches, verify the timestamp is stamped.

- [ ] **Step 8: Commit any final fixes**

```bash
git add -A
git commit -m "chore(bank): quality-gate fixes"
```

(only if there are uncommitted changes from steps 1–7)

---

## Self-review

**1. Spec coverage**
- §2 data model → Tasks 1, 5, 6, 7.
- §2.4 dropped tables → Tasks 1, 8, 14.
- §3 enums → Tasks 3, 4.
- §3.2 repositories → Task 8.
- §3.3 services → Task 9.
- §3.4 controllers + routes → Tasks 11, 12, 13.
- §3.5 FormRequests → Task 11.
- §3.6 permissions → Task 13.
- §3.7 seeders → Task 2.
- §3.8 transfer data flow → Task 9 (TransferService) + Task 16 (tests).
- §3.9 error handling → covered in services (RuntimeException) + controllers (withErrors).
- §4 UI pages → Task 15.
- §5 tests → Tasks 16, 18.
- §6.4 integration with PaymentService + DashboardController + billing → Task 17.
- §6.5 reconciliation semantics → Task 9 (ReconciliationService) + Task 16 (ReconciliationServiceTest).
- §7 deviations → documented in spec; Tasks 8 (BankAccount rename) and 9 (External Adjustment reconciliation) follow the deviations as designed.

**2. Placeholder scan** — no `TBD`, `TODO`, or vague "add appropriate" wording in the plan; every step shows code or commands.

**3. Type consistency**
- `BankAccount::factory()->bank() / cash() / external()` used in tests and seeders — defined in Task 5.
- `TransferService::execute(fromId, toId, amount, occurredAt, reference, note, userId)` — same signature used in ReconciliationService, TransferController, and tests.
- `CashMovementType::{CashIn, CashOut, TransferIn, TransferOut, Adjustment, Sale, Expense, Refund}` — defined in Task 4, used in services/tests throughout.
- `BankAccountType::{Cash, Bank, External}` — defined in Task 3, used in FormRequests/services/tests.
- `BankAccountService::{create, update, recordMovement, findMainCash}` — defined in Task 9, called by PaymentService (Task 17) and controllers.
- `ReconciliationService::{snapshot, run}` — defined in Task 9, called by the controller in Task 12 and tests in Task 16.
- `TransferRepository::{paginate, findByIdOrFail, create}` — defined in Task 8, used in services and controllers.

All consistent.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-15-bank-and-cash-plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

The user requested autonomous completion, so I will proceed with **Subagent-Driven** execution via `superpowers:subagent-driven-development`. Reporting back as tasks complete.
