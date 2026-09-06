<?php

use App\Modules\Finance\Http\Controllers\FinanceExpenseController;
use App\Modules\Finance\Http\Controllers\FinanceExportController;
use App\Modules\Finance\Http\Controllers\FinanceIncomeController;
use App\Modules\Finance\Http\Controllers\FinanceLoanController;
use App\Modules\Finance\Http\Controllers\MyFinanceController;
use Illuminate\Support\Facades\Route;

Route::get('finance', MyFinanceController::class)->name('finance.index');

Route::get('finance/income', [FinanceIncomeController::class, 'index'])->name('finance.income.index');
Route::post('finance/income', [FinanceIncomeController::class, 'store'])->name('finance.income.store');
Route::put('finance/income/{income}', [FinanceIncomeController::class, 'update'])->name('finance.income.update');
Route::delete('finance/income/{income}', [FinanceIncomeController::class, 'destroy'])->name('finance.income.destroy');

Route::get('finance/expenses', [FinanceExpenseController::class, 'index'])->name('finance.expenses.index');
Route::post('finance/expenses', [FinanceExpenseController::class, 'store'])->name('finance.expenses.store');
Route::put('finance/expenses/{expense}', [FinanceExpenseController::class, 'update'])->name('finance.expenses.update');
Route::delete('finance/expenses/{expense}', [FinanceExpenseController::class, 'destroy'])->name('finance.expenses.destroy');

Route::get('finance/loans', [FinanceLoanController::class, 'index'])->name('finance.loans.index');
Route::post('finance/loans', [FinanceLoanController::class, 'store'])->name('finance.loans.store');
Route::put('finance/loans/{loan}', [FinanceLoanController::class, 'update'])->name('finance.loans.update');
Route::delete('finance/loans/{loan}', [FinanceLoanController::class, 'destroy'])->name('finance.loans.destroy');
Route::post('finance/loans/{loan}/payments', [FinanceLoanController::class, 'recordPayment'])->name('finance.loans.payments.store');

Route::get('finance/export/income', [FinanceExportController::class, 'income'])->name('finance.export.income');
Route::get('finance/export/expenses', [FinanceExportController::class, 'expenses'])->name('finance.export.expenses');
Route::get('finance/export/loans', [FinanceExportController::class, 'loans'])->name('finance.export.loans');
