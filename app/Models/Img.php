<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Img extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public $timestamps = false;

    public static function getImgUrlById($id) {
        if(empty($id)) return null;
        $img = static::where('id', $id)->first();
        return empty($img) ? null : url()->to($img->url);
    }

    public static function getImgIdByUrl($url) {
        if(empty($url)) return null;
        $img = static::where('url', $url)->first();

        if(empty($img))
            $img = static::create(['url' => $url]);
        return empty($img) ? null : $img->id;
    }
}
