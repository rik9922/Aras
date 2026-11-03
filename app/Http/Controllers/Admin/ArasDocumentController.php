<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ArasDocuments;
use Illuminate\Support\Facades\Auth;

class ArasDocumentController extends Controller{
    
    public function index(Request $request){
        $search = $request->input('search');
        $query  = ArasDocuments::where('user_id', Auth::user()->id);

        if ($search) {
            $query->where('file_name', 'like', '%' . $search . '%');
        }

        $documents = $query->orderByDesc('id')->paginate(20)->appends(['search' => $search]);
        return view('admin.documents.index', compact('documents', 'search'));
    }
    
    public function uploadDocument(Request $request){
        $file_data = '';
        if ($request->hasFile('file')) {
            $app_url    =   config('app.url');
            $path       =   public_path('documents/');
            $file       =   $request->file('file');
            $name       =   Carbon::now()->timestamp.'.'.$file->getClientOriginalExtension();
            $file_size  =   $file->getSize(); // capture size BEFORE move
            $file->move($path, $name);  
            $file_data = ($app_url.'/public/documents/'.$name);
            
            $temp=new ArasDocuments();
            $temp->user_id=Auth::user()->id;
            $temp->file_name=$file->getClientOriginalName();
            $temp->url=$file_data;
            $temp->file_size=$file_size;
            $temp->save();
            
            return ["message"=>'Document Uploaded Successfully!'];
        }else {
            return ["message"=>'Invalid File'];
        }
    }
    
    public function getDocuments(Request $request){
        return ArasDocuments::where('user_id', Auth::user()->id)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Rename and/or replace a document file.
     * Only the owning user may edit their document.
     */
    public function update(Request $request, $id)
    {
        $document = ArasDocuments::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $request->validate([
            'file_name' => 'required|string|max:255',
            'file'      => 'nullable|file|max:51200',
        ]);

        // If a new file was uploaded, replace the old one on disk
        if ($request->hasFile('file')) {
            // Delete old physical file
            $oldFilename = basename($document->url);
            $oldFilePath = public_path('documents/' . $oldFilename);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }

            // Store the new file
            $app_url = config('app.url');
            $path    = public_path('documents/');
            $file      = $request->file('file');
            $newName   = Carbon::now()->timestamp . '.' . $file->getClientOriginalExtension();
            $file_size = $file->getSize(); // capture size BEFORE move
            $file->move($path, $newName);
            $document->url       = $app_url . '/public/documents/' . $newName;
            $document->file_size = $file_size;
        }

        $document->file_name = $request->input('file_name');
        $document->save();

        return redirect()->route('admin.documents')
            ->with('success', 'Document updated successfully.');
    }

    /**
     * Permanently delete a document and its physical file.
     * Only the owning user may delete their own document.
     */
    public function destroy($id)
    {
        $document = ArasDocuments::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        // Remove physical file from disk
        $filename = basename($document->url);
        $filePath = public_path('documents/' . $filename);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $document->delete();

        return redirect()->route('admin.documents')
            ->with('success', 'Document deleted successfully.');
    }
}