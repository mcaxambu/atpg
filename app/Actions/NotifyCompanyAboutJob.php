<?php

namespace App\Actions;

use App\Mail\JobApplicationReceivedMail;
use App\Mail\JobStatusMail;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos por e-mail para a empresa dona da vaga.
 *
 * Todo envio passa por `send()`, que engole a falha e registra no log: um
 * e-mail que nao sai nao pode derrubar uma aprovacao ja gravada nem recusar
 * uma candidatura que o candidato ja enviou.
 */
class NotifyCompanyAboutJob
{
    public function approved(JobOpening $job): void
    {
        $this->sendStatus($job, JobStatusMail::APPROVED);
    }

    public function rejected(JobOpening $job): void
    {
        $this->sendStatus($job, JobStatusMail::REJECTED);
    }

    public function applicationReceived(JobApplication $application): void
    {
        $company = $application->jobOpening?->company;

        if (! $company || blank($company->email)) {
            return;
        }

        $this->send(
            $company->email,
            new JobApplicationReceivedMail($application, $company->contact_name ?: $company->name),
            ['evento' => 'candidatura', 'application_id' => $application->id]
        );
    }

    private function sendStatus(JobOpening $job, string $event): void
    {
        $company = $job->company;

        if (! $company || blank($company->email)) {
            return;
        }

        $this->send(
            $company->email,
            new JobStatusMail($job, $event, $company->contact_name ?: $company->name),
            ['evento' => $event, 'job_id' => $job->id]
        );
    }

    private function send(string $to, $mailable, array $contexto): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $exception) {
            Log::error('Falha ao avisar a empresa sobre vaga.', $contexto + [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
