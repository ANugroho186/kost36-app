<?php

namespace App\Http\Requests\Owner;

use App\Models\Announcement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dipakai untuk membuat dan mengubah pengumuman.
 */
class AnnouncementRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $announcement = $this->route('announcement');

        return $announcement instanceof Announcement
            ? $this->user()?->can('update', $announcement) ?? false
            : $this->user()?->can('create', Announcement::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:5000'],
            // Dari input datetime-local (misalnya "2026-11-01T18:00"), dibaca sebagai WIB
            // karena zona waktu aplikasi Asia/Jakarta. Waktu lampau boleh: artinya langsung tayang.
            'published_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires_at.after' => 'The end time must be after the time it is shown.',
        ];
    }
}
