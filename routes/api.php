<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\OnlineCustomer\OnlineCustomerController;
use App\Http\Controllers\Api\Dashboard\KitchenController;
use App\Http\Controllers\Api\Dashboard\OrderHistoryController;
use App\Http\Controllers\Api\Dashboard\DeliveryUserOrderController;
use App\Http\Controllers\Api\Dashboard\WorkPeriodController;
use App\Http\Controllers\Api\Food\FoodGroupController;
use App\Http\Controllers\Api\Food\FoodItemController;
use App\Http\Controllers\Api\Food\FoodUnitController;
use App\Http\Controllers\Api\Food\PropertyGroupController;
use App\Http\Controllers\Api\Food\PropertyItemController;
use App\Http\Controllers\Api\Food\VariationController;
use App\Http\Controllers\Api\InstallerController;
use App\Http\Controllers\Api\Pos\OrderController;
use App\Http\Controllers\Api\Report\BranchWiseController;
use App\Http\Controllers\Api\Report\DailyController;
use App\Http\Controllers\Api\Report\DashboardController;
use App\Http\Controllers\Api\Report\DepartmentWiseController;
use App\Http\Controllers\Api\Report\DiscountWiseController;
use App\Http\Controllers\Api\Report\GroupWiseController;
use App\Http\Controllers\Api\Report\ItemWiseController;
use App\Http\Controllers\Api\Report\MonthlyController;
use App\Http\Controllers\Api\Report\ServiceChargeWiseController;
use App\Http\Controllers\Api\Report\UserWiseController;
use App\Http\Controllers\Api\RestaurantDetails\BranchController;
use App\Http\Controllers\Api\RestaurantDetails\DeptTagController;
use App\Http\Controllers\Api\RestaurantDetails\PaymentTypeController;
use App\Http\Controllers\Api\RestaurantDetails\TableController;
use App\Http\Controllers\Api\Settings\CurrencyController;
use App\Http\Controllers\Api\Settings\LanguageController;
use App\Http\Controllers\Api\Settings\PermissionController;
use App\Http\Controllers\Api\Settings\SettingsController;
use App\Http\Controllers\Api\Users\AdminStaffController;
use App\Http\Controllers\Api\Users\CustomerController;
use App\Http\Controllers\Api\Users\DeliveryController;
use App\Http\Controllers\Api\Users\WaiterController;
use App\Http\Controllers\Api\Website\WebsiteController;
use App\Http\Controllers\Api\Stock\SupplierController;
use App\Http\Controllers\Api\Stock\FoodStockController;
use App\Http\Controllers\Api\Stock\IngredientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/* installation routes*/
    Route::group(['middleware' => 'checkInstall'], function () {
        Route::get('check-permission', [InstallerController::class, 'permission'])->name('permission');
        Route::post('setup/database', [InstallerController::class, 'dbStore'])->name('db.setup');
        Route::get('check-database-connection', [InstallerController::class, 'checkDbConnection'])->name('check.db');
        Route::get('import-fresh-sql', [InstallerController::class, 'sqlUpload'])->name('org.create');
        Route::get('import-demo-sql', [InstallerController::class, 'sqlUploadDemo'])->name('org.create.demo');
        Route::post('setup/admin/store', [InstallerController::class, 'adminStore'])->name('admin.store');
        Route::get('setup/check_ip_domain', [InstallerController::class, 'getServerIpAddress'])->name('check.getServerIpAddress');
    });

//passport auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', [ ApiAuthController::class, 'login' ]); //login route
        Route::post('/register', [ OnlineCustomerController::class, 'store' ]); //online registration route
        Route::post('/be-delivery-man', [ DeliveryController::class, 'storeDeliveryMan' ]); //online registration route
        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('/logout', [ ApiAuthController::class, 'logout' ]); //logout route
            Route::get('/user', [ ApiAuthController::class, 'user' ]); //get auth user
        });
        Route::post('/resetPassword', [ ApiAuthController::class, 'sendEmailToken' ]);
        Route::post('/setNewPassword', [ ApiAuthController::class, 'setNewPassword' ]);
    });

