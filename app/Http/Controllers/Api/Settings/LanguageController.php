<?php

namespace App\Http\Controllers\Api\Settings;
use App\Http\Controllers\Controller;
use App\Models\Lang;
use App\Models\Temporary;
use Illuminate\Http\Request;
use File;
use Illuminate\Support\Str;

class LanguageController extends Controller
{
    //get all languages
    public function index(Request $request)
    {
        $languages = Lang::all();
        $modifiedLangs = array();
        foreach ($languages as $lang) {
            $temp = new Temporary;
            $temp->id = $lang->id;
            $temp->name = $lang->name;
            $temp->code = $lang->code;
            $temp->is_default = $lang->is_default == 0 ? false: true;
            if($request->ip()=="127.0.0.1" || $request->ip()=="::1" ){
                $theImage = substr($lang->image, 1);
                $temp->image = asset('').$theImage;
            }else{
                $temp->image = asset('').$lang->image;
            }
            array_push($modifiedLangs, $temp);
        }
        return [customPaginate($modifiedLangs), $modifiedLangs];
    }

    //store a new language
    public function langStore(Request $request)
    {
        $request->validate([
            'code'   => ['required', 'unique:langs'],
            'name'   => ['required', 'unique:langs']
        ],
            [
                'name.unique'                => 'A language already exists with this name',
                'code.unique'                => 'A language already exists with this code'
            ]
        );
        $lan = new Lang;
        $lan->code =Str::lower(str_replace(' ','_',$request->code));
        $lan->name = $request->name;
        $lan->is_default = false;
        $flag = $request->file('image');
        if(!is_null($flag)){
            $request->validate([
                'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'                => 'Please select a valid image file',
                    'image.max'                  => 'Please select a file less than 5MB'
                ]
            );
            //storing assignment file to server
            $name = time()."-".Str::slug($flag->getClientOriginalName()).".".$flag->getClientOriginalExtension();
            $flag->move(public_path().'/images/flags/', $name);
        }else{
            $name = 'default.png';
        }
        //updating db
        $lan->image = '/images/flags/'.$name;
        $lan->save();

        if($request->ip()=="127.0.0.1" || $request->ip()== "::1" ){
            $path = base_path()."\locales\\en";
            $library_to_path = base_path()."\locales\\".$lan->code;// Coping to folder Path
            $laravel_Path = public_path()."\locales\\".$lan->code;
        }else{
            $path = base_path()."/locales/en";
            $library_to_path = base_path()."/locales/".$lan->code;// Coping to folder Path
            $laravel_Path = public_path()."/locales/".$lan->code;
        }

        File::copyDirectory($path, $library_to_path);
        File::copyDirectory($path, $laravel_Path);
        //get all language
        return $this->index($request);
    }


    //Update a  new language
    public function langUpdate(Request $request)
    {
        $lan = Lang::where('code', $request->editCode)->first();
        $request->validate([
            'name'  => ['required', 'unique:langs,code,'.$lan->code]
        ],
            [
                'name.unique'   => 'A language already exists with this name'
            ]
        );

        $lan->name = $request->name;

        $flag = $request->file('image');
        if(!is_null($flag)){
            $request->validate([
                'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'   => 'Please select a valid image file',
                    'image.max'     => 'Please select a file less than 5MB'
                ]
            );
            //deleting image here
            if($lan->image != "/images/flags/default.png") {
                if (file_exists(public_path($lan->image))) {
                    unlink(public_path($lan->image));
                }
            }
            //storing assignment file to server
            $name = time()."-".Str::slug($flag->getClientOriginalName()).".".$flag->getClientOriginalExtension();
            $flag->move(public_path().'/images/flags/', $name);
            //updating db
            $lan->image = '/images/flags/'.$name;
        }
        $lan->save();

        //get all language
        return $this->index($request);
    }

    //change default language
    public function setDefault(Request $request)
    {
        $lang = Lang::where('code', $request->code)->first();
        $default = Lang::where('is_default', true)->first();
        $default->is_default = false;
        $lang->is_default = true;
        $default->save();
        $lang->save();
        //get all language
        return $this->index($request);
    }


    //save to en > translation.json
    public function store(Request $request)
    {
        //todo:: make url dynamic for live
        $tpath =  "C:/work/Installed/xampp/htdocs/foodkhan/client/public/locales/en/";
        if(File::exists($tpath  . 'translation.json')){
            $jsonString = file_get_contents($tpath  . 'translation.json');
            $jsonString = json_decode($jsonString, true);
            if(!isset($jsonString[$request->key])){
                $jsonString[$request->key] = $request->key;
                ksort($jsonString);
                $jsonData = json_encode($jsonString, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($tpath  . 'translation.json', stripslashes($jsonData));
            }
        }
    }


    //get data to translate
    public function getTranslations(Request $request, $code)
    {
        $jsonString = [];

        if($request->ip()=="127.0.0.1" || $request->ip()=="::1" ){
            $tpath =  public_path()."\locales\\".$code.'\\';
        }else{
            $tpath =  public_path()."/locales/".$code.'/';
        }

        if (File::exists($tpath."translation.json")) {
            $jsonString = file_get_contents($tpath."translation.json");

            $jsonString = json_decode($jsonString, true);
        }
        return $jsonString;
    }

    //save new translations
    public function saveTranslation(Request $request)
    {
        $data = $request->data;
        $code = $request->code;

        if($request->ip()=="127.0.0.1" || $request->ip()=="::1" ){
            $path = base_path()."\locales\\".$code.'\\';
            $laravel_Path = public_path()."\locales\\".$code.'\\';
        }else{
            $path = base_path()."/locales/".$code.'/';
            $laravel_Path = public_path()."/locales/".$code.'/';
        }

        ksort($data);
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path  . 'translation.json', stripslashes($jsonData));
        file_put_contents($laravel_Path  . 'translation.json', stripslashes($jsonData));
        return $jsonData;
    }


    //delete language
    public function destroy($code, Request $request)
    {
        if( $code!= "en"){
            $lang = Lang::where('code',$code)->first();
            if($lang->is_default == true){
                $enLang = Lang::where('code','en')->first();
                $enLang->is_default = true;
                $enLang->save();
            }
            //deleting image here
            if($lang->image != "/images/flags/default.png") {
                if (file_exists(public_path($lang->image))) {
                    unlink(public_path($lang->image));
                }
            }
            $lang->delete();
            //get all language
            return $this->index($request);
        }
    }
}
