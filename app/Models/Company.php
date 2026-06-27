<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['code', 'name', 'alias'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}