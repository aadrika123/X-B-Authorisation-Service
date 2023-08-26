<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperList extends Model
{
    use HasFactory;

    /**
     * | All Developer List
     */
    public function developerList()
    {
        $data = DeveloperList::where('status', 1)
            ->orderbydesc('id')
            ->get();
        return $data;
    }
}
