<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContractHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contract_id
 * @property string $evento
 * @property array<string, mixed> $payload
 * @property int|null $usuario_id
 * @property string $unique_id
 * @property Carbon $created_at
 * @property-read Contract $contract
 */
class ContractHistory extends Model
{
    /** @use HasFactory<ContractHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_id',
        'evento',
        'payload',
        'usuario_id',
        'unique_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'usuario_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
