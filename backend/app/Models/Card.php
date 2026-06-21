<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'kanban_list_id',
        'title',
        'description',
        'labels_csv',
        'assigned_member',
        'due_date',
        'position'
    ];

    public function kanbanList()
    {
        return $this->belongsTo(KanbanList::class);
    }
}
