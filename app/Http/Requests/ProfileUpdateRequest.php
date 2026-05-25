<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Tentukan apakah user boleh make request ini.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }
    /**
     * Aturan validasi untuk update profile.
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'no_hp'      => ['required', 'string', 'max:15'],
            'alamat'     => ['required', 'string', 'max:255'],
            'asal_kota'  => ['required', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah digunakan oleh akun lain.',
            'no_hp.required'     => 'Nomor HP wajib diisi.',
            'alamat.required'    => 'Alamat wajib diisi.',
            'asal_kota.required' => 'Asal kota wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
