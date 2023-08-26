<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionMaster extends Model
{
    use HasFactory;

    /**
     * | Get Permissions Action by Role Id
     */
    public function getPermissionsByRoleId()
    {
        return ActionMaster::query()
            ->whereIn('action_masters.role_id', $this->_roleIds)
            ->where('action_masters.status', 1)
            ->orderBy('serial');
    }
}
