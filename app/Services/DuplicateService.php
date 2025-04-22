<?php

namespace App\Services;

class DuplicateService
{
    public function isDuplicate($data)
    {
        
        // Perform the duplicate check logic here
        // This is just an example of how you might structure it
        $isDuplicate = false;
        $msg = '';

        // Check duplicate based on provided name and gstin (this is just a placeholder logic)
        if ($data['name'] === 'Duplicate Company' || $data['gstin'] === 'duplicate-gst') {
            $isDuplicate = true;
            $msg = 'Duplicate company found!';
        }

        return [
            'status' => $isDuplicate,
            'msg' => $msg,
        ];
    }
}
