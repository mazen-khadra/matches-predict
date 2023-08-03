<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User as UserModel;

class User extends Controller
{
    public function index (Request $req) {
        $userId = $req->user()->id;

        return UserModel::whereNot('id', $userId)->get();
    }
}
