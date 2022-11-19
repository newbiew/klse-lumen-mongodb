<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Quarterly_report extends Model
{
    protected $fillable = [
        'company_name',
        'short_name',
        'announcement_date',
        'category',
        'reference_number',
        'financial_year_end',
        'qr_number',
        'current_period_end',
        'the_figures',
        'revenue',
        'pl_before_tax',
        'pl_after_tax',
        'current_pl',
        'current_preceding_year_percentage',
        'current_earning_per_share'
    ];
}
