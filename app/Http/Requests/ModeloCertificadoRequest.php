<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModeloCertificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['administrador', 'gestor']);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome'        => isset($this->nome) ? trim((string) $this->nome) : null,
            'descricao'   => isset($this->descricao) ? trim((string) $this->descricao) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'eixo_id'       => ['nullable', 'exists:eixos,id'],
            'nome'          => ['required', 'string', 'max:255'],
            'descricao'     => ['nullable', 'string'],
            'imagem_frente' => ['nullable', 'image', 'max:5120'],
            'imagem_verso'  => ['nullable', 'image', 'max:5120'],
            'texto_frente'  => ['nullable', 'string'],
            'texto_verso'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'   => 'Informe o nome do modelo.',
            'nome.max'        => 'Nome deve ter no máximo 255 caracteres.',
            'eixo_id.exists'  => 'Eixo inválido.',
        ];
    }
}
