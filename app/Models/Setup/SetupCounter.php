<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupCounter extends Model
{
    protected $table = 'setup_counters';
    protected $primaryKey = 'counter_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const DEV = 'DEV';
    public const FAM = 'FAM';
    public const TKT = 'TKT';
    public const STF = 'STF';
    public const USR = 'USR';

    protected $fillable = [
        'counter_id',
        'counter_value',
        'counter_description',
    ];

    public static function generateCustomId(string $counterId): string
    {
        $counter = self::where('counter_id', $counterId)->first();
        if (!$counter) {
            $counter = self::create([
                'counter_id' => $counterId,
                'counter_value' => 0,
                'counter_description' => "Counter for {$counterId}",
            ]);
        }
        $counter->increment('counter_value');
        $currentValue = $counter->counter_value;
        if ($currentValue < 10) {
            $no = '00' . $currentValue;
        } elseif ($currentValue >= 10 && $currentValue < 100) {
            $no = '0' . $currentValue;
        } else {
            $no = (string) $currentValue;
        }
        return $counterId . $no . date('YmdHis') . rand(100000, 999999);
    }
}
