<?php

namespace App\Http\Controllers;

use App\Models\Img as ImgModel;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;

class Auth extends Controller
{
    public function signUp(Request $req)
    {
        $data = $req->validate([
            "name" => 'bail|required',
            "email" => 'bail|required|email|:users',
            "password" => 'required',
            "remember_token" => 'required',
        ]);

        if (!empty($req->img_url)) {
            $data["img_id"] = ImgModel::getImgIdByUrl($req->img_url);
        }

        if (UserModel::where('email', '=', $req->email)->count() == 0) {
            UserModel::create($data);
            return $this->logIn($req);
        }

        return response(["error" => "The email has already been taken"], 401);
    }

    public function logIn(Request $req)
    {
        $data = $req->validate([
            "email" => "required|email",
            "password" => "required",
        ]);

        if (AuthFacade::attempt($data)) {
            $user = AuthFacade::user();
            $token = $user->createToken($user->id)->plainTextToken;
            $user->token = $token;
            return response($user);
        }

        return response(["error" => "Invalid Credentials"], 401);
    }

    public function getLoggedInUser()
    {
        if (AuthFacade::check()) {
            return AuthFacade::user();
        }

        return response(null, 401);
    }

    public function logOut()
    {
        AuthFacade::logout();
    }
}
