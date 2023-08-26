<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * | Get User by Email
     */
    public function getUserByEmail($email)
    {
        return User::where('email', $email)
            ->first();
    }

    public function getUserById($userId)
    {
        return User::select('users.*', 'ulb_masters.ulb_name')
            ->join('ulb_masters', 'ulb_masters.id', 'users.ulb_id')
            ->where('users.id', $userId)
            ->first();
    }

    /**
     * | getUserRoleDtls
     */
    public function getUserRoleDtls()
    {
        return  User::select('users.*', 'wf_role_id', 'role_name')
            ->leftjoin('wf_roleusermaps', 'wf_roleusermaps.user_id', 'users.id')
            ->leftjoin('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            ->where('suspended', false)
            ->where('wf_roleusermaps.is_suspended', false);
    }
}
