<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'documento',
        'tipo_documento',
        'email',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento' => DocumentType::class,
            'status' => ClientStatus::class,
        ];
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Ativo);
    }
}
