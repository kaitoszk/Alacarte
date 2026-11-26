<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * ログイン中の本人だけに渡す Resource（共有 props の auth.user 用）。
 * email を含むため、他人のユーザー情報には使わないこと。
 */
class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'email' => $this->email,
            'user_image_url' => $this->user_image_path ? Storage::url($this->user_image_path) : null,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
