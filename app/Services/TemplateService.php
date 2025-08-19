<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Database\Eloquent\Collection;

class TemplateService
{
    public function getAllTemplates(): Collection
    {
        $templates = Template::all();

        return $templates;
    }

    public function getTemplateById(int $id): Template
    {
        $template = Template::findOrFail($id);

        return $template;
    }

    public function createTemplate(array $data): Template
    {
        $template = Template::create($data);

        return $template;
    }

    public function updateTemplate(Template $template, array $data): Template
    {
        $template->update($data);

        return $template;
    }

    public function deleteTemplate(Template $template): void
    {
        $template->delete();
    }
}