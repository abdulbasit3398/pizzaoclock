<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebsiteController extends Controller
{
    //hero section store
    public function store(Request $request)
    {
        //sub-heading 1
        $oldSub1 = Setting::where('name', 'hero_sub_1')->first();
        if (!is_null($oldSub1)) {
            $oldSub1->value = $request->subHeading1;
        } else {
            $oldSub1 = new Setting;
            $oldSub1->name = 'hero_sub_1';
            $oldSub1->value = $request->subHeading1;
        }

        //heading
        $heading = Setting::where('name', 'hero_heading')->first();
        if (!is_null($heading)) {
            $heading->value = $request->heading;
        } else {
            $heading = new Setting;
            $heading->name = 'hero_heading';
            $heading->value = $request->heading;
        }

        //sub-heading 2
        $oldSub2 = Setting::where('name', 'hero_sub_2')->first();
        if (!is_null($oldSub2)) {
            $oldSub2->value = $request->subHeading2;
        } else {
            $oldSub2 = new Setting;
            $oldSub2->name = 'hero_sub_2';
            $oldSub2->value = $request->subHeading2;
        }

        $oldSub1->save();
        $heading->save();
        $oldSub2->save();

        //image
        $logo = $request->file('image');
        if (!is_null($logo)) {
            $oldLogo = Setting::where('name', "hero_image")->first();
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
            if (!is_null(getSystemSettings("hero_image"))) {
                if (file_exists(public_path(getSystemSettings("hero_image")))) {
                    unlink(public_path(getSystemSettings("hero_image")));
                }
            }
            if (!is_null($oldLogo)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo->getClientOriginalName()).".".$logo->getClientOriginalExtension();
                $logo->move(public_path().'/images/hero/', $name);
                //updating db
                $oldLogo->value = '/images/hero/'.$name;
            } else {
                $oldLogo = new Setting;
                $oldLogo->name = 'hero_image';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo->getClientOriginalName()).".".$logo->getClientOriginalExtension();
                $logo->move(public_path().'/images/hero/', $name);
                //updating db
                $oldLogo->value = '/images/hero/'.$name;
            }
            $oldLogo->save();
        }
    }


    //promotion section store
    public function promotionStore(Request $request)
    {
        //sub-heading 1
        $oldSub1 = Setting::where('name', 'banner_sub_heading_1')->first();
        if (!is_null($oldSub1)) {
            $oldSub1->value = $request->subHeading1;
        } else {
            $oldSub1 = new Setting;
            $oldSub1->name = 'banner_sub_heading_1';
            $oldSub1->value = $request->subHeading1;
        }
        $oldSub1->save();
        //heading 1
        $heading1 = Setting::where('name', 'banner_heading_1')->first();
        if (!is_null($heading1)) {
            $heading1->value = $request->heading1;
        } else {
            $heading1 = new Setting;
            $heading1->name = 'banner_heading_1';
            $heading1->value = $request->heading1;
        }
        $heading1->save();

        //image1
        $logo1 = $request->file('image1');
        if (!is_null($logo1)) {
            $oldLogo1 = Setting::where('name', "banner_image_1")->first();
            $request->validate(
                [
            'image1'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image1.mimes'   => 'Please select a valid image file',
                'image1.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_1"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_1")))) {
                    unlink(public_path(getSystemSettings("banner_image_1")));
                }
            }
            if (!is_null($oldLogo1)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo1->getClientOriginalName()).".".$logo1->getClientOriginalExtension();
                $logo1->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo1->value = '/images/promotion/'.$name;
            } else {
                $oldLogo1 = new Setting;
                $oldLogo1->name = 'banner_image_1';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo1->getClientOriginalName()).".".$logo1->getClientOriginalExtension();
                $logo1->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo1->value = '/images/promotion/'.$name;
            }
            $oldLogo1->save();
        }

        //sub-heading 6
        $oldSub6 = Setting::where('name', 'banner_sub_heading_6')->first();
        if (!is_null($oldSub6)) {
            $oldSub1->value = $request->subHeading6;
        } else {
            $oldSub6 = new Setting;
            $oldSub6->name = 'banner_sub_heading_6';
            $oldSub6->value = $request->subHeading6;
        }
        $oldSub6->save();
        //heading 6
        $heading6 = Setting::where('name', 'banner_heading_6')->first();
        if (!is_null($heading6)) {
            $heading6->value = $request->heading6;
        } else {
            $heading6 = new Setting;
            $heading6->name = 'banner_heading_6';
            $heading6->value = $request->heading6;
        }
        $heading6->save();
        //image6
        $logo6 = $request->file('image6');
        if (!is_null($logo6)) {
            $oldLogo6 = Setting::where('name', "banner_image_6")->first();
            $request->validate(
                [
            'image6'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image6.mimes'   => 'Please select a valid image file',
                'image6.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_6"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_6")))) {
                    unlink(public_path(getSystemSettings("banner_image_6")));
                }
            }
            if (!is_null($oldLogo6)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo6->getClientOriginalName()).".".$logo6->getClientOriginalExtension();
                $logo6->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo6->value = '/images/promotion/'.$name;
            } else {
                $oldLogo6 = new Setting;
                $oldLogo6->name = 'banner_image_6';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo6->getClientOriginalName()).".".$logo6->getClientOriginalExtension();
                $logo6->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo6->value = '/images/promotion/'.$name;
            }
            $oldLogo6->save();
        }

        //heading 2
        $heading2 = Setting::where('name', 'banner_heading_2')->first();
        if (!is_null($heading2)) {
            $heading2->value = $request->heading2;
        } else {
            $heading2 = new Setting;
            $heading2->name = 'banner_heading_2';
            $heading2->value = $request->heading2;
        }
        $heading2->save();
        //image2
        $logo2 = $request->file('image2');
        if (!is_null($logo2)) {
            $oldLogo2 = Setting::where('name', "banner_image_2")->first();
            $request->validate(
                [
            'image2'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image2.mimes'   => 'Please select a valid image file',
                'image2.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_2"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_2")))) {
                    unlink(public_path(getSystemSettings("banner_image_2")));
                }
            }
            if (!is_null($oldLogo2)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo2->getClientOriginalName()).".".$logo2->getClientOriginalExtension();
                $logo2->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo2->value = '/images/promotion/'.$name;
            } else {
                $oldLogo2 = new Setting;
                $oldLogo2->name = 'banner_image_2';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo2->getClientOriginalName()).".".$logo2->getClientOriginalExtension();
                $logo2->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo2->value = '/images/promotion/'.$name;
            }
            $oldLogo2->save();
        }

        //heading3
        $heading3 = Setting::where('name', 'banner_heading_3')->first();
        if (!is_null($heading3)) {
            $heading3->value = $request->heading3;
        } else {
            $heading3 = new Setting;
            $heading3->name = 'banner_heading_3';
            $heading3->value = $request->heading3;
        }
        $heading3->save();
        //image3
        $logo3 = $request->file('image3');
        if (!is_null($logo3)) {
            $oldLogo3 = Setting::where('name', "banner_image_3")->first();
            $request->validate(
                [
            'image3'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image3.mimes'   => 'Please select a valid image file',
                'image3.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_3"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_3")))) {
                    unlink(public_path(getSystemSettings("banner_image_3")));
                }
            }
            if (!is_null($oldLogo3)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo3->getClientOriginalName()).".".$logo3->getClientOriginalExtension();
                $logo3->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo3->value = '/images/promotion/'.$name;
            } else {
                $oldLogo3 = new Setting;
                $oldLogo3->name = 'banner_image_3';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo3->getClientOriginalName()).".".$logo3->getClientOriginalExtension();
                $logo3->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo3->value = '/images/promotion/'.$name;
            }
            $oldLogo3->save();
        }

        //heading4
        $heading4 = Setting::where('name', 'banner_heading_4')->first();
        if (!is_null($heading4)) {
            $heading4->value = $request->heading4;
        } else {
            $heading4 = new Setting;
            $heading4->name = 'banner_heading_4';
            $heading4->value = $request->heading4;
        }
        $heading4->save();
        //image4
        $logo4 = $request->file('image4');
        if (!is_null($logo4)) {
            $oldLogo4 = Setting::where('name', "banner_image_4")->first();
            $request->validate(
                [
            'image4'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image4.mimes'   => 'Please select a valid image file',
                'image4.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_4"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_4")))) {
                    unlink(public_path(getSystemSettings("banner_image_4")));
                }
            }
            if (!is_null($oldLogo4)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo4->getClientOriginalName()).".".$logo4->getClientOriginalExtension();
                $logo4->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo4->value = '/images/promotion/'.$name;
            } else {
                $oldLogo4 = new Setting;
                $oldLogo4->name = 'banner_image_4';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo4->getClientOriginalName()).".".$logo4->getClientOriginalExtension();
                $logo4->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo4->value = '/images/promotion/'.$name;
            }
            $oldLogo4->save();
        }


        //heading5
        $heading5 = Setting::where('name', 'banner_heading_5')->first();
        if (!is_null($heading5)) {
            $heading5->value = $request->heading5;
        } else {
            $heading5 = new Setting;
            $heading5->name = 'banner_heading_5';
            $heading5->value = $request->heading5;
        }
        $heading5->save();
        //image4
        $logo5 = $request->file('image5');
        if (!is_null($logo5)) {
            $oldLogo5 = Setting::where('name', "banner_image_5")->first();
            $request->validate(
                [
            'image5'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
        ],
                [
                'image5.mimes'   => 'Please select a valid image file',
                'image5.max'     => 'Please select a file less than 5MB'
            ]
            );
            //deleting old image here
            if (!is_null(getSystemSettings("banner_image_5"))) {
                if (file_exists(public_path(getSystemSettings("banner_image_5")))) {
                    unlink(public_path(getSystemSettings("banner_image_5")));
                }
            }
            if (!is_null($oldLogo5)) {
                //storing assignment file to server
                $name = time()."-".Str::slug($logo5->getClientOriginalName()).".".$logo5->getClientOriginalExtension();
                $logo5->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo5->value = '/images/promotion/'.$name;
            } else {
                $oldLogo5 = new Setting;
                $oldLogo5->name = 'banner_image_5';
                //storing assignment file to server
                $name = time()."-".Str::slug($logo5->getClientOriginalName()).".".$logo5->getClientOriginalExtension();
                $logo5->move(public_path().'/images/promotion/', $name);
                //updating db
                $oldLogo5->value = '/images/promotion/'.$name;
            }
            $oldLogo5->save();
        }
    }
}
