<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
            'mobils.0.mobil_id' => 'required|exists:mobils,id',
            'mobils.0.tanggal_sewa' => 'required|date|after_or_equal:now',
            'mobils.0.tanggal_kembali' => 'required|date|after:mobils.0.tanggal_sewa',
        ];
    }
    
    public function messages(): array
    {
        return [
            'mobils.0.mobil_id.required' => 'Mobil harus dipilih.',
            'mobils.0.tanggal_sewa.required' => 'Tanggal sewa wajib diisi.',
            'mobils.0.tanggal_sewa.after_or_equal' => 'Tanggal sewa tidak boleh sebelum sekarang.',
            'mobils.0.tanggal_kembali.after' => 'Tanggal kembali harus setelah tanggal sewa.',
        ];
    }
}
