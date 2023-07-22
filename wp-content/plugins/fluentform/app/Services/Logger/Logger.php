<?php

namespace FluentForm\App\Services\Logger;

use FluentForm\App\Models\Log;
use FluentForm\App\Models\Form;
use FluentForm\App\Helpers\Helper;
use FluentForm\App\Models\Scheduler;
use FluentForm\Framework\Support\Arr;
use FluentForm\Framework\Support\Collection;
use FluentForm\Framework\Validator\ValidationException;

class Logger
{
    public function get($attributes = [])
    {
        $status = Arr::get($attributes, 'status');
        $formId = Arr::get($attributes, 'form_id');
        $component = Arr::get($attributes, 'component');
        $sortBy = Arr::get($attributes, 'sort_by', 'DESC');
        $type = Arr::get($attributes, 'type', 'log');

        [$table, $model, $columns, $join, $componentColumn] = $this->getBases($type);

        $logsQuery = $model->select($columns)
            ->leftJoin('fluentform_forms', 'fluentform_forms.id', '=', $join)
            ->orderBy($table . '.id', $sortBy)
            ->when($formId, function ($q) use ($formId) {
                return $q->where('fluentform_forms.id', intval($formId));
            })
            ->when($status, function ($q) use ($status, $table) {
                return $q->where($table . '.status', sanitize_text_field($status));
            })
            ->when($component, function ($q) use ($component, $componentColumn) {
                return $q->where($componentColumn, sanitize_text_field($component));
            });

        $logs = $logsQuery->paginate();

        $logItems = $logs->items();

        foreach ($logItems as $log) {
            $hasUrl = ('api' === $type) || (
                'submission_item' == $log->source_type && $log->submission_id
            );

            if ($hasUrl) {
                $log->submission_url = admin_url(
                    'admin.php?page=fluent_forms&route=entries&form_id=' . $log->form_id . '#/entries/' . $log->submission_id
                );
            }

            $log->component = Helper::getLogInitiator($log->component, $type);
        }

        $logs->setCollection(Collection::make($logItems));
    
        $logItems = apply_filters_deprecated(
            'fluentform_all_logs',
            [
                $logItems
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/get_logs',
            'Use fluentform/get_logs instead of fluentform_all_logs'
        );

        return apply_filters('fluentform/get_logs', $logs);
    }

    protected function getBases($type)
    {
        if ('log' === $type) {
            $table = 'fluentform_logs';
            $model = Log::query();
            $columns = [
                'fluentform_logs.*',
                'fluentform_forms.title as form_title',
                'fluentform_logs.source_id as submission_id',
                'fluentform_logs.parent_source_id as form_id',
            ];
            $join = 'fluentform_logs.parent_source_id';
            $componentColumn = 'fluentform_logs.component';
        } else {
            $table = 'ff_scheduled_actions';
            $model = Scheduler::query();
            $columns = [
                'ff_scheduled_actions.id',
                'ff_scheduled_actions.action as component',
                'ff_scheduled_actions.form_id',
                'ff_scheduled_actions.origin_id as submission_id',
                'ff_scheduled_actions.status',
                'ff_scheduled_actions.note',
                'ff_scheduled_actions.created_at',
                'fluentform_forms.title as form_title',
            ];
            $join = 'ff_scheduled_actions.form_id';
            $componentColumn = 'ff_scheduled_actions.action';
        }

        return [$table, $model, $columns, $join, $componentColumn];
    }

    public function getFilters($attributes = [])
    {
        $type = Arr::get($attributes, 'type', 'log');

        if ('log' === $type) {
            $logs = Log::select('status', 'component', 'parent_source_id as form_id')->get();
        } else {
            $logs = Scheduler::select('status', 'action as component', 'form_id')->get();
        }

        $statuses = $logs->groupBy('status')->keys()->map(function ($item) {
            return [
                'label' => ucwords($item),
                'value' => $item,
            ];
        });

        $components = $logs->groupBy('component')->keys()->map(function ($item) use ($type) {
            return [
                'label' => Helper::getLogInitiator($item, $type),
                'value' => $item,
            ];
        });

        $formIds = $logs->pluck('form_id')->unique()->filter()->toArray();

        $forms = Form::select('id', 'title')->whereIn('id', $formIds)->get();

        return apply_filters('fluentform/get_log_filters', [
            'statuses'   => $statuses,
            'components' => $components,
            'forms'      => $forms,
        ]);
    }

    public function getSubmissionLogs($submissionId, $attributes = [])
    {
        $logType = Arr::get($attributes, 'log_type', 'logs');

        $sourceType = Arr::get($attributes, 'source_type', 'submission_item');

        if ('logs' === $logType) {
            $logs = Log::where('source_id', $submissionId)
                ->where('source_type', $sourceType)
                ->orderBy('id', 'DESC')
                ->get();

            $log = apply_filters_deprecated(
                'fluentform_entry_logs',
                [
                    $logs,
                    $submissionId
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/submission_logs',
                'Use fluentform/submission_logs instead of fluentform_entry_logs.'
            );

            $logs = apply_filters('fluentform/submission_logs', $logs, $submissionId);

            $entryLogs = [];

            foreach ($logs as $log) {
                $entryLogs[] = [
                    'id'          => $log->id,
                    'status'      => $log->status,
                    'title'       => $log->component . ' (' . $log->title . ')',
                    'description' => $log->description,
                    'created_at'  => (string) $log->created_at,
                ];
            }
        }
        else {
            $columns = [
                'id',
                'action',
                'status',
                'note',
                'created_at',
            ];

            $logs = Scheduler::select($columns)
                ->where('origin_id', $submissionId)
                ->orderBy('id', 'DESC')
                ->get();

            $logs = apply_filters_deprecated(
                'fluentform_entry_api_logs',
                [
                    $logs,
                    $submissionId
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/submission_api_logs',
                'Use fluentform/submission_api_logs instead of fluentform_entry_api_logs.'
            );
            $logs = apply_filters('fluentform/submission_api_logs', $logs, $submissionId);

            $entryLogs = [];

            foreach ($logs as $log) {
                $entryLog = [
                    'id'          => $log->id,
                    'status'      => $log->status,
                    'title'       => 'n/a',
                    'description' => $log->note,
                    'created_at'  => (string) $log->created_at,
                ];

                if ($log->action) {
                    $entryLog['title'] = Helper::getLogInitiator($log->action, $logType);
                }

                $entryLogs[] = $entryLog;
            }
        }

        return apply_filters('fluentform/submission_logs', $entryLogs, $submissionId);
    }

    public function remove($attributes = [])
    {
        $ids = Arr::get($attributes, 'log_ids');

        if (!$ids) {
            throw new ValidationException(
                __('No selections found', 'fluentform')
            );
        }

        $logType = Arr::get($attributes, 'type', 'log');

        $model = 'log' === $logType ? Log::query() : Scheduler::query();

        $model->whereIn('id', $ids)->delete();

        return [
            'message' => __('Selected log(s) successfully deleted', 'fluentform'),
        ];
    }
}
