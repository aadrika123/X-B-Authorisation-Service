<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCategory extends Model
{
    use HasFactory;

    /**
     * | All Api list
     */
    public function categoryList()
    {
        $data = ApiCategory::where('status', 1)
            ->orderbydesc('id')
            ->get();
        return $data;
    }
}
