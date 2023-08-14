<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User as UserModel;
use App\Http\Controllers\Img as ImgController;
Use App\Models\Img as ImgModel;

class User extends Controller
{
    public function index (Request $request) {
        $userId = $request->user()->id;

        return UserModel::whereNot('id', $userId)->get();
    }

    public function updateProfile(Request $req) {
        $userId = $req->user()->id;
        $data = [];

        if(!empty($req->name))
          $data["name"] = $req->name;

        if($req->hasFile('img')) {
            $data["img_id"] = ImgModel::getImgIdByUrl((new ImgController())->uploadImg($req));
        }

        UserModel::whereKey($userId)->update($data);
        return UserModel::whereKey($userId)->get()->first();
    }
}
