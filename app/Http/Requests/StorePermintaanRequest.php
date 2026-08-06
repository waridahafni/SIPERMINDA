<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_data' => 'required|string|max:255',
            'tujuan_penggunaan' => 'required|string',
            'periode_data' => 'required|string|max:50',
            'kategori_id' => 'sometimes|nullable|exists:kategori_data,id',
        ];
    }
}
