<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UomController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CanvasController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SubUnitController;
use App\Http\Controllers\Api\ApproverController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\JobOrderController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\AccountTypeController;
use App\Http\Controllers\Api\PoApproversController;
use App\Http\Controllers\Api\AccountGroupController;
use App\Http\Controllers\Api\AccountTitleController;
use App\Http\Controllers\Api\NormalBalanceController;
use App\Http\Controllers\Api\PRTransactionController;
use App\Http\Controllers\Api\DepartmentUnitController;
use App\Http\Controllers\Api\AccountSubGroupController;
use App\Http\Controllers\Api\AccountTitleUnitController;
use App\Http\Controllers\Api\ApproverSettingsController;
use App\Http\Controllers\Api\JobOrderTransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::post("logout", [UserController::class, "logout"]);
    Route::patch("reset_password", [UserController::class, "resetPassword"]);
    Route::patch("change_password", [UserController::class, "changePassword"]);
    Route::patch("users/archived/{id}", [UserController::class, "destroy"]);
    Route::apiResource("users", UserController::class);

    Route::patch("roles/archived/{id}", [RoleController::class, "destroy"]);
    Route::apiResource("roles", RoleController::class);

    Route::patch("companies/archived/{id}", [
        CompanyController::class,
        "destroy",
    ]);
    Route::apiResource("companies", CompanyController::class);

    Route::patch("business-units/archived/{id}", [
        BusinessController::class,
        "destroy",
    ]);
    Route::apiResource("business-units", BusinessController::class);

    Route::patch("departments/archived/{id}", [
        DepartmentController::class,
        "destroy",
    ]);
    Route::apiResource("departments", DepartmentController::class);

    Route::patch("sub_units/archived/{id}", [
        SubUnitController::class,
        "destroy",
    ]);
    Route::apiResource("sub_units", SubUnitController::class);

    Route::patch("locations/archived/{id}", [
        LocationController::class,
        "destroy",
    ]);
    Route::apiResource("locations", LocationController::class);

    Route::patch("warehouses/archived/{id}", [
        WarehouseController::class,
        "destroy",
    ]);
    Route::apiResource("warehouses", WarehouseController::class);

    Route::patch("account_type/archived/{id}", [
        AccountTypeController::class,
        "destroy",
    ]);
    Route::apiResource("account_type", AccountTypeController::class);

    Route::patch("account_group/archived/{id}", [
        AccountGroupController::class,
        "destroy",
    ]);
    Route::apiResource("account_group", AccountGroupController::class);

    Route::patch("account_sub_group/archived/{id}", [
        AccountSubGroupController::class,
        "destroy",
    ]);
    Route::apiResource("account_sub_group", AccountSubGroupController::class);

    Route::patch("financial_statement/archived/{id}", [
        FinancialController::class,
        "destroy",
    ]);
    Route::apiResource("financial_statement", FinancialController::class);

    Route::patch("normal_balance/archived/{id}", [
        NormalBalanceController::class,
        "destroy",
    ]);
    Route::apiResource("normal_balance", NormalBalanceController::class);

    Route::patch("account_title_units/archived/{id}", [
        AccountTitleUnitController::class,
        "destroy",
    ]);
    Route::apiResource(
        "account_title_units",
        AccountTitleUnitController::class
    );

    Route::patch("types/archived/{id}", [TypeController::class, "destroy"]);
    Route::apiResource("types", TypeController::class);

    Route::patch("uoms/archived/{id}", [UomController::class, "destroy"]);
    Route::apiResource("uoms", UomController::class);

    Route::patch("suppliers/archived/{id}", [
        SupplierController::class,
        "destroy",
    ]);
    Route::apiResource("suppliers", SupplierController::class);

    Route::patch("units/archived/{id}", [UnitController::class, "destroy"]);
    Route::apiResource("units", UnitController::class);

    Route::patch("items/archived/{id}", [ItemController::class, "destroy"]);
    Route::apiResource("items", ItemController::class);

    Route::patch("units_department/archived/{id}", [
        DepartmentUnitController::class,
        "destroy",
    ]);
    Route::apiResource("units_department", DepartmentUnitController::class);

    Route::patch("account_titles/archived/{id}", [
        AccountTitleController::class,
        "destroy",
    ]);
    Route::apiResource("account_titles", AccountTitleController::class);

    Route::patch("pr_transaction/archived/{id}", [
        PRTransactionController::class,
        "destroy",
    ]);

    Route::apiResource("pr_transaction", PRTransactionController::class);

    Route::patch("approvers_settings/archived/{id}", [
        ApproverSettingsController::class,
        "destroy",
    ]);

    Route::apiResource("approvers_settings", ApproverSettingsController::class);

    Route::patch("approved/{id}", [ApproverController::class, "approved"]);
    Route::get("job_approver", [ApproverController::class, "job_order"]);
    Route::apiResource("approver_dashboard", ApproverController::class);
    Route::patch("cancelled/{id}", [ApproverController::class, "cancelled"]);
    Route::patch("void/{id}", [ApproverController::class, "voided"]);
    Route::patch("rejected/{id}", [ApproverController::class, "rejected"]);

    Route::patch("job_order/archived/{id}", [
        JobOrderController::class,
        "destroy",
    ]);

    Route::apiResource("job_order", JobOrderController::class);

    Route::patch("po_approver/archived/{id}", [
        PoApproversController::class,
        "destroy",
    ]);

    Route::apiResource("po_approver", PoApproversController::class);

    Route::apiResource("canvas_approver", CanvasController::class);

    Route::patch("expense/archived/{id}", [
        ExpenseController::class,
        "destroy",
    ]);

    Route::apiResource("expense", ExpenseController::class);

    Route::apiResource(
        "job_order_transaction",
        JobOrderTransactionController::class
    );
});
Route::post("login", [UserController::class, "login"]);