//website Routes
Route::group(['prefix' => 'website'], function () {
    Route::get('/home', [ OnlineCustomerController::class, 'index' ]); //foods route
    //branch
    Route::get('/get-branch-web', [BranchController::class, 'indexWeb']); //get all branch

    Route::group(['middleware' => 'auth:api'], function () {
        //customers
        Route::post('/order', [ OnlineCustomerController::class, 'storeOrder' ]);//online order route
        Route::get('/pending-order', [ OnlineCustomerController::class, 'pending' ]);//online order route
        Route::get('/online-orders', [ OnlineCustomerController::class, 'onlineOrders' ]);//online order route
        Route::get('/online-orders/customer', [ OnlineCustomerController::class, 'onlineOrdersCustomer' ]);//online order route
        Route::post('/update-user-profile', [ OnlineCustomerController::class, 'updateProfile' ]);//online order route
        //admins
        Route::post('/accept-order', [ OnlineCustomerController::class, 'acceptOnlineOrders' ]);//online order route
        Route::post('/cancel-order', [ OnlineCustomerController::class, 'cancelOnlineOrders' ]);//online order route
    });
});

//check install
    Route::get('/check-install', function () {
        try {
            $value = \Config::get('app.install');
            if ($value == "7469a286259799e5b37e5db9296f00b3") {
                return "YES";
            } else {
                return "NO";
            }
        } catch (\Exception $e) {
            return "NO";
        }
    });

