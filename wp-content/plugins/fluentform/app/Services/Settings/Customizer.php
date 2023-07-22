<?php

namespace FluentForm\App\Services\Settings;

use Exception;
use FluentForm\App\Models\FormMeta;
use FluentForm\Framework\Support\Arr;

class Customizer
{
    public function get($formId)
    {
        $customizer = FormMeta::where('form_id', $formId)
            ->whereIn('meta_key', ['_custom_form_css', '_custom_form_js', '_ff_form_styler_css', '_ff_selected_style'])
            ->get()
            ->keyBy(function ($item) {
                if ($item->meta_key === '_custom_form_css') {
                    return 'css';
                } elseif ($item->meta_key === '_custom_form_js') {
                    return 'js';
                } elseif ($item->meta_key === '_ff_form_styler_css') {
                    return 'styler';
                } elseif ($item->meta_key === '_ff_selected_style') {
                    return 'selected_style';
                }
            })
            ->transform(function ($item) {
                return $item->value;
            })->toArray();
        
        return [
            'css'            => Arr::get($customizer, 'css'),
            'js'             => Arr::get($customizer, 'js'),
            'styler'         => Arr::get($customizer, 'styler'),
            'selected_style' => Arr::get($customizer, 'selected_style'),
        ];
    }

    public function store($attributes = [])
    {
        if (!fluentformCanUnfilteredHTML()) {
            throw new Exception(
                __('You need unfiltered_html permission to save Custom CSS & JS', 'fluentform')
            );
        }

        $formId = absint(Arr::get($attributes, 'form_id'));

        $css = fluentformSanitizeCSS(Arr::get($attributes, 'css'));
        $js = fluentform_kses_js(Arr::get($attributes, 'js'));

        FormMeta::persist($formId, '_custom_form_css', $css);
        FormMeta::persist($formId, '_custom_form_js', $js);
    }
}
