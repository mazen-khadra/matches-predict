<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Img as ImgController;
use App\Models\Img as ImgModel;
use App\Models\User as UserModel;
use App\Models\UserPrediction as UserPredictionModel;
use Illuminate\Http\Request;

class User extends Controller
{
    public function index(Request $req)
    {
        $userId = $req->user()->id;

    

        return UserModel::whereNot('id', $userId)->get();

    }

    public function updateProfile(Request $req)
    {
        $userId = $req->user()->id;
        $data = [];

        if (!empty($req->name)) {
            $data["name"] = $req->name;
        }

        if ($req->hasFile('img')) {
            $data["img_id"] = ImgModel::getImgIdByUrl((new ImgController())->uploadImg($req));
        }

        UserModel::whereKey($userId)->update($data);
        return UserModel::whereKey($userId)->get()->first();
    }
}