//Manage routes
    //unauthenticated
    Route::group(['prefix' => 'settings'], function () {
        //languages
        Route::get('/get-lang', [ LanguageController::class, 'index' ]); //get all languages
        Route::post('/save-to-en', [ LanguageController::class, 'store' ]); //save new key to en

        //currencies
        Route::get('/get-currency', [CurrencyController::class, 'index' ]); //get all currencies

        //general-settings
        Route::get('/general-settings', [SettingsController::class, 'index']); //get general-settings configuration
    });

    //authenticated
    Route::group(['prefix' => 'settings','middleware' => ['auth:api']], function () {
        //without permission middleware
        //auth user profile
        Route::post('/update-profile', [AdminStaffController::class,'updateProfile']); // update auth user profile

        //permission group
        Route::get('/permission-group-list', [ PermissionController::class, 'index' ]); //get all permissions group

        //branch
        Route::get('/get-branch', [BranchController::class, 'index']); //get all branch

        //assign permission middleware
        //Manage Middleware
        Route::group(['middleware' => ['manage']], function () {
            //Settings
            //currencies
            Route::post('/new-currency', [CurrencyController::class, 'store']); //store new currency
            Route::post('/update-currency', [CurrencyController::class, 'update']); //update currency
            Route::post('/update-default-currency', [CurrencyController::class, 'setDefault']); //default currency
            Route::get('/delete-currency/{code}', [CurrencyController::class, 'destroy']); //delete currency

            //languages
            Route::post('/new-lang', [LanguageController::class, 'langStore']); //store new language
            Route::post('/update-lang', [LanguageController::class, 'langUpdate']); //update language
            Route::post('/update-default', [LanguageController::class, 'setDefault']); //default language
            Route::get('/delete-lang/{code}', [LanguageController::class, 'destroy']); //delete language
            //translation
            Route::get('/get-lang/{code}', [LanguageController::class, 'getTranslations']); //get items to translate
            Route::post('/save-translation', [LanguageController::class, 'saveTranslation']); //save translation

            //email-smtp
            Route::get('/get-smtp', [SettingsController::class, 'smtp']); //get smtp configuration
            Route::post('/set-smtp', [SettingsController::class, 'smtpStore']); //store smtp configuration

            //general-settings
            Route::post('/general-settings', [SettingsController::class, 'store']); //store general-settings configuration
            Route::post('/pos-screen', [SettingsController::class, 'posScreen']); //posScreen settings configuration

            //website configuration
            Route::post('/hero-section', [WebsiteController::class, 'store']); //store hero section configuration
            Route::post('/promotion-section', [WebsiteController::class, 'promotionStore']); //store hero section configuration

            //update system
            Route::post('/update-system', [SettingsController::class, 'updateSystem']); //store general-settings configuration
            Route::get('/refresh-system', [SettingsController::class, 'refreshSystem']); //refresh configuration

            //user management
            //permission group
            Route::post('/new-permission-group', [ PermissionController::class, 'store' ]); //store new permission group
            Route::post('/update-permission-group', [ PermissionController::class, 'update' ]); //update permission group
            Route::get('/delete-permission-group/{slug}', [ PermissionController::class, 'destroy' ]); //delete permission group

            //waiter
            Route::post('/new-waiter', [WaiterController::class, 'store']); //store new waiter
            Route::post('/update-waiter', [WaiterController::class, 'update']); //update waiter
            Route::get('/delete-waiter/{slug}', [WaiterController::class, 'destroy']); //delete waiter

           //admin-staff
            Route::post('/new-admin-staff', [AdminStaffController::class, 'store']); //store new admin-staff
            Route::post('/update-admin-staff', [AdminStaffController::class, 'update']); //update admin-staff
            Route::get('/delete-admin-staff/{slug}', [AdminStaffController::class, 'destroy']); //disable-active admin-staff

            //delivery man
             Route::post('/new-delivery-man', [DeliveryController::class, 'store']); //store new delivery man
             Route::post('/update-delivery-man', [DeliveryController::class, 'update']); //update delivery man
             Route::post('/approve-delivery-man', [DeliveryController::class, 'approveDeliveryMan']); //approveDeliveryMan
             Route::get('/delete-delivery-man/{slug}', [DeliveryController::class, 'destroy']); //disable-active delivery man

             //supplier
             Route::get('/get-supplier', [SupplierController::class, 'index']); //get all supplier
             Route::post('/new-supplier', [SupplierController::class, 'store']); //store new supplier
             Route::post('/update-supplier', [SupplierController::class, 'update']); //update supplier
             Route::get('/delete-supplier/{slug}', [SupplierController::class, 'destroy']); //delete supplier
             Route::post('/get-supplier-report', [SupplierController::class, 'supplierLeadger']);//supplierLeadger


             //ingredient_group
             Route::get('/get-ingredient_group', [IngredientController::class, 'indexGroup']); //get all ingredient_group
             Route::post('/new-ingredient_group', [IngredientController::class, 'storeGroup']); //store new ingredient_group
             Route::post('/update-ingredient_group', [IngredientController::class, 'updateGroup']); //update ingredient_group
             Route::get('/delete-ingredient_group/{slug}', [IngredientController::class, 'destroyGroup']); //delete ingredient_group
             //ingredient item
             Route::get('/get-ingredient_item', [IngredientController::class, 'indexItem']); //get all ingredient_item
             Route::post('/new-ingredient_item', [IngredientController::class, 'storeItem']); //store new ingredient_item
             Route::post('/update-ingredient_item', [IngredientController::class, 'updateItem']); //update ingredient_item
             Route::get('/delete-ingredient_item/{slug}', [IngredientController::class, 'destroyItem']); //delete ingredient_item
             //ingredient purchase
             Route::get('/get-ingredient_purchase', [IngredientController::class, 'indexPurchase']); //get all Purchase
             Route::get('/get-ingredient_purchase_items/{id}', [IngredientController::class, 'indexPurchaseItems']); //get all Purchase
             Route::post('/new-ingredient_purchase', [IngredientController::class, 'storePurchase']); //store new Purchase
             Route::post('/update-ingredient_purchase', [IngredientController::class, 'updatePurchase']); //update Purchase
             Route::get('/delete-ingredient_purchase/{slug}', [IngredientController::class, 'destroyPurchase']); //delete Purchase
             //food purchase
             Route::get('/get-food_purchase', [FoodStockController::class, 'indexPurchase']); //get all Purchase
             Route::get('/get-food_purchase_items/{id}', [FoodStockController::class, 'indexPurchaseItems']); //get all Purchase
             Route::post('/new-food_purchase', [FoodStockController::class, 'storePurchase']); //store new Purchase
             Route::post('/update-food_purchase', [FoodStockController::class, 'updatePurchase']); //update Purchase
             Route::get('/delete-food_purchase/{slug}', [FoodStockController::class, 'destroyPurchase']); //delete Purchase


            //restaurant
            //branch
            Route::post('/new-branch', [BranchController::class, 'store']); //store new branch
            Route::post('/update-branch', [BranchController::class, 'update']); //update branch
            Route::get('/delete-branch/{slug}', [BranchController::class, 'destroy']); //delete branch

            //table
            Route::post('/new-table', [TableController::class, 'store']); //store new table
            Route::post('/update-table', [TableController::class, 'update']); //update table
            Route::get('/delete-table/{slug}', [TableController::class, 'destroy']); //delete table

            //dept-tag
            Route::post('/new-dept-tag', [DeptTagController::class, 'store']); //store new dept-tag
            Route::post('/update-dept-tag', [DeptTagController::class, 'update']); //update dept-tag
            Route::get('/delete-dept-tag/{slug}', [DeptTagController::class, 'destroy']); //delete dept-tag

            //payment type
            Route::post('/new-payment-type', [PaymentTypeController::class, 'store']); //store new payment-type
            Route::post('/update-payment-type', [PaymentTypeController::class, 'update']); //update payment-type
            Route::get('/delete-payment-type/{slug}', [PaymentTypeController::class, 'destroy']); //delete payment-type

            //food
            //food item
            Route::post('/new-food-item', [FooditemController::class, 'store']); //store new food-item
            Route::post('/update-food-item', [FooditemController::class, 'update']); //update food-item
            Route::post('/update-food-item-variation', [FooditemController::class, 'updateVariation']); //update food-item-variation
            Route::post('/new-food-item-variation', [FooditemController::class, 'storeVariation']); //store new food-item-variation
            Route::get('/delete-food-item/{slug}', [FooditemController::class, 'destroy']); //delete food-item

            //food group
            Route::post('/new-food-group', [FoodGroupController::class, 'store']); //store new food-group
            Route::post('/update-food-group', [FoodGroupController::class, 'update']); //update food-group
            Route::get('/delete-food-group/{slug}', [FoodGroupController::class, 'destroy']); //delete food-group

            //food unit
            Route::get('/get-food-unit', [FoodUnitController::class, 'index']); //get all food-unit
            Route::post('/new-food-unit', [FoodUnitController::class, 'store']); //store new food-unit
            Route::post('/update-food-unit', [FoodUnitController::class, 'update']); //update food-unit
            Route::get('/delete-food-unit/{slug}', [FoodUnitController::class, 'destroy']); //delete food-unit

            //variation
            Route::post('/new-variation', [VariationController::class, 'store']); //store new variation
            Route::post('/update-variation', [VariationController::class, 'update']); //update variation
            Route::get('/delete-variation/{slug}', [VariationController::class, 'destroy']); //delete variation

            //property-group
            Route::post('/new-property-group', [PropertyGroupController::class, 'store']); //store new property-group
            Route::post('/update-property-group', [PropertyGroupController::class, 'update']); //update property-group
            Route::get('/delete-property-group/{slug}', [PropertyGroupController::class, 'destroy']); //delete property-group

            //property-item
            Route::post('/new-property-item', [PropertyItemController::class, 'store']); //store new property-item
            Route::post('/update-property-item', [PropertyItemController::class, 'update']); //update property-item
            Route::get('/delete-property-item/{slug}', [PropertyItemController::class, 'destroy']); //delete property-item
        });

        //Work period middleware
        Route::group(['middleware' => ['workPeriod']], function () {
            //work period
            Route::post('/new-work-period', [WorkPeriodController::class, 'store']); //store new work-period
            Route::post('/update-work-period', [WorkPeriodController::class, 'update']); //update work-period
            Route::get('/get-closing-items/{id}', [WorkPeriodController::class, 'getStockItems']); //getStockItems
            Route::get('/get-closing-items-food/{id}', [WorkPeriodController::class, 'getStockItemsFood']); //getStockItems
            Route::post('/update-closing-items', [WorkPeriodController::class, 'updateStockItems']); //getStockItems
        });

        //customer middleware, for customer list, pos
        Route::group(['middleware' => ['customer']], function () {
            //admin-staff
            Route::get('/get-admin-staff', [AdminStaffController::class, 'index']); //get all admin-staff
            //delivery man
            Route::get('/get-delivery-man', [DeliveryController::class, 'index']); //get all delivery man

            //work period
            Route::get('/get-work-period', [WorkPeriodController::class, 'index']); //get all work-period

            //customer
            Route::get('/get-customer', [CustomerController::class, 'index']); //get all customer
            Route::post('/new-customer', [CustomerController::class, 'store']); //store new customer
            Route::post('/update-customer', [CustomerController::class, 'update']); //update customer
            Route::get('/delete-customer/{slug}', [CustomerController::class, 'destroy']); //delete customer

            //customer online
            Route::get('/get-website-customer', [CustomerController::class, 'indexOnline']); //get all customer

            //table
            Route::get('/get-table', [TableController::class, 'index']); //get all table

            //waiter
            Route::get('/get-waiter', [WaiterController::class, 'index']); //get all waiter

            //dept-tag
            Route::get('/get-dept-tag', [DeptTagController::class, 'index']); //get all dept-tag

            //payment type
            Route::get('/get-payment-type', [PaymentTypeController::class, 'index']); //get all payment-type

            //food item
            Route::get('/get-food-item', [FooditemController::class, 'index']); //get all food-item

            //food group
            Route::get('/get-food-group', [FoodGroupController::class, 'index']); //get all food-group

            //variation
            Route::get('/get-variation', [VariationController::class, 'index']); //get all variation

            //property-group
            Route::get('/get-property-group', [PropertyGroupController::class, 'index']); //get all property-group

            //property-item
            Route::get('/get-property-item/{slug}', [PropertyItemController::class, 'index']); //get all property-item

            //orders from pos page
            Route::post('/new-order', [OrderController::class, 'submit']); //add items as new order
            Route::post('/settle-order', [OrderController::class, 'settle']); //add items as new settled order
            Route::get('/get-submitted-orders', [OrderController::class, 'getSubmitted']);
            Route::get('/get-settled-orders', [OrderController::class, 'getSettled']);
            Route::post('/settle-submitted-order', [OrderController::class, 'submitToSettle']); //submit to settle
            Route::post('/cancel-submitted-order', [OrderController::class, 'cancelSubmitted']); //cancel submitted
            Route::get('/settle-order-ready/{id}', [OrderController::class, 'settleOrderReady']); //submit to settle


            //report routes goes here in customer middleware, coz to generate reports the data from above routes are needed
            Route::get('/report-dashboard', [DashboardController::class, 'index']);//dashboard
            Route::get('/get-daily-report', [DailyController::class, 'index']);//daily
            Route::get('/get-monthly-report', [MonthlyController::class, 'index']);//monthly
            Route::post('/get-monthly-report', [MonthlyController::class, 'filter']);//selected month
            Route::post('/get-food-item-report', [ItemWiseController::class, 'filter']);//food-group wise
            Route::post('/get-food-group-report', [GroupWiseController::class, 'filter']);//food-group wise
            Route::post('/get-branch-report', [BranchWiseController::class, 'filter']);//branch wise
            Route::post('/get-user-report', [UserWiseController::class, 'filter']);//user wise
            Route::post('/get-department-report', [DepartmentWiseController::class, 'filter']);//department wise
            Route::post('/get-service-report', [ServiceChargeWiseController::class, 'filter']);//service charge wise
            Route::post('/get-discount-report', [DiscountWiseController::class, 'filter']);//discount charge wise
            //stock report
            Route::post('/get-food-stock-report', [FoodStockController::class, 'filter']);//food stock wise
        });

        //kitchen middleware
        Route::group(['middleware' => ['kitchen']], function () {
            //kitchen dashboard
            Route::get('/get-new-orders', [KitchenController::class, 'index']); //get all new order, is_ready == 0; is_cancelled == 0;
            Route::get('/get-new-orders-online', [KitchenController::class, 'indexOnline']); //get all new order, is_ready == 0; is_cancelled == 0;
            Route::post('/accept-new-order', [KitchenController::class, 'accept']); //accept new order
            Route::post('/mark-order-item-ready', [KitchenController::class, 'itemReady']); //order-item-ready
            Route::post('/mark-all-items-ready', [KitchenController::class, 'orderReady']); //all order-items-ready and order ready

            Route::post('/accept-new-order-online', [KitchenController::class, 'acceptOnline']); //accept new order
            Route::post('/mark-order-item-ready-online', [KitchenController::class, 'itemReadyOnline']); //order-item-ready
            Route::post('/mark-all-items-ready-online', [KitchenController::class, 'orderReadyOnline']); //all order-items-ready and order ready
        });

        //orderHistory middleware
        Route::group(['middleware' => ['orderHistory']], function () {
            //order history
            Route::get('/get-order-history', [orderHistoryController::class, 'index']); //get all new order
            Route::get('/get-order-history-all', [orderHistoryController::class, 'indexAll']); //get all new order
            Route::post('/delete-order-from-history', [orderHistoryController::class, 'destroy']); //delete an order
            //online orders
            Route::get('/get-online-order-history', [orderHistoryController::class, 'indexOnline']); //get all new order
            Route::get('/get-online-order-history-all', [orderHistoryController::class, 'indexAllOnline']); //get all new order
            Route::post('/delete-online-order-from-history', [orderHistoryController::class, 'destroyOnline']); //delete an order
        });

        // DeliveryMan User Routes middleware
        Route::group(['middleware' => ['delivery']], function () {
            //work period
            Route::get('/get-assigned-counters', [DeliveryUserOrderController::class, 'alarm']); //get pending count
            Route::get('/get-assigned-orders', [DeliveryUserOrderController::class, 'index']); //get non delivered orders
            Route::post('/change-status', [DeliveryUserOrderController::class, 'changeStatus']); //change status
            Route::get('/get-delivered-orders', [DeliveryUserOrderController::class, 'indexDelivered']); //get delivered orders
        });
    });
