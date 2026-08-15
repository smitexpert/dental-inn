<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\NotificationDriver;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentCreated;
use App\Events\InvoiceIssued;
use App\Events\PaymentReceived;
use App\Listeners\SendAppointmentCancelledNotification;
use App\Listeners\SendAppointmentCreatedNotification;
use App\Listeners\SendInvoiceIssuedNotification;
use App\Listeners\SendPaymentReceivedNotification;
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
use App\Repositories\Eloquent\DoctorTimeOffEloquentRepository;
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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
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
            CashMovementRepository::class,
            CashMovementEloquentRepository::class,
        );

        $this->app->bind(BankAccountRepository::class, BankAccountEloquentRepository::class);
        $this->app->bind(TransferRepository::class, TransferEloquentRepository::class);

        // Services are singletons because they hold no per-request state
        // and depend only on the bound repositories + other services.
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(ExpenseService::class);
        $this->app->singleton(BankAccountService::class);
        $this->app->singleton(TransferService::class);
        $this->app->singleton(ReconciliationService::class);
        $this->app->singleton(NotificationService::class);

        // Bind the default notification driver as a singleton based on
        // config('notifications.default_driver'). The driver class and
        // its config come from config('notifications.drivers.<name>').
        $this->app->singleton(NotificationDriver::class, function ($app): NotificationDriver {
            $default = config('notifications.default_driver', 'log');
            $entry = config("notifications.drivers.{$default}");

            if (! is_array($entry) || ! isset($entry['class'])) {
                throw new \RuntimeException(
                    "Notification driver '{$default}' is not configured."
                );
            }

            $class = $entry['class'];
            $config = is_array($entry['config'] ?? null) ? $entry['config'] : [];

            return $app->make($class, ['config' => $config]);
        });
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

        Event::listen(AppointmentCreated::class, SendAppointmentCreatedNotification::class);
        Event::listen(AppointmentCancelled::class, SendAppointmentCancelledNotification::class);
        Event::listen(InvoiceIssued::class, SendInvoiceIssuedNotification::class);
        Event::listen(PaymentReceived::class, SendPaymentReceivedNotification::class);

        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->user() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'avatar_url' => auth()->user()->getFirstMediaUrl('avatar'),
                    'roles' => auth()->user()->getRoleNames()->values()->all(),
                    'permissions' => auth()->user()->getAllPermissions()->pluck('name')->values()->all(),
                ] : null,
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                // Forward pass-through flash keys that dialogs and
                // other front-end consumers may rely on (e.g. the
                // newly-created patient returned by PatientController
                // @ quickStore, read at `page.props.flash.patient`).
                'patient' => session('patient'),
            ],
        ]);
    }
}
