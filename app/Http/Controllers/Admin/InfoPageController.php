<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\InfoPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class InfoPageController extends Controller
{
    public function index()
    {
        $infoPages = InfoPage::get();
        return view('admin.infoPage.index', compact('infoPages'));
    }

    public function show($page_url)
    {
        $page = InfoPage::where('page_url', $page_url)->first();
        if (!$page) {
            abort(404);
        }
        return view("admin.infoPage.show", compact('page'));
    }
    public function create()
    {
        return view('admin.infoPage.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_name' => 'required',
            'page_content' => 'required',

        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            if (InfoPage::where('page_name', $request->page_name)->first()) {
                flash()->error("Page All Ready exist");
                return redirect()->route('info-pages.create');
            }

            $pages =  new InfoPage;
            $pages->page_name   = $request->page_name;
            $pages->page_content = $request->page_content;
            $pages->page_url = $this->slugify($request->page_name);
            $pages->save();
            flash()->success('Page has been created successfully!');
            return redirect()->route('info-pages.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('info-pages.create');
        }
    }

    public function edit($id)
    {
        $page = InfoPage::find($id);
        return view('admin.infoPage.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'page_name' => 'required',
            'page_content' => 'required',

        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            if (InfoPage::where('page_name', $request->page_name)->where('id', '<>', $id)->first()) {
                flash()->error("Page All Ready exist");
                return redirect()->route('info-pages.create');
            }

            $pages =  InfoPage::find($id);
            $pages->page_name   = $request->page_name;
            $pages->page_content = $request->page_content;
            $pages->page_url = $this->slugify($request->page_name);
            $pages->save();
            flash()->success('Page has been updated successfully!');
            return redirect()->route('info-pages.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('info-pages.edit', $id);
        }
    }
    public function destroy($id)
    {
        try {
            $page = InfoPage::find($id);
            $page->delete();
            flash()->success('Page has been deleted successfully!');
            return redirect()->route('info-pages.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('info-pages.index');
        }
    }

    public static function slugify($text, string $divider = '-')
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public function privacyPolicy()
    {
        $page = InfoPage::where('page_url', 'privacy-policy')->first();
        if (!$page) {
            abort(404);
        }
        return view("admin.infoPage.show", compact('page'));
    }
}
