<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class HakAkses extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MHakAkses';

    protected $fillable = [
        'NamaHakAksesId',
        'NamaHakAksesEn',
        'ModulId',
        'ModulEn',
        'KeteranganId',
        'KeteranganEn',
        'NonAktif',
    ];

    protected $casts = [
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
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
}
