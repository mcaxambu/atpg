<?php

namespace App\Http\Requests\Admin;

use App\Models\MeetingMinute;
use App\Support\NotionMarkdown;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class MeetingMinuteRequest extends FormRequest
{
    /** Imagens relativas descartadas na limpeza do export do Notion. */
    public int $imagensRemovidas = 0;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date', 'before_or_equal:today'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:200000'],
            'is_published' => ['nullable', 'boolean'],
            'remove_file' => ['nullable', 'boolean'],

            // O PDF e opcional: uma ata pode existir so com o texto em
            // Markdown. A regra de "pelo menos um dos dois" fica em after().
            'file' => ['nullable', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:20480'],

            // Arquivo .md: e apenas uma forma de preencher o campo de texto.
            'markdown_file' => ['nullable', 'file', 'mimes:md,markdown,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimetypes' => 'O documento oficial precisa ser um PDF.',
            'file.mimes' => 'O documento oficial precisa ser um PDF.',
            'file.max' => 'O PDF pode ter no máximo 20 MB.',
            'markdown_file.mimes' => 'O texto da ata deve ser um arquivo .md.',
            'markdown_file.max' => 'O arquivo .md pode ter no máximo 2 MB.',
            'meeting_date.before_or_equal' => 'A data da reunião não pode ser no futuro.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ata = $this->route('ata');

                $temTexto = filled($this->input('body')) || $this->hasFile('markdown_file');
                $temPdf = $this->hasFile('file')
                    || ($ata?->hasFile() && ! $this->boolean('remove_file'));

                if (! $temTexto && ! $temPdf) {
                    $validator->errors()->add(
                        'file',
                        'Informe ao menos o PDF assinado ou o texto da ata em Markdown.'
                    );
                }
            },
        ];
    }

    /**
     * Dados prontos para persistir.
     *
     * São duas origens com formatos diferentes: o arquivo .md do Notion chega
     * em Markdown e é convertido aqui, enquanto o campo do formulário já vem em
     * HTML do editor do painel. Passar o HTML do editor pela limpeza de Notion
     * destruiria a formatação — ela é escrita para Markdown.
     *
     * O HTML não é limpo aqui de propósito: isso mora no model, que é por onde
     * todos os caminhos de gravação passam (ver RendersRichText).
     */
    public function minuteData(): array
    {
        $data = $this->safe()->except(['file', 'markdown_file', 'remove_file']);
        $data['is_published'] = $this->boolean('is_published');

        // Arquivo .md enviado tem precedência sobre o que estiver no campo:
        // quem anexou um arquivo espera que ele seja o conteúdo.
        if ($this->hasFile('markdown_file')) {
            $limpo = NotionMarkdown::clean(
                file_get_contents($this->file('markdown_file')->getRealPath())
            );

            $this->imagensRemovidas = $limpo['imagens_removidas'];

            $data['body'] = Str::markdown($limpo['body'], [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]);
        }

        return $data;
    }

    /**
     * @return array{file_path: string, file_name: string, file_size: int}
     */
    public function storeFile(): array
    {
        $arquivo = $this->file('file');

        return [
            'file_path' => $arquivo->store(MeetingMinute::DIRECTORY, MeetingMinute::DISK),
            'file_name' => $arquivo->getClientOriginalName(),
            'file_size' => $arquivo->getSize(),
        ];
    }
}
