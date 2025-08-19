<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\TemplateVersion;
use Carbon\Carbon;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Corporate Banking Assessment Template',
            'SME Risk Evaluation Template',
            'Consumer Credit Assessment Template',
            'Trade Finance Evaluation Template',
            'Mortgage Risk Assessment Template',
            'Working Capital Assessment Template',
            'Investment Loan Evaluation Template',
            'Syariah Banking Assessment Template',
            'Annual Review Template',
            'Quarterly Monitoring Template'
        ];
        
        foreach ($templates as $templateName) {
            $template = Template::firstOrCreate(
                ['name' => $templateName]
            );
            
            // Create template versions only if they don't exist
            if ($template->templateVersions()->count() == 0) {
                for ($version = 1; $version <= rand(1, 3); $version++) {
                    TemplateVersion::create([
                        'template_id' => $template->id,
                        'version_number' => $version,
                        'description' => "Version {$version} of {$templateName}",
                        'effective_from' => Carbon::now()->subMonths(rand(1, 12)),
                    ]);
                }
            }
        }
    }
}
