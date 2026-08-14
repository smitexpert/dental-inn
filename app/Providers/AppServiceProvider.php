<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\Contracts\AccountRepository;
use App\Repositories\Contracts\AppointmentRepository;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\CurrencyRepository;
use App\Repositories\Contracts\DoctorRepository;
use App\Repositories\Contracts\DoctorScheduleRepository;
use App\Repositories\Contracts\DoctorTimeOffRepository;
use App\Repositories\Contracts\ExchangeRateRepository;
use App\Repositories\Contracts\ExpenseCategoryRepository;
use App\Repositories\Contracts\ExpenseRepository;
use App\Repositories\Contracts\InvoiceRepository;
use App\Repositories\Contracts\PatientRepository;
use App\Repositories\Contracts\PaymentRepository;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\StockLocationRepository;
use App\Repositories\Contracts\StockMovementRepository;
use App\Repositories\Contracts\SupplierRepository;
use App\Repositories\Contracts\TreatmentRecordRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\AccountEloquentRepository;
use App\Repositories\Eloquent\AppointmentEloquentRepository;
use App\Repositories\Eloquent\CashDrawerEloquentRepository;
use App\Repositories\Eloquent\CashMovementEloquentRepository;
use App\Repositories\Eloquent\CurrencyEloquentRepository;
use App\Repositories\Eloquent\DoctorEloquentRepository;
use App\Repositories\Eloquent\DoctorScheduleEloquentRepository;
use App\Repositories\Eloquent\DoctorTimeOffEloquentRepository;
use App\Repositories\Eloquent\ExchangeRateEloquentRepository;
use App\Repositories\Eloquent\ExpenseCategoryEloquentRepository;
use App\Repositories\Eloquent\ExpenseEloquentRepository;
use App\Repositories\Eloquent\InvoiceEloquentRepository;
use App\Repositories\Eloquent\PatientEloquentRepository;
use App\Repositories\Eloquent\PaymentEloquentRepository;
use App\Repositories\Eloquent\ProductEloquentRepository;
use App\Repositories\Eloquent\StockLocationEloquentRepository;
use App\Repositories\Eloquent\StockMovementEloquentRepository;
use App\Repositories\Eloquent\SupplierEloquentRepository;
use App\Repositories\Eloquent\TreatmentRecordEloquentRepository;
use App\Repositories\Eloquent\UserEloquentRepository;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            UserEloquentRepository::class,
        );

        $this->app->bind(
            DoctorRepository::class,
            DoctorEloquentRepository::class,
        );

        $this->app->bind(
            DoctorScheduleRepository::class,
            DoctorScheduleEloquentRepository::class,
        );

        $this->app->bind(
            DoctorTimeOffRepository::class,
            DoctorTimeOffEloquentRepository::class,
        );

        $this->app->bind(
            AppointmentRepository::class,
            AppointmentEloquentRepository::class,
        );

        $this->app->bind(
            PatientRepository::class,
            PatientEloquentRepository::class,
        );

        $this->app->bind(
            TreatmentRecordRepository::class,
            TreatmentRecordEloquentRepository::class,
        );

        $this->app->bind(
            ProductRepository::class,
            ProductEloquentRepository::class,
        );

        $this->app->bind(
            SupplierRepository::class,
            SupplierEloquentRepository::class,
        );

        $this->app->bind(
            StockLocationRepository::class,
            StockLocationEloquentRepository::class,
        );

        $this->app->bind(
            StockMovementRepository::class,
            StockMovementEloquentRepository::class,
        );

        $this->app->bind(
            AccountRepository::class,
            AccountEloquentRepository::class,
        );

        $this->app->bind(
            InvoiceRepository::class,
            InvoiceEloquentRepository::class,
        );

        $this->app->bind(
            PaymentRepository::class,
            PaymentEloquentRepository::class,
        );

        $this->app->bind(
            ExpenseCategoryRepository::class,
            ExpenseCategoryEloquentRepository::class,
        );

        $this->app->bind(
            ExpenseRepository::class,
            ExpenseEloquentRepository::class,
        );

        $this->app->bind(
            CurrencyRepository::class,
            CurrencyEloquentRepository::class,
        );

        $this->app->bind(
            ExchangeRateRepository::class,
            ExchangeRateEloquentRepository::class,
        );

        $this->app->bind(
            CashDrawerRepository::class,
            CashDrawerEloquentRepository::class,
        );

        $this->app->bind(
            CashMovementRepository::class,
            CashMovementEloquentRepository::class,
        );

        // Services are singletons because they hold no per-request state
        // and depend only on the bound repositories + other services.
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(ExpenseService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'expense' => Expense::class,
            'user' => User::class,
            'patient' => Patient::class,
            'doctor' => Doctor::class,
            'product' => Product::class,
            'supplier' => Supplier::class,
        ]);

        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->user() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'avatar_url' => auth()->user()->getFirstMediaUrl('avatar'),
                    'roles' => auth()->user()->getRoleNames(),
                    'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
            ],
        ]);
    }
}
