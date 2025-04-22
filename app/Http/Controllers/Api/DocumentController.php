<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function handleRequest(Request $request)
    {
       
        $action = $request->input('action');
        
        if ($action === 'delete') {
            return $this->deleteDoc($request);
        } else {
            return $this->insertDoc($request);
        }
    }

    protected function insertDoc(Request $request)
    {
           
      
        $response = [
            'doc_name' => '',
            'filename' => '',
            'doc_urn' => '',
            'status' => false,
            'msg' => '',
        ];

        $linked_to = $request->input('linked_to');
       
        $linked_id = $request->input('linked_id');
        
        $company_type = $request->input('company_type');
       

        if ($linked_to == 'booking' && $request->input('booking_urn')) {

            $linked_id = $request->input('booking_urn');
        } elseif ($linked_to == 'notes') {
            
            $linked_id = decrypt($linked_id); // Assuming you have a decryption method
        } elseif ($linked_to == 'company') {
            
            $prefix = 'TE' . strtoupper(substr($company_type, 0, 2)) . strtoupper(substr($request->input('company_name'), 0, 2));
           
            $company_urn = $this->generateURN($prefix, 'company');
           
            $linked_id = $company_urn;
          
        }
        

        $doc_urn = strtoupper($this->generateURN($linked_to));
        

        if (empty($linked_id)) {

            $response['msg'] = "Link Error!";
            return response()->json($response);
        }
        

        // $file = $request->input('file_metadata');
        $file = $request->input('file_metadata');
        $doc_name = $request->input('doc_name');
        $doc_type = $request->input('doc_type');
        $size = $file['size'];
        $hashkey = time();
        $shaFilename = sha1($file['name'] . $hashkey);
        $filepath = 'uploads/' . $linked_to . "/" . substr($shaFilename, 0, 2) . "/" . substr($shaFilename, 2, 2) . "/";
       
        $uploadfile = $doc_urn . '.' . $file['name'];
        if ($file instanceof \Illuminate\Http\UploadedFile) {
           
        $file->move(public_path($filepath), $uploadfile);

            $response['msg'] = "File Uploaded!";
            $response['status'] = true;

            DB::table('docs')->insert([
                'doc_urn' => $doc_urn,
                'doc_name' => $doc_name,
                'doc_type' => $doc_type,
                'original_filename' => $filename,
                'filename' => $uploadfile,
                'size' => $size,
                'filepath' => $filepath,
                'linked_id' => $linked_id,
                'linked_to' => $linked_to,
                'doe' => now(),
                'excel_link' => $request->input('excel_link'),
                'co_id' => $request->input('co_id'),
            ]);

            $response['doc_urn'] = $doc_urn;
            $response['doc_name'] = $doc_name;
            $response['original_filename'] = $filename . " (" . $this->formatFileSize($size) . ")";
        } else {
            $response['msg'] = "File Upload Error!";
        }

        return response()->json($response);
    }

    protected function deleteDoc(Request $request)
    {
        
        $doc_urn = $request->input('doc_urn');
        $response = ['status' => false, 'msg' => ''];

        if ($doc_urn) {
            $deleted = DB::table('docs')
                ->where('doc_urn', $doc_urn)
                ->update(['deleted' => DB::raw('ABS(deleted - 1)')]);

            if ($deleted) {
                $response['status'] = true;
                $response['msg'] = "Action Processed!";
            } else {
                $response['msg'] = "Action not executed!";
            }
        } else {
            $response['msg'] = "Document Reference missing!";
        }

        return response()->json($response);
    }

    protected function generateURN($prefix = '', $type = '')
    {
        // Your URN generation logic here (e.g., random string of 10 chars)
        return strtoupper(uniqid($prefix));
    }

    protected function formatFileSize($size)
    {
        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }
}
