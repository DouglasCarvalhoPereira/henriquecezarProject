<?php

namespace FluentForm\App\Http\Controllers;

use Exception;
use FluentForm\App\Services\Analytics\AnalyticsService;
use FluentForm\App\Services\Browser\Browser;
use FluentForm\App\Services\Form\FormService;

class AnalyticsController extends Controller
{
    /**
     * Store Form Analytics data
     *
     * @param  \FluentForm\App\Services\Form\FormService $formService
     * @return \WP_REST_Response
     */
    public function store($formId)
    {
        try {
            $analyticsService = new AnalyticsService();
            $data = [
                'form_id'    => $formId,
                'ip'         => $this->request->getIp(),
                'source_url' => $this->request->server('HTTP_REFERER', '')
            ];
            return $this->sendSuccess($analyticsService->store($data));
        } catch (Exception $e) {
            return $this->sendError([
                'message' => __('Something went wrong, please try again!', 'fluentform'),
                'error'   => $e->getMessage(),
            ]);
        }
    }
    
    public function reset(AnalyticsService $analyticsService,$formId)
    {
        try {
            return $this->sendSuccess(
                $analyticsService->reset(intval($formId))
            );
        } catch (Exception $e) {
            return $this->sendError([
                'message' => __('Something went wrong, please try again!', 'fluentform'),
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
