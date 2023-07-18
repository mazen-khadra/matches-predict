<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User as UserModel;
use App\Models\Img as ImgModel;
use Illuminate\Support\Facades\Auth as AuthFacade;

class Auth extends Controller
{
    public function signUp(Request $req) {
        $data = $req->validate([
            "name" => 'bail|required|alpha',
            "email" => 'bail|required|email|unique:users',
            "password" => 'required'
        ]);

        if(!empty($req->img_url))
            $data["img_id"] = ImgModel::getImgIdByUrl($req->img_url);

        UserModel::create($data);

        return $this->logIn($req);
    }

    public function logIn(Request $req) {
        $data = $req->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        if(AuthFacade::attempt($data)) {
            $user = AuthFacade::user();
            $token = $user->createToken($user->id)->plainTextToken;
            $user->token = $token;
            return response($user);
        }

        return response(null, 401);
    }

    public function getLoggedInUser() {
        if(AuthFacade::check())
            return AuthFacade::user();

        return response(null, 401);
    }

    public function logOut() {
        AuthFacade::logout();
    }
}
