<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int $pelanggan_id
 * @property int $mobil_id
 * @property int|null $supir_id
 * @property string $tanggal_sewa
 * @property string $tanggal_kembali
 * @property int $status
 */

class TransaksiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pelanggan_id'          => 'required|exists:users,id',
            'asal_kota'             => 'required|in:1,2',
            'nama_kota'             => 'required_if:asal_kota,2|nullable|string',
            'jaminan'               => 'required|in:1,2',

            // Multi-mobil
            'mobils'                => 'required|array|min:1',
            'mobils.*.mobil_id'     => 'required|exists:mobils,id',
            'mobils.*.tanggal_sewa' => 'required|date',
            'mobils.*.tanggal_kembali' => 'required|date|after_or_equal:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'  => 'required|in:0,1',
            'mobils.*.supir_id'     => 'nullable|exists:supirs,id',
        ];
    }

    public function messages(): array
    {
        return [
            'pelanggan_id.required'          => 'Pelanggan wajib dipilih.',
            'pelanggan_id.exists'            => 'Pelanggan tidak valid.',

            'asal_kota.required'             => 'Asal kota wajib dipilih.',
            'asal_kota.in'                   => 'Asal kota tidak valid.',
            'nama_kota.required_if'          => 'Nama kota harus diisi jika asal kota = luar kota.',

            'jaminan.required'               => 'Jaminan wajib dipilih.',
            'jaminan.in'                     => 'Jaminan tidak valid.',

            'mobils.required'                => 'Minimal 1 mobil harus dipilih.',
            'mobils.array'                   => 'Format data mobil tidak valid.',
            'mobils.*.mobil_id.required'     => 'Mobil harus dipilih.',
            'mobils.*.mobil_id.exists'       => 'Mobil tidak valid.',
            'mobils.*.tanggal_sewa.required' => 'Tanggal sewa harus diisi.',
            'mobils.*.tanggal_kembali.required' => 'Tanggal kembali harus diisi.',
            'mobils.*.tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal sewa.',
            'mobils.*.pakai_supir.required'  => 'Harus pilih apakah pakai supir atau tidak.',
            'mobils.*.pakai_supir.in'        => 'Pilihan pakai supir tidak valid.',
            'mobils.*.supir_id.exists'       => 'Supir tidak valid.',
        ];
    }
}
