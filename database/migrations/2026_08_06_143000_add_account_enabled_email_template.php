<?php

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\User;
use App\Models\UserEmailTemplate;
use App\Models\Utility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates') || ! Schema::hasTable('email_template_langs')) {
            return;
        }

        $content = Utility::loginCredentialsEmailContent();
        $employeeContent = str_replace(
            ['{name}', '{email}', '{password}'],
            ['{employee_name}', '{employee_email}', '{employee_password}'],
            $content
        );

        $templates = [
            'account_enabled' => [
                'name' => 'Account Enabled',
                'subject' => 'Your account has been enabled',
                'content' => $content,
            ],
            'new_user' => [
                'name' => 'New User',
                'subject' => 'Your login credentials',
                'content' => $content,
            ],
            'new_employee' => [
                'name' => 'New Employee',
                'subject' => 'Your login credentials',
                'content' => $employeeContent,
            ],
        ];

        foreach ($templates as $slug => $meta) {
            $template = EmailTemplate::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'from' => 'HRMS',
                    'created_by' => 1,
                ]
            );

            if ($template->name !== $meta['name']) {
                $template->name = $meta['name'];
                $template->save();
            }

            $lang = EmailTemplateLang::firstOrNew([
                'parent_id' => $template->id,
                'lang' => 'en',
            ]);
            $lang->subject = $meta['subject'];
            $lang->content = $meta['content'];
            $lang->save();

            if (Schema::hasTable('user_email_templates')) {
                $companyIds = User::where('type', 'company')->pluck('id');
                foreach ($companyIds as $companyId) {
                    UserEmailTemplate::firstOrCreate(
                        [
                            'template_id' => $template->id,
                            'user_id' => $companyId,
                        ],
                        ['is_active' => 1]
                    );
                }

                // Ensure active for companies that already had a row
                UserEmailTemplate::where('template_id', $template->id)->update(['is_active' => 1]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $template = EmailTemplate::where('slug', 'account_enabled')->first();
        if (! $template) {
            return;
        }

        if (Schema::hasTable('user_email_templates')) {
            UserEmailTemplate::where('template_id', $template->id)->delete();
        }
        if (Schema::hasTable('email_template_langs')) {
            EmailTemplateLang::where('parent_id', $template->id)->delete();
        }
        $template->delete();
    }
};
