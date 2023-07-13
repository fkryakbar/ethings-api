<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage as FacadesStorage;

class PublicController extends Controller
{
    public function get_folder_items($folder_id)
    {
        $folder = Storage::where('item_id', $folder_id)->with('user')->first();
        if ($folder && $folder->type == 'folder') {
            if ($folder->access == 'public' || $folder->access == 'open') {
                return response([
                    'message' => 'Item received',
                    'folder' => $folder,
                    'data' => Storage::where('belongs_to', $folder_id)->with('user')->get()
                ]);
            }
            return response([
                'message' => 'Private items'
            ], 403);
        }
        return response([
            'message' => 'Item not found'
        ], 404);
    }

    public function download_item($item_id)
    {
        $item = Storage::where('item_id', $item_id)->first();
        $the_folder = Storage::where('item_id', $item->belongs_to)->first();
        if ($item && $item->type != 'folder') {
            if ($item->access == 'public' || $item->access == 'open' || $the_folder->access == 'public' || $the_folder->access == 'open') {
                if (FacadesStorage::exists($item->real_path)) {
                    $headers = [
                        'Content-Type' => FacadesStorage::mimeType($item->real_path),
                        'Content-Disposition' => 'inline; filename=' . $item->name,
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                    ];
                    return response()->file(storage_path('app/public/') . $item->real_path, $headers);
                }
                return response(['message' => 'File missing'], 404);
            }
            return response(['message' => 'Item is private'], 403);
        }
        return response(['message' => 'Item not found'], 404);
    }
}
