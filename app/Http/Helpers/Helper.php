<?php
use App\Http\Helpers\Helper;
use App\Models\Setting;
use Illuminate\Pagination\LengthAwarePaginator;

//override or add env file or key
function overWriteEnvFile($type, $val)
{
    $path = base_path('.env'); // get file ENV path
    if (file_exists($path)) {
        $val = '"' . trim($val) . '"';
        if (is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0) {
            file_put_contents($path, str_replace($type . '="' . env($type) . '"', $type . '=' . $val, file_get_contents($path)));
        } else {
            file_put_contents($path, file_get_contents($path) . "\r\n" . $type . '=' . $val);
        }
    }
}

//get system settings
function getSystemSettings($type)
{
    $setting = Setting::where('name', $type)->first();
    if (!is_null($setting)) {
        return $setting->value;
    } else {
        return null;
    }
}

//custom pagination
function customPaginate($data)
{
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $perPageData = array_slice($data, perPagePaginate() * ($currentPage - 1), perPagePaginate());
    return (new LengthAwarePaginator($perPageData, count($data), perPagePaginate(), $currentPage));
}

//todo:: make 25/50/100... perPage here
//pagination per page data count
function perPagePaginate()
{
    return 100;
}
