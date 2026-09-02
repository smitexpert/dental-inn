<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BankAccountRepository;
use App\Settings\ClinicSettings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly BankAccountRepository $accounts) {}

    public function index(): Response
    {
        $accounts = $this->accounts->paginate(50);

        $currency = app(ClinicSettings::class)->currency_code ?? 'BDT';

        return Inertia::render('Bank/Dashboard', [
            'accounts' => $accounts,
            'currency' => $currency,
        ]);
    }
}
