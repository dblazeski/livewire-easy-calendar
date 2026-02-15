<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoResource extends Model
{
    protected $table = 'demo_resources';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'title',
        'capacity',
        'location',
    ];
}
