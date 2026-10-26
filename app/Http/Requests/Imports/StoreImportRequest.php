<?php

declare(strict_types=1);

namespace App\Http\Requests\Imports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The upload's file rules. Whether the cards inside are right is ImportService's job, so the
 * upload screen and the shiplog:import command refuse exactly the same files.
 */
final class StoreImportRequest extends FormRequest
{
    // 2 MB, in the kilobytes the max rule counts. A probe file of 960 cards (8 months of 4 lanes)
    // was 373 KB on 26 Oct 2026, so a year fits. PHP and nginx allow far more, so this is the cap.
    public const int MAX_KILOBYTES = 2048;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // extensions checks the name, mimes what the content looks like (JSON or plain text).
        return [
            'file' => ['required', 'file', 'extensions:json,txt', 'mimes:json,txt', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    public function fileText(): string
    {
        return (string) $this->file('file')->get();
    }

    public function fileName(): string
    {
        // imports.file_name is 255 characters.
        return mb_substr($this->file('file')->getClientOriginalName(), 0, 255);
    }
}
