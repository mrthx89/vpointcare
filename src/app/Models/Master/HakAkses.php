<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Support\NavigationHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class HakAkses extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MHakAkses';

    protected $fillable = [
        'IdHakAkses',
        'NamaHakAksesId',
        'NamaHakAksesEn',
        'ModulId',
        'ModulEn',
        'KeteranganId',
        'KeteranganEn',
        'SortOrder',
        'IconString',
        'NonAktif',
    ];

    protected $casts = [
        'NonAktif' => 'boolean',
        'SortOrder' => 'integer',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->getKey() && (string) $model->IdHakAkses === (string) $model->getKey()) {
                throw ValidationException::withMessages([
                    'IdHakAkses' => __('ui.models.hak_akses.parent_self_denied'),
                ]);
            }

            if ($model->KodeHakAkses === 'dashboard.view') {
                $model->IdHakAkses = null;
            }

            if ($model->IdHakAkses && self::query()->whereKey($model->IdHakAkses)->whereNotNull('IdHakAkses')->exists()) {
                throw ValidationException::withMessages([
                    'IdHakAkses' => __('ui.models.hak_akses.parent_child_denied'),
                ]);
            }

            if ($model->isDirty('NamaHakAksesId')) {
                $model->NamaHakAkses = $model->NamaHakAksesId;
            }

            if ($model->isDirty('ModulId')) {
                $model->Modul = $model->ModulId;
            }

            if ($model->isDirty('KeteranganId')) {
                $model->Keterangan = $model->KeteranganId;
            }

            $model->TglEdit = now();
        });

        static::saved(function (): void {
            NavigationHelper::flush();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'IdHakAkses', 'Id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'IdHakAkses', 'Id');
    }

    public function getNamaLocalizedAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en' && ! empty($this->NamaHakAksesEn)) {
            return $this->NamaHakAksesEn;
        }

        return $this->NamaHakAksesId ?: ($this->NamaHakAkses ?? '');
    }

    public function getModulLocalizedAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en' && ! empty($this->ModulEn)) {
            return $this->ModulEn;
        }

        return $this->ModulId ?: ($this->Modul ?? '');
    }

    public function getKeteranganLocalizedAttribute(): ?string
    {
        $locale = app()->getLocale();

        if ($locale === 'en' && ! empty($this->KeteranganEn)) {
            return $this->KeteranganEn;
        }

        return $this->KeteranganId ?: $this->Keterangan;
    }

    public function getGroupLocalizedAttribute(): ?string
    {
        return $this->parent?->NamaLocalized;
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->KodeHakAkses === 'dashboard.view') {
            return __('ui.models.hak_akses.type_dashboard');
        }

        if ($this->KodeHakAkses === null && $this->IdHakAkses === null) {
            return __('ui.models.hak_akses.type_group');
        }

        if ($this->SortOrder !== null || $this->IconString) {
            return __('ui.models.hak_akses.type_menu');
        }

        return __('ui.models.hak_akses.type_permission');
    }

    /**
     * @return array<string, string>
     */
    public static function groupOptions(?string $exceptId = null): array
    {
        return self::query()
            ->whereNull('KodeHakAkses')
            ->whereNull('IdHakAkses')
            ->when($exceptId, fn ($query) => $query->where('Id', '<>', $exceptId))
            ->orderBy('SortOrder')
            ->orderBy('NamaHakAksesId')
            ->get()
            ->mapWithKeys(fn (self $record): array => [(string) $record->getKey() => $record->NamaLocalized])
            ->all();
    }
}
