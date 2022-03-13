<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Temporary;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use ZipArchive;
use File;

class SettingsController extends Controller
{
    //general settings
    public function index(Request $request)
    {
        $settings = Setting::all();
        $modifiedSettings = array();
        foreach ($settings as $setting) {
            $temp = new Temporary;
            $temp->id = $setting->id;
            $temp->name = $setting->name;

            if ($request->ip()=="127.0.0.1" || $request->ip()=="::1") {
                $theImage = substr($setting->value, 1);
                if ($setting->name == "type_logo") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "hero_image") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_1") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_2") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_3") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_4") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_5") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_6") {
                    $temp->value = asset('').$theImage;
                } else {
                    $temp->value = $setting->value;
                }
            } else {
                $theImage = substr($setting->value, 1);
                if ($setting->name == "type_logo") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "hero_image") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_1") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_2") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_3") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_4") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_5") {
                    $temp->value = asset('').$theImage;
                } elseif ($setting->name == "banner_image_6") {
                    $temp->value = asset('').$theImage;
                } else {
                    $temp->value = $setting->value;
                }
            }
            array_push($modifiedSettings, $temp);
        }
        return $modifiedSettings;
    }

    //update general settings
    public function store(Request $request)
    {
        $logo = $request->file('image');
        if (!is_null($logo)) {
            $oldLogo = Setting::where('name', "type_logo")->first();
            $request->validate(
                [
              'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
          ],
                [
                  'image.mimes'   => 'Please select a valid image file',
                  'image.max'     => 'Please select a file less than 5MB'
              ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("type_logo"))) {
                if (file_exists(public_path(getSystemSettings("type_logo")))) {
                    unlink(public_path(getSystemSettings("type_logo")));
                }
            }
            //storing assignment file to server
            $name = time()."-".Str::slug($logo->getClientOriginalName()).".".$logo->getClientOriginalExtension();
            $logo->move(public_path().'/images/logo/', $name);
            //updating db
            $oldLogo->value = '/images/logo/'.$name;
            $oldLogo->save();
        }
        $old_background = Setting::where('name', "type_background")->first();
        $old_background->value = $request->type_background;
        $old_background->save();

        $old_clock = Setting::where('name', "type_clock")->first();
        $old_clock->value = $request->type_clock;
        $old_clock->save();

        $old_color = Setting::where('name', "type_color")->first();
        $old_color->value = $request->type_color;
        $old_color->save();

        $old_footer = Setting::where('name', "type_footer")->first();
        $old_footer->value = $request->type_footer;
        $old_footer->save();

        $old_print_footer = Setting::where('name', "type_print_footer")->first();
        $old_print_footer->value = $request->type_print_footer;
        $old_print_footer->save();

        $old_print_heading = Setting::where('name', "type_print_heading")->first();
        $old_print_heading->value = $request->type_print_heading;
        $old_print_heading->save();

        $old_siteName = Setting::where('name', "siteName")->first();
        $old_siteName->value = $request->siteName;
        $old_siteName->save();
        overWriteEnvFile('APP_NAME', $request->siteName);

        $old_address = Setting::where('name', "address")->first();
        $old_address->value = $request->address;
        $old_address->save();

        $old_phnNo = Setting::where('name', "phnNo")->first();
        $old_phnNo->value = $request->phnNo;
        $old_phnNo->save();

        $old_vat = Setting::where('name', "type_vat")->first();
        if ($request->vat_system == "igst") {
            $old_vat->value = $request->type_vat;
            $old_vat->save();
        } else {
            $old_cgst = Setting::where('name', "cgst")->first();
            $old_cgst->value = $request->cgst;
            $old_cgst->save();

            $old_sgst = Setting::where('name', "sgst")->first();
            $old_sgst->value = $request->sgst;
            $old_sgst->save();

            $old_vat->value = $request->sgst + $request->cgst;
            $old_vat->save();
        }

        $old_vat_system = Setting::where('name', "vat_system")->first();
        $old_vat_system->value = $request->vat_system;
        $old_vat_system->save();

        $old_sDiscount = Setting::where('name', "sDiscount")->first();
        $old_sDiscount->value = $request->sDiscount;
        $old_sDiscount->save();

        $old_print_kitchen_bill = Setting::where('name', "print_kitchen_bill")->first();
        $old_print_kitchen_bill->value = $request->print_kitchen_bill;
        $old_print_kitchen_bill->save();

        $old_timezone = Setting::where('name', "timezone")->first();
        if ($request->timezone != '' && $request->timezone != 'null') {
            $old_timezone->value = $request->timezone;
            overWriteEnvFile('APP_TIMEZONE', $request->timezone);
            $old_timezone->save();
        }

        $old_table_waiter = Setting::where('name', "table_waiter")->first();
        $old_table_waiter->value = $request->table_waiter;
        $old_table_waiter->save();

        $old_play_sound = Setting::where('name', "play_sound")->first();
        $old_play_sound->value = $request->play_sound;
        $old_play_sound->save();

        $fav = $request->file('favicon');
        if (!is_null($fav)) {
            $oldFav = Setting::where('name', "favicon")->first();
            $request->validate(
                [
              'favicon'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
          ],
                [
                  'favicon.mimes'   => 'Please select a valid image file',
                  'favicon.max'     => 'Please select a file less than 5MB'
              ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("favicon"))) {
                if (file_exists(public_path(getSystemSettings("favicon")))) {
                    unlink(public_path(getSystemSettings("favicon")));
                }
            }
            //storing assignment file to server
            $name = time()."-".Str::slug($fav->getClientOriginalName()).".".$fav->getClientOriginalExtension();
            $fav->move(public_path().'/images/favicon/', $name);
            //updating db
            $oldFav->value = '/images/favicon/'.$name;
            $oldFav->save();
        }
        return $this->index($request);
    }

    //update pos Screen settings
    public function posScreen(Request $request)
    {
        $old_pos = Setting::where('name', "pos_screen")->first();
        $old_pos->value = $request->pos_screen;
        $old_pos->save();
        return $this->index($request);
    }

    //smtp
    public function smtp()
    {
        $smtpSettings = Collect();
        $smtp = new Temporary;
        $smtp->MAIL_MAILER = \Config::get('app.MAIL_MAILER');
        $smtp->MAIL_HOST = \Config::get('app.MAIL_HOST');
        $smtp->MAIL_PORT = \Config::get('app.MAIL_PORT');
        $smtp->MAIL_USERNAME = \Config::get('app.MAIL_USERNAME');
        $smtp->MAIL_PASSWORD = \Config::get('app.MAIL_PASSWORD');
        $smtp->MAIL_ENCRYPTION = \Config::get('app.MAIL_ENCRYPTION');
        $smtp->MAIL_FROM_ADDRESS = \Config::get('app.MAIL_FROM_ADDRESS');
        $smtp->MAIL_FROM_NAME = \Config::get('app.MAIL_FROM_NAME');
        $smtpSettings->push($smtp);
        return $smtpSettings;
    }

    //overWrite in .env file
    public function smtpStore(Request $request)
    {
        overWriteEnvFile('MAIL_MAILER', $request->MAIL_MAILER);
        overWriteEnvFile('MAIL_HOST', $request->MAIL_HOST);
        overWriteEnvFile('MAIL_PORT', $request->MAIL_PORT);
        overWriteEnvFile('MAIL_USERNAME', $request->MAIL_USERNAME);
        overWriteEnvFile('MAIL_PASSWORD', $request->MAIL_PASSWORD);
        overWriteEnvFile('MAIL_ENCRYPTION', $request->MAIL_ENCRYPTION);
        overWriteEnvFile('MAIL_FROM_ADDRESS', $request->MAIL_FROM_ADDRESS);
        overWriteEnvFile('MAIL_FROM_NAME', $request->MAIL_FROM_NAME);

        $smtpSettings = Collect();
        $smtp = new Temporary;
        $smtp->MAIL_MAILER = env('MAIL_MAILER');
        $smtp->MAIL_HOST = env('MAIL_HOST');
        $smtp->MAIL_PORT = env('MAIL_PORT');
        $smtp->MAIL_USERNAME = env('MAIL_USERNAME');
        $smtp->MAIL_PASSWORD = env('MAIL_PASSWORD');
        $smtp->MAIL_ENCRYPTION = env('MAIL_ENCRYPTION');
        $smtp->MAIL_FROM_ADDRESS = env('MAIL_FROM_ADDRESS');
        $smtp->MAIL_FROM_NAME = env('MAIL_FROM_NAME');
        $smtpSettings->push($smtp);
        return $smtpSettings;
    }

    //update system
    public function updateSystem(Request $request)
    {
        $file = $request->file('file');
        $name = $file->getClientOriginalName(); // file name
        $file->move(base_path('update/'), $name); // storing file
        //Extract
        $zip = new ZipArchive;
        $extract_dir=base_path().'/update'; // extract path
        if ($zip->open($extract_dir . '/' . $name, ZipArchive::CREATE) === true) {
            $zip->extractTo($extract_dir); // extracting zip
            $zip->close();
            unlink(base_path().'/update/'.$name);
        }

        //directories
        $from_update = base_path().'/update/frontend';
        $to_directory = base_path();

        //precache file remove
        // $preCacheFile = glob(base_path().'/precache-manifest.*.js')[0];
        // if (file_exists($preCacheFile)) {
        //     unlink($preCacheFile);
        // }

        //precache from public folder remove
        // $preCacheFile2 = glob(public_path().'/precache-manifest.*.js')[0];
        // if (file_exists($preCacheFile2)) {
        //     unlink($preCacheFile2);
        // }

        //copy files to base path to update
        File::copyDirectory($from_update, $to_directory);
        unlink(base_path().'/update/frontend/'.'.htaccess');
        unlink(base_path().'/update/frontend/'.'index.html');

        //copy files to public path to update
        $to_public = public_path();
        File::copyDirectory($from_update, $to_public);

        //copy file to resources
        $html = base_path().'/index.html';
        $view = base_path().'/resources/views/build/index.html';
        copy($html, $view);

        //copy app & routes from backend to base path
        $from_update_backend = base_path().'/update/backend';
        File::copyDirectory($from_update_backend, $to_directory);
    }

    //todo:: manage db changes here in updates when db changes
    public function refreshSystem()
    {

      //website db changes
        if (!Schema::hasTable('online_order_groups')) {
            Schema::create('online_order_groups', function ($table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id')->nullable(); //for SaaS

                $table->unsignedBigInteger('work_period_id');
                $table->unsignedBigInteger('user_id'); //who made this order
              $table->string('user_name'); //who made this order
              $table->unsignedBigInteger('pos_user_id')->nullable(); //who accepted/cancelled this order

            //order details
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('branch_name')->nullable();
                $table->text('token')->nullable();

                //save after all item save in order items in update version
                $table->string('order_bill')->nullable();
                $table->string('vat')->nullable();
                $table->string('total_payable')->nullable(); //orderBill + vat + serviceCharge - discount
                $table->string('payment_method');

                $table->boolean('is_accepted'); //accepted by pos user
                $table->string('time_to_deliver')->nullable();
                $table->unsignedBigInteger('delivery_boy_id')->nullable();//for future update //who will deliver->waiter id till delivery boy crud be done
              $table->string('delivery_boy_name')->nullable();//for future update

              $table->string('note_to_rider')->nullable();
                $table->string('delivery_phn_no')->nullable();
                $table->text('delivery_address')->nullable();

                $table->boolean('is_cancelled'); //cancelled by the pos user
              $table->text('reason_of_cancel')->nullable(); //cancelled by the pos user
              $table->boolean('is_delivered'); //confirmed by the pos user
              $table->text('delivery_status')->nullable();
                $table->timestamps();
            });
        }
        //online_order_items
        if (!Schema::hasTable('online_order_items')) {
            Schema::create('online_order_items', function ($table) {
                $table->id();
                $table->unsignedBigInteger('order_group_id');
                $table->string('food_item');
                $table->string('food_group');
                $table->text('variation')->nullable();
                $table->longText('properties')->nullable();
                $table->unsignedBigInteger('quantity');
                $table->text('price')->nullable(); //food price or variation price * quantity
                $table->timestamps();
            });
        }

        //online Order Group special column
        if (!Schema::hasColumn('online_order_groups', 'delivery_status')) {
            Schema::table('online_order_groups', function ($table) {
                $table->text('delivery_status')->nullable();
            });
        }

        //food Item special column
        if (!Schema::hasColumn('food_items', 'isSpecial')) {
            Schema::table('food_items', function ($table) {
                $table->string('isSpecial')->after('has_variation')->default(0);
            });
        }

        //order groups
        if (!Schema::hasColumn('order_groups', 'dept_commission')) {
            Schema::table('order_groups', function ($table) {
                $table->unsignedBigInteger('dept_commission')->after('discount')->default(0);
            });
        }

        //work period
        if (!Schema::hasColumn('work_periods', 'token')) {
            Schema::table('work_periods', function ($table) {
                $table->unsignedBigInteger('token')->after('branch_id')->default(1);
            });
        }

        //department tags
        if (!Schema::hasColumn('department_tags', 'commission')) {
            Schema::table('department_tags', function ($table) {
                $table->string('commission')->after('slug')->default(0);
            });
        }

        //settings table new data
        $settings1 = Setting::where('name', "type_print_footer")->first();
        $settings2 = Setting::where('name', "type_print_heading")->first();
        $settings3 = Setting::where('name', "table_waiter")->first();
        $settings4 = Setting::where('name', "pos_screen")->first();
        $settings5 = Setting::where('name', "sDiscount")->first();
        if (is_null($settings1)) {
            $setting = new Setting;
            $setting->name = "type_print_footer";
            $setting->value = "Thanks";
            $setting->save();
        }
        if (is_null($settings2)) {
            $setting = new Setting;
            $setting->name = "type_print_heading";
            $setting->value = "Hello";
            $setting->save();
        }
        if (is_null($settings3)) {
            $setting = new Setting;
            $setting->name = "table_waiter";
            $setting->value = "0";
            $setting->save();
        }
        if (is_null($settings4)) {
            $setting = new Setting;
            $setting->name = "pos_screen";
            $setting->value = "0";
            $setting->save();
        }
        if (is_null($settings5)) {
            $setting = new Setting;
            $setting->name = "sDiscount";
            $setting->value = "flat";
            $setting->save();
        }

        //delivery permission
        $permission = Permission::where('name', 'Delivery')->first();
        if (is_null($permission)) {
            $permission = new Permission;
            $permission->name = "Delivery";
            $permission->slug = "delivery";
            $permission->save();
        }
        $permissionGroup = PermissionGroup::where('name', 'Delivery Man')->first();
        if (is_null($permissionGroup)) {
            $permission = new PermissionGroup;
            $permission->name = "Delivery Man";
            $permission->slug = "delivery-man";
            $permission->permission_id_array = "[9]";
            $permission->save();
        }
    }
}
