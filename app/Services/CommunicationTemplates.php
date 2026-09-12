<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Tender;

/**
 * Default subject/body templates for each communication category.
 * Placeholders resolved:
 *   {{tender_title}}, {{tender_no}}, {{institution}},
 *   {{closing_date}}, {{application_no}}, {{company_name}}
 */
class CommunicationTemplates
{
    public static function all(): array
    {
        return [
            'submission_ack' => [
                'label'   => 'Submission Acknowledgement',
                'subject' => 'Acknowledgement of Receipt - {{tender_title}}',
                'body'    => "We thank you for your participation in the procurement process for {{tender_title}}.\n\nWe confirm receipt of your application (reference {{application_no}}). Your submission is currently undergoing evaluation and due diligence in accordance with the applicable procurement requirements.\n\nPlease note that the submission of an application does not constitute an award or commitment to engage your organization.\n\nThe results of the evaluation will be communicated to participating bidders in due course.\n\nTenderPlug Procurement Team",
            ],
            'clarification_request' => [
                'label'   => 'Clarification Request',
                'subject' => 'Request for Clarification - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nDuring the evaluation of your application for {{tender_title}} ({{tender_no}}), we require clarification on the following:\n\n[Please describe what needs clarification]\n\nKindly respond by [DATE] to enable the evaluation to proceed. Please quote your application reference {{application_no}} in your response.\n\nTenderPlug Procurement Team",
            ],
            'missing_documents' => [
                'label'   => 'Missing Document Request',
                'subject' => 'Missing Documents - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nDuring the compliance review of your application for {{tender_title}} ({{tender_no}}), the following documents were noted as missing or incomplete:\n\n[List missing documents]\n\nPlease submit the outstanding documents by [DATE]. Failure to comply may result in your application being declared non-responsive.\n\nTenderPlug Procurement Team",
            ],
            'shortlisting' => [
                'label'   => 'Shortlisting Notification',
                'subject' => 'You have been shortlisted - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nWe are pleased to inform you that your application ({{application_no}}) for {{tender_title}} ({{tender_no}}) has been shortlisted for the next stage of evaluation.\n\nNext steps will be communicated to you separately. Please continue to keep your bid documentation available for any further verification requests.\n\nTenderPlug Procurement Team",
            ],
            'due_diligence' => [
                'label'   => 'Due Diligence Request',
                'subject' => 'Due Diligence - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nAs part of the due-diligence process for {{tender_title}} ({{tender_no}}), we request the following:\n\n[Describe DD requirements — site visit, references, financials, insurance certificates, etc.]\n\nKindly respond by [DATE].\n\nTenderPlug Procurement Team",
            ],
            'award' => [
                'label'   => 'Award Notification',
                'subject' => 'Notification of Award - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nWe are pleased to inform you that {{company_name}} has been awarded the tender {{tender_title}} ({{tender_no}}).\n\nContract details and next steps will be communicated to you separately by the contracting authority ({{institution}}).\n\nCongratulations, and thank you for participating in this procurement process.\n\nTenderPlug Procurement Team",
            ],
            'unsuccessful' => [
                'label'   => 'Unsuccessful Bidder Notification',
                'subject' => 'Notification of Outcome - {{tender_title}}',
                'body'    => "Reference: {{application_no}}\n\nThank you for participating in {{tender_title}} ({{tender_no}}). Following the evaluation, we regret to inform you that your bid ({{application_no}}) was not successful on this occasion.\n\nWe appreciate the effort taken to prepare your submission and encourage you to participate in future opportunities.\n\nTenderPlug Procurement Team",
            ],
            'other' => [
                'label'   => 'Other',
                'subject' => '{{tender_title}}',
                'body'    => 'Reference: {{application_no}}',
            ],
        ];
    }

    /**
     * Render a template's subject+body against a tender and (optional) application,
     * replacing {{placeholders}}.
     */
    public static function render(string $category, Tender $tender, ?Application $application = null): array
    {
        $tpl = self::all()[$category] ?? self::all()['other'];
        $vars = self::vars($tender, $application);
        return [
            'subject' => strtr($tpl['subject'], $vars),
            'body'    => strtr($tpl['body'], $vars),
        ];
    }

    /**
     * Apply {{placeholder}} substitution against an arbitrary string.
     */
    public static function substitute(string $text, Tender $tender, ?Application $application = null): string
    {
        return strtr($text, self::vars($tender, $application));
    }

    private static function vars(Tender $tender, ?Application $application): array
    {
        return [
            '{{tender_title}}'   => $tender->title,
            '{{tender_no}}'      => $tender->tender_no ?? '',
            '{{institution}}'    => $tender->institution?->institution_name ?? '',
            '{{closing_date}}'   => $tender->closing_date_and_time?->format('D, d M Y H:i') ?? '',
            '{{application_no}}' => $application?->application_no ?? '',
            '{{company_name}}'   => $application?->company_name ?? '',
        ];
    }
}
