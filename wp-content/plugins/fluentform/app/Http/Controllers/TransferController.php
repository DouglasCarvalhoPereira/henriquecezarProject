<?php

namespace FluentForm\App\Http\Controllers;

use Exception;
use FluentForm\App\Services\Transfer\TransferService;

class TransferController extends Controller
{
    /**
     * Export forms as JSON.
     */
    public function exportForms()
    {
        try {
            $formIds = $this->request->get('forms');
            TransferService::exportForms($formIds);
        } catch (Exception $exception) {
            $this->json([
                'message' => $exception->getMessage()
            ], 424);
        }
    }
    
    /**
     * Import forms from a previously exported JSON file.
     */
    public function importForms()
    {
        try {
            $file = $this->request->file('file');
            $this->json(TransferService::importForms($file), 200);
        } catch (Exception $exception) {
            $this->json([
                'message' => $exception->getMessage()
            ], 424);
        }
    }

    public function exportEntries() {
        try {
            TransferService::exportEntries($this->request->get());
        } catch (Exception $exception) {
            $this->json([
                'message' => $exception->getMessage()
            ], 424);
        }
    }
}
