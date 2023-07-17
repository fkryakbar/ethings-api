<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage as FacadesStorage;

class PublicController extends Controller
{
    private function generate_id()
    {
        $str = Str::random(4) . '-' . Str::random(3) . '-' . Str::random(3);
        return $str;
    }
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

    public function upload(Request $request)
    {
        $request->validate([
            "file" => ["required"],
            "file.*" => ["required", 'max:5120'],
            "belongs_to" => ['required']
        ]);
        if ($request->belongs_to) {
            $isValid = Storage::where('item_id', $request->belongs_to)->where('access', 'open')->first();
            if (!$isValid) {
                return response(['message' => 'Folder is not valid'], 422);
            }
        }
        foreach ($request->file('file') as $file) {
            $path = $file->store('');

            $request->merge([
                'user_id' =>  $isValid->user_id,
                'item_id' => $this->generate_id() . '.' . $file->getClientOriginalExtension(),
                'real_path' => $path,
                'type' => $file->getClientOriginalExtension(),
                'name' => $file->getClientOriginalName(),
                'file_size' => number_format(($file->getSize() / 1000), 2) . ' KB'
            ]);
            Storage::create($request->except(['file']));
        }
        return response([
            'message' => 'File(s) Uploaded'
        ]);
    }

    public function delete_file(Request $request)
    {
        $request->validate([
            'item_ids' => ['array', 'required']
        ]);

        foreach ($request->item_ids as $item_id) {
            $item = Storage::where('item_id', $item_id)->first();
            if ($item) {
                $the_folder = Storage::where('item_id', $item->belongs_to)->first();
                if ($item->type == 'folder' && ($the_folder->access == 'private' || $the_folder->access == 'public')) {
                    return response(['message' => 'Access denied'], 422);
                }
            } else {
                return response(['message' => 'Item Not found'], 404);
            }
        }
        foreach ($request->item_ids as $item_id) {
            $item = Storage::where('item_id', $item_id)->first();
            FacadesStorage::delete($item->real_path);
            $item->delete();
        }
        return response(['message' => 'Item Deleted']);
    }

    public function update_file(Request $request, $item_id)
    {
        $request->validate([
            'name' => 'required|max:40',
        ]);
        $item = Storage::where('item_id', $item_id)->where('type', '!=', 'folder')->first();
        $the_folder = Storage::where('item_id', $item->belongs_to)->where('access', 'open')->where('type', 'folder')->first();
        if ($item && $the_folder) {
            $item->update($request->only(['name']));
            return response([
                'message' => 'Item Updated',
                'data' => $item
            ]);
        }
        return response(['message' => 'Item not found'], 404);
    }
}
