<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    // permission
    protected function permission()
    {
        $phpVersion = number_format((float)phpversion(), 2, '.', '');
        $curlStatus = function_exists('curl_version');
        $envStatus = is_writable(base_path('.env'));
        return [$phpVersion,$curlStatus,$envStatus];
    }


    //save database information in env file
    // clear cache
    protected function dbStore(Request $request)
    {
        overWriteEnvFile('DB_HOST', $request->DB_HOST);
        overWriteEnvFile('DB_PORT', $request->DB_PORT);
        overWriteEnvFile('DB_DATABASE', $request->DB_DATABASE);
        overWriteEnvFile('DB_USERNAME', $request->DB_USERNAME);
        overWriteEnvFile('DB_PASSWORD', $request->DB_PASSWORD);
    }

    // checkDbConnection
    protected function checkDbConnection()
    {
        try {
            //check the database connection for import the sql file
            DB::connection()->getPdo();
            return "ok";
        } catch (\Exception $e) {
            return "error";

        }
    }


    //import the sql file in database or goto organization setup page
    protected function sqlUpload()
    {
        try {
            //import the sql file in database
            $sql_path = base_path('install.sql');
            DB::unprepared(file_get_contents($sql_path)); // uploaded sql
            return "ok";
        } catch (\Exception $e) {
            return "error";
        }
    }

    //import the demo sql file in database or goto organization setup page
    protected function sqlUploadDemo()
    {
        try {
            //import the sql file in database
            $sql_path = base_path('demo.sql');
            DB::unprepared(file_get_contents($sql_path)); // uploaded sql
            return "ok";
        } catch (\Exception $e) {
            return "error";
        }
    }

    //return ip and domain for purchase key verification
    protected function getServerIpAddress(Request $request)
    {
        $ip_address = $request->ip();
        $Domain = request()->getHttpHost();
        return [$ip_address, $Domain];
    }


    protected function adminStore(Request $request)
    {
        $admin = User::where('id',1)->first();
        $admin->name = $request->name;
        $admin->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $admin->phn_no = $request->phn_no;
        $admin->email = $request->email;
        if($request->password !=NULL || $request->password != ""){
            $admin->password = Hash::make($request->password);
        }
        if ($admin->save()) {
            //replace the env file
            overWriteEnvFile('PURCHASE_KEY', $request->purchase_key);
            overWriteEnvFile('MIX_PUSHER_APP_CLUSTER_SECURE', '7469a286259799e5b37e5db9296f00b3');
            if($request->ip()=="127.0.0.1" || $request->ip()=="::1" ){
                $new_base_path = "http://localhost/khadyo";
                overWriteEnvFile('APP_URL', $new_base_path);
            }else{
                $new_base_path = $request->getSchemeAndHttpHost();
                overWriteEnvFile('APP_URL', URL::to($new_base_path));
            }
            return "ok";
        } else {
            return "error";
        }
    }
}
