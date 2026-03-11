<?php

namespace App\Events;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayslipsPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public PayrollPeriod $period)
    {
    }
}
