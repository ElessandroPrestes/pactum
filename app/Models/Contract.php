<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Observers\ContractObserver;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property Carbon $data_inicio
 * @property Carbon|null $data_fim
 * @property ContractStatus $status
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Client $client
 * @property-read Collection<int, ContractItem> $items
 */
#[ObservedBy(ContractObserver::class)]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'data_inicio',
        'data_fim',
        'status',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'status' => ContractStatus::class,
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<ContractItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Ativo);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeDoCliente(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }
}
