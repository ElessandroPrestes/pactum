<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ContractItemObserver;
use Database\Factories\ContractItemFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contract_id
 * @property int $service_id
 * @property int $quantidade
 * @property string $valor_unitario
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contract $contract
 * @property-read Service $service
 */
#[ObservedBy(ContractItemObserver::class)]
class ContractItem extends Model
{
    /** @use HasFactory<ContractItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_id',
        'service_id',
        'quantidade',
        'valor_unitario',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'valor_unitario' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
