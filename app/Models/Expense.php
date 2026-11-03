<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'txn_date' => 'date',
        'total_amount' => 'decimal:2',
        'created_time' => 'datetime',
        'last_updated_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payout()
    {
        return $this->belongsTo(AmbassadorPayout::class, 'payout_id');
    }

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class, 'reimbursement_id');
    }
}

