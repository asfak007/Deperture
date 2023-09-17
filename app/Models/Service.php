<?php

namespace App\Models;


use DateTimeInterface;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Service extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    // protected $primaryKey = 'eiin';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["*"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata' => AsCollection::class,
        'thumbnail' => AsCollection::class,
    ];

    /**
     * Interact with the image.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value != null && file_exists(public_path($value)) ? asset($value) : asset("assets/images/service/default.png"),
            // set: fn ($value) => strtolower($value),
        );
    }


    /**
     * Interact with the thumbnail.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    // protected function thumbnail(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => $value != null && file_exists(public_path($value)) ? asset($value) : asset("assets/images/thumbnail/default.png"),
    //         // set: fn ($value) => strtolower($value),
    //     );
    // }
}
