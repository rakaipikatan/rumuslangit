<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionnaireSession extends Model
{
    protected $fillable = ['user_id', 'feature_id', 'answers'];

    protected $casts = ['answers' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
