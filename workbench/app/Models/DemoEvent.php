<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoEvent extends Model
{
    protected $table = 'demo_events';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'title',
        'start_iso',
        'end_iso',
        'all_day',
        'resource_id',
        'rrule',
        'exdate_json',
        'color',
        'location',
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'exdate_json' => 'array',
    ];
}
